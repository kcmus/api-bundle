<?php

namespace RJP\ApiBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

abstract class AbstractSecurityUser
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=45, nullable=false)
     */
    protected $name;

    /**
     * @ORM\OneToMany(targetEntity="RJP\ApiBundle\Entity\SecurityUserRole", mappedBy="user", cascade={"persist"})
     **/
    protected $roles;

    /**
     * @ORM\OneToOne(targetEntity="RJP\ApiBundle\Entity\SecurityToken", mappedBy="securityUser")
     */
    protected $token;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
    }

    /**
     * @return \RJP\ApiBundle\Entity\SecurityToken
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return mixed
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /** @param \RJP\ApiBundle\Entity\SecurityUserRole $role */
    public function addRoles($role)
    {
        $role->setUser($this);
        $this->roles->add($role);
    }

    /**
     * @param mixed $roles
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
