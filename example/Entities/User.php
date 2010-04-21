<?php

namespace Entities;

/** @Entity @Table(name="user") */
class User
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /** @Column(type="string", length=255) */
    private $username;

    /** @Column(type="string", length=255) */
    private $password;

    /** @Column(type="datetime", name="created_at") */
    private $created_at;

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $created_at;
    }
}