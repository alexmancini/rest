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
 * Simple class for parsing PHP $_SERVER['PATH_INFO'] and $_REQUEST to build
 * a request array to pass to the server.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class PHPRequestParser
{
    public function getRequestArray()
    {
        $path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : null;
        $path = ltrim($path, '/');
        $e = explode('/', $path);
        $count = $path ? count($e) : 0;
        $end = end($e);
        $e2 = explode('.', $end);
        $e[count($e) - 1] = $e2[0];
        $format = isset($e2[1]) ? $e2[1] : 'xml';
        $entity = $e[0];
        $id = isset($e[1]) ? $e[1] : null;
        $action = isset($e[2]) ? $e[2] : null;
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        $method = isset($_REQUEST['_method']) ? $_REQUEST['_method'] : $method;
        $method = strtoupper($method);

        if ($count === 1) {
            if ($method === 'POST' || $method === 'PUT') {
                $action = 'insert';
            } else if ($method === 'GET') {
                $action = 'list';
            }
        } else if ($count === 2) {
            if ($method === 'POST' || $method === 'PUT') {
                $action = 'update';
            } else if ($method === 'GET') {
                $action = 'get';
            } else if ($method === 'DELETE') {
                $action = 'delete';
            }
        } else if ($count === 3) {
            $action = $action;
        } else {
          $action = 'entities';
        }

        $data = array_merge(array(
            '_entity' => $entity,
            '_id' => $id,
            '_action' => $action,
            '_format' => $format
        ), $_REQUEST);

        return $data;
    }
}