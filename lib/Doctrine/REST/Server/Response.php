<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
*/

namespace Doctrine\REST\Server;

/**
 * Class that represents a REST server response.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Response
{
    private $_configuration;
    private $_requestHandler;
    private $_request;
    private $_responseData;
    private $_rootNodeName;

    public function __construct(Request $request)
    {
        $this->_request = $request;
    }

    public function setRequestHandler(RequestHandler $requestHandler)
    {
        $this->_requestHandler = $requestHandler;
        $this->_configuration = $requestHandler->getConfiguration();
    }

    public function setError($error, $code)
    {
        $this->_responseData = array();
        $this->_responseData['error'] = array('message' => $error, 'code' => $code);
    }

    public function setResponseData($responseData)
    {
        $this->_responseData = $responseData;
    }

    public function setRootNodeName($rootNodeName)
    {
        $this->_rootNodeName = $rootNodeName;
    }

    public function send()
    {
        $this->_sendHeaders();
        echo $this->getContent();
    }

    public function getContent()
    {
        $data = $this->_responseData;

        switch ($this->_request['_format']) {
            case 'php':
                return serialize($data);
            break;

            case 'json':
                return json_encode($data);
            break;

            case 'xml':
            default:
                if (isset($data['error'])) {
                    $pluralName = 'error';
                } else if ($this->_rootNodeName) {
                    $pluralName =  $this->_rootNodeName;
                } else if ($entity = $this->_requestHandler->getEntity()) {
                    $configuration = $this->_configuration->getEntity($entity);
                    $pluralName = $configuration->getPlural();
                } else {
                    $pluralName = $this->_request['_action'];
                }
                if (isset($configuration) && $configuration) {
                    $singularName = $configuration->getSingular();
                } else {
                    $singularName = $pluralName;
                }
                if ($pluralName === 'error') {
                    $data = $data['error'];
                }
                $count = count($data, true) - count($data);
                if ($count === 0) {
                    $pluralName = $singularName;
                }
                return $this->_arrayToXml($data, $pluralName, $singularName);
        }
    }

    private function _sendHeaders()
    {
        switch ($this->_request['_format']) {
            case 'php':
                header('Content-type: text/html;');
            break;

            case 'json':
                header('Content-type: text/json;');
                header('Content-Disposition: attachment; filename="' . $this->_request['_action'] . '.json"');
            break;

            case 'xml':
            default:
                header('Content-type: application/xml;');
        }
        
        if (isset($this->_responseData['error'])) {
            header($_SERVER['SERVER_PROTOCOL'] . sprintf(' %s %s', $this->_responseData['error']['code'], $this->_responseData['error']['message']));
        }
    }

    private function _arrayToXml($array, $pluralName = 'doctrine', $singularName = 'doctrine', $xml = null, $charset = null)
    {
        if ($xml === null) {
            $string = "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
            if ($pluralName) {
              $string .= "<$pluralName/>";
            }
            $xml = new \SimpleXmlElement($string);
        }

        foreach($array as $key => $value) {
            if (is_numeric($key)) {
                $key = $singularName . $key;
            }
            $key = preg_replace('/[^A-Za-z_]/i', '', $key);

            if (isset($key[0]) && $key[0] === '_') {
                $xml->addAttribute(substr($key, 1), $value);
            } else if (is_array($value) && ! empty($value)) {
                $node = $xml->addChild($key);
                $this->_arrayToXml($value, $pluralName, $singularName, $node, $charset);
            } else {
                $charset = $charset ? $charset : 'utf-8';
                if (strcasecmp($charset, 'utf-8') !== 0 && strcasecmp($charset, 'utf8') !== 0) {
                    $value = iconv($charset, 'UTF-8', $value);
                }
                $value = htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
                if ($value) {
                    $xml->addChild($key, $value);
                } else {
                    $xml->addChild($key);
                }
            }
        }

        return $this->_formatXml($xml);
    }

    private function _formatXml($simpleXml)
    {
        $xml = $simpleXml->asXml();

        // add marker linefeeds to aid the pretty-tokeniser (adds a linefeed between all tag-end boundaries)
        $xml = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $xml);

        // now indent the tags
        $token = strtok($xml, "\n");
        $result = ''; // holds formatted version as it is built
        $pad = 0; // initial indent
        $matches = array(); // returns from preg_matches()

        // test for the various tag states
        while ($token !== false) {
            // 1. open and closing tags on same line - no change
            if (preg_match('/.+<\/\w[^>]*>$/', $token, $matches)) {
                $indent = 0;
            // 2. closing tag - outdent now
            } else if (preg_match('/^<\/\w/', $token, $matches)) {
                $pad = $pad - 4;
            // 3. opening tag - don't pad this one, only subsequent tags
            } elseif (preg_match('/^<\w[^>]*[^\/]>.*$/', $token, $matches)) {
                $indent = 4;
            // 4. no indentation needed
            } else {
                $indent = 0; 
            }

            // pad the line with the required number of leading spaces
            $line = str_pad($token, strlen($token)+$pad, ' ', STR_PAD_LEFT);
            $result .= $line . "\n"; // add to the cumulative result, with linefeed
            $token = strtok("\n"); // get the next token
            $pad += $indent; // update the pad size for subsequent lines    
        }
        return $result;
    }
}