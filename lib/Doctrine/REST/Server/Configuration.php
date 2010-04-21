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
    Doctrine\ORM\Connection;

/**
 * REST server Configuration class.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Configuration
{
    private $_name = 'Doctrine REST API';
    private $_source;
    private $_entities = array();
    private $_baseUrl;
    private $_username;
    private $_password;
    private $_authenticatedUsername;
    private $_authenticatedPassword;
    private $_credentialsCallback;
    private $_authenticationCallback;
    private $_actions = array(
        'entities' => 'Doctrine\\REST\\Server\\Action\\EntitiesAction',
        'delete' => 'Doctrine\\REST\\Server\\Action\\DeleteAction',
        'get' => 'Doctrine\\REST\\Server\\Action\\GetAction',
        'insert' => 'Doctrine\\REST\\Server\\Action\\InsertAction',
        'update' => 'Doctrine\\REST\\Server\\Action\\UpdateAction',
        'list' => 'Doctrine\\REST\\Server\\Action\\ListAction'
    );

    public function __construct($source)
    {
        $this->_source = $source;
        $this->_credentialsCallback = array($this, 'checkCredentials');
        $this->_authenticationCallback = array($this, 'httpAuthenticate');
    }

    public function authenticate()
    {
        return call_user_func_array($this->_authenticationCallback, array($this));
    }

    public function httpAuthenticate()
    {
        header('WWW-Authenticate: Basic realm="' . $this->getName() . '"');
        header('HTTP/1.0 401 Unauthorized');
    }

    public function setName($name)
    {
        $this->_name = $name;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function registerAction($action, $className)
    {
        $this->_actions[$action] = $className;
    }

    public function getAction($entity, $action)
    {
        if (isset($this->_actions[$action])) {
            return $this->_actions[$action];
        }
        if (isset($this->_entities[$entity]) && $this->_entities[$entity]->hasAction($action)) {
            return $this->_entities[$entity]->getAction($action);
        }
    }

    public function checkCredentials($username, $password)
    {
        if ( ! $this->isSecure()) {
            return true;
        }

        if ($this->_username == $username && $this->_password == $password) {
            return true;
        } else {
            return false;
        }
    }

    public function hasValidCredentials($action, $entity, $id)
    {
        $args = array($this->_authenticatedUsername, $this->_authenticatedPassword, $action, $entity, $id);
        return call_user_func_array($this->_credentialsCallback, $args);
    }

    public function configureEntity($entity, $configuration)
    {
        $this->_entities[$entity] = $configuration;
    }

    public function setBaseUrl($baseUrl)
    {
        $this->_baseUrl = $baseUrl;
    }

    public function getBaseUrl()
    {
        return $this->_baseUrl;
    }

    public function getEntities()
    {
        return $this->_entities;
    }

    public function registerEntity(EntityConfiguration $entityConfiguration)
    {
        $this->_entities[$entityConfiguration->getName()] = $entityConfiguration;
    }

    public function getEntityIdentifierKey($entity)
    {
        return $this->_entities[$entity]->getIdentifierKey();
    }

    public function resolveEntityAlias($alias)
    {
        if ($alias) {
            foreach ($this->_entities as $entityConfiguration) {
                if ($entityConfiguration->getAlias() === $alias) {
                    return $entityConfiguration->getName();
                }
            }
            throw ServerException::notFound();
        }
    }

    public function setCredentialsCallback($callback)
    {
        $this->_credentialsCallback = $callback;
    }

    public function isSecure()
    {
        return ($this->_username && $this->_password) ? true : false;
    }

    public function getAuthenticatedUsername()
    {
        return $this->_authenticatedUsername;
    }

    public function setAuthenticatedUsername($authenticatedUsername)
    {
        return $this->_authenticatedUsername = $authenticatedUsername;
    }

    public function getAuthenticatedPassword()
    {
        return $this->_authenticatedPassword;
    }

    public function setAuthenticatedPassword($authenticatedPassword)
    {
        return $this->_authenticatedPassword = $authenticatedPassword;
    }

    public function getUsername()
    {
        return $this->_username;
    }

    public function setUsername($username)
    {
        $this->_username = $username;
    }

    public function getPassword()
    {
        return $this->_password;
    }

    public function setPassword($password)
    {
        $this->_password = $password;
    }

    public function getActions()
    {
        return $this->_actions;
    }

    public function getSource()
    {
        return $this->_source;
    }
}