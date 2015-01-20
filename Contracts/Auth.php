<?php

namespace RJP\ApiBundle\Contracts;

use RJP\ApiBundle\Contract;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

class Auth extends Contract
{
    /**
     * Authorization token.
     *
     * @Serializer\Type("string")
     */
    protected $token;

    /**
     * Authorization username.
     *
     * @Serializer\Groups({"Post"})
     * @Serializer\Type("string")
     * @Assert\Type(type="string", message="The value {{ value }} is not a valid {{ type }}.")
     * @Assert\NotBlank()
     */
    protected $username;

    /**
     * Authorization password.
     *
     * @Serializer\Groups({"Post"})
     * @Serializer\Type("string")
     * @Assert\Type(type="string", message="The value {{ value }} is not a valid {{ type }}.")
     * @Assert\NotBlank()
     */
    protected $password;

    /**
     * @param mixed $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
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
    public function getToken()
    {
        return $this->token;
    }


}