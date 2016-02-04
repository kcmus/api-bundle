<?php

namespace RJP\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Token
 *
 * @ORM\Table(name="security_token", uniqueConstraints={@ORM\UniqueConstraint(name="token_UNIQUE", columns={"token"})}, indexes={@ORM\Index(name="fk_token_1_idx", columns={"security_user_id"})})
 * @ORM\Entity
 */
class SecurityToken
{
    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=255, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $token;

    /**
     * @ORM\OneToOne(targetEntity="SecurityUser", inversedBy="token")
     * @ORM\JoinColumn(name="security_user_id", referencedColumnName="id")
     */
    private $securityUser;

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
     * @return SecurityUser
     */
    public function getSecurityUser()
    {
        return $this->securityUser;
    }

    /**
     * @param SecurityUser $securityUser
     */
    public function setSecurityUser($securityUser)
    {
        $this->securityUser = $securityUser;
    }
}
