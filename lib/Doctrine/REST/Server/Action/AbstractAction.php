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

namespace Doctrine\REST\Server\Action;

use Doctrine\REST\Server\RequestHandler,
    Doctrine\ORM\EntityManager,
    Doctrine\REST\Server\ServerException;

/**
 * Abstract server action class for REST server actions to extend from.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
abstract class AbstractAction
{
    protected $_requestHandler;
    protected $_configuration;
    protected $_source;
    protected $_request;
    protected $_response;

    public function __construct(RequestHandler $requestHandler)
    {
        $this->_requestHandler = $requestHandler;
        $this->_configuration = $requestHandler->getConfiguration();
        $this->_source = $this->_configuration->getSource();
        $this->_request = $requestHandler->getRequest();
        $this->_response = $requestHandler->getResponse();
    }

    public function executeORM()
    {
    }

    public function executeDBAL()
    {
    }

    protected function _enforceReadOnly()
    {
        if ($this->_configuration->getEntity($this->_getEntity())->isReadOnly()) {
            throw ServerException::readOnly();
        }
    }

    protected function _getBaseUrl()
    {
        return $this->_configuration->getBaseUrl();
    }

    protected function _getEntity()
    {
        return $this->_requestHandler->getEntity();
    }

    protected function _getEntityIdentifierKey()
    {
        return $this->_configuration->getEntityIdentifierKey($this->_getEntity());
    }

    protected function _setRootNodeName($rootNodeName)
    {
        $this->_response->setRootNodeName($rootNodeName);
    }

    protected function _setQueryFirstAndMax($q)
    {
        if ( ! isset($this->_request['_page']) && ! isset($this->_request['_first']) && ! isset($this->_request['_max'])) {
            $this->_request['_page'] = '1';
        }
        $maxPerPage = isset($this->_request['_max_per_page']) ? $this->_request['_max_per_page'] : 20;
        if (isset($this->_request['_page'])) {
            $page = $this->_request['_page'];
            $first = ($page - 1) * $maxPerPage;
        } else {
            if (isset($this->_request['_first'])) {
                $first = $this->_request['_first'];
            } else {
                $first = 0;
            }
            if (isset($this->_request['_max'])) {
                $maxPerPage = $this->_request['_max'];
            }
        }

        if ($this->_source instanceof EntityManager) {
            $q->setFirstResult($first);
            $q->setMaxResults($maxPerPage);
        } else {
            $platform = $this->_source->getDatabasePlatform();
            return $platform->modifyLimitQuery($q, $maxPerPage, $first);
        }
    }

    protected function _findEntityById()
    {
        if ($this->_source instanceof EntityManager) {
            $entity = $this->_getEntity();
            $id = $this->_request['_id'];

            $qb = $this->_source->createQueryBuilder()
                ->select('a')
                ->from($entity, 'a')
                ->where('a.id = ?1')
                ->setParameter('1', $id);
            $query = $qb->getQuery();

            try {
                $result = $query->getSingleResult();
            } catch (\Exception $e) {
                throw ServerException::notFound();
            }
        } else {
            $entity = $this->_getEntity();
            $identifierKey = $this->_getEntityIdentifierKey($entity);

            $query = sprintf('SELECT * FROM %s WHERE %s = ?', $entity, $identifierKey);

            $result = $this->_source->fetchRow($query, array($this->_request['_id']));
        }
        if ( ! $result) {
            throw ServerException::notFound();
        }
        return $result;
    }

    protected function _updateEntityInstance($entity)
    {
        $data = $this->_gatherData($this->_request->getData());
        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (is_callable(array($entity, $setter))) {
                $entity->$setter($value);
            }
        }
        return $entity;
    }

    protected function _gatherData()
    {
        $data = array();
        foreach ($this->_request->getData() as $key => $value) {
            if ($key[0] == '_') {
                continue;
            }
            $data[$key] = $value;
        }
        return $data;
    }
}