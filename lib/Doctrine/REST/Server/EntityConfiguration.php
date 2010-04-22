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
 * EntityConfiguration for REST server.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class EntityConfiguration
{
    private $_name;
    private $_alias;
    private $_singular;
    private $_plural;
    private $_identifierKey = 'id';
    private $_readOnly = false;
    private $_username;
    private $_password;
    private $_actions = array();

    public function __construct($name, $alias, $singular = null, $plural = null)
    {
        if ($singular === null) {
            $singular = $alias;
        }
        if ($plural === null) {
            $plural = $alias;
        }
        $this->_name = $name;
        $this->_alias = $alias;
        $this->_singular = $singular;
        $this->_plural = $plural;
    }

    public function isSecure()
    {
        return $this->_username ? true : false;
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

    public function setIdentifierKey($identifierKey)
    {
        $this->_identifierKey = $identifierKey;
    }

    public function getIdentifierKey()
    {
        return $this->_identifierKey;
    }

    public function setName($name)
    {
        $this->_name = $name;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function getAlias()
    {
        return $this->_alias;
    }

    public function getSingular()
    {
        return $this->_singular;
    }

    public function getPlural()
    {
        return $this->_plural;
    }

    public function isReadOnly($bool = null)
    {
        if ($bool !== null) {
            $this->_readOnly = $bool;
        }
        return $this->_readOnly;
    }

    public function registerAction($action, $className)
    {
        $this->_actions[$action] = $className;
    }

    public function hasAction($action)
    {
        return isset($this->_actions[$action]) ? true : false;
    }

    public function getAction($action)
    {
        return $this->_actions[$action];
    }
}