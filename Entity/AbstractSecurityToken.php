<?php

namespace RJP\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

abstract class AbstractSecurityToken
{
    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=255, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $token;

    protected $securityUser;

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return \RJP\ApiBundle\Entity\AbstractSecurityUser
     */
    public function getSecurityUser()
    {
        return $this->securityUser;
    }

    /**
     * @param \RJP\ApiBundle\Entity\AbstractSecurityUser $securityUser
     */
    public function setSecurityUser($securityUser)
    {
        $this->securityUser = $securityUser;
    }
}
