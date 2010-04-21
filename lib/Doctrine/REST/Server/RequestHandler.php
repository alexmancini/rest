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

use Doctrine\ORM\EntityManager,
    Doctrine\DBAL\Connection;

/**
 * Class responsible for transforming a REST server request to a response.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class RequestHandler
{
    private $_source;
    private $_request;
    private $_response;

    public function __construct(Configuration $configuration, Request $request, Response $response)
    {
        $this->_configuration = $configuration;
        $this->_source = $configuration->getSource();
        $this->_request = $request;
        $this->_response = $response;
        $this->_response->setRequestHandler($this);
    }

    public function getConfiguration()
    {
        return $this->_configuration;
    }

    public function getRequest()
    {
        return $this->_request;
    }

    public function getResponse()
    {
        return $this->_response;
    }

    public function getEntity()
    {
        return $this->_configuration->resolveEntityAlias($this->_request['_entity']);
    }

    public function execute()
    {
        try {
            $entity = $this->getEntity();

            if ($this->_configuration->getUsername()) {
                if ( ! $this->_configuration->getAuthenticatedUsername()) {
                    $this->_configuration->sendAuthentication();
                    throw ServerException::notAuthorized();
                } else {
                    if ( ! $this->_configuration->hasValidCredentials($this->_request['_action'], $entity, $this->_request['_id'])) {
                        throw ServerException::notAuthorized();
                    }
                }
            }

            $class = $this->_configuration->getAction($entity, $this->_request['_action']);
            if ( ! $class) {
                throw ServerException::notFound();
            }
            $actionInstance = new $class($this);

            if (method_exists($actionInstance, 'execute')) {
                $result = $actionInstance->execute();
            } else {
                if ($this->_source instanceof EntityManager) {
                    $result = $actionInstance->executeORM();
                } else {
                    $result = $actionInstance->executeDBAL();
                }
            }

            $this->_response->setResponseData(
                $this->_transformResultForResponse($result)
            );
        } catch (\Exception $e) {
            $this->_response->setError($e->getMessage(), $e->getCode());
        }
        return $this->_response;
    }

    private function _transformResultForResponse($result, $array = null)
    {
        if ( ! $array) {
            $array = array();
        }
        if (is_object($result)) {
            $entityName = get_class($result);
            if ($this->_source instanceof EntityManager) {
                $class = $this->_source->getMetadataFactory()->getMetadataFor($entityName);
                foreach ($class->fieldMappings as $fieldMapping) {
                    $array[$fieldMapping['fieldName']] = $this->_formatValue($class->getReflectionProperty($fieldMapping['fieldName'])->getValue($result));
                }
            }
            $vars = get_object_vars($result);
            foreach ($vars as $key => $value) {
                if ( ! isset($array[$key])) {
                    $array[$key] = $this->_formatValue($value);
                }
            }
        } else if (is_array($result)) {
            foreach ($result as $key => $value) {
                if (is_object($value) || is_array($value)) {
                    if (is_object($value)) {
                        $key = $this->_request['_entity'] . $key;
                    }
                    $array[$key] = $this->_transformResultForResponse($value, isset($array[$key]) ? $array[$key] : array());
                } else {
                    $array[$key] = $this->_formatValue($value);
                }
            }
        }
        return $array;
    }

    private function _formatValue($value)
    {
        if ($value instanceof \DateTime) {
            if ($value->getTimestamp()) {
                return $value->format('c');
            } else {
                return;
            }
        } else if (($val = strtotime($value)) !== false) {
            return date('c', $val);
        } else if ($value === '0000-00-00 00:00:00') {
            return null;
        } else {
            return $value;
        }
    }
}