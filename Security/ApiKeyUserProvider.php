<?php

namespace RJP\ApiBundle\Security;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

use RJP\ApiBundle\Security\ApiUser;
use RJP\ApiBundle\Entity\SecurityToken;

class ApiKeyUserProvider implements UserProviderInterface
{
    private $doctrine;
    /** @var \RJP\ApiBundle\Entity\SecurityToken userToken */
    private $userToken;

    public function __construct($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param $apiKey
     * @return string
     */
    public function getUsernameForApiKey($apiKey)
    {
        $this->userToken = $this->doctrine->getRepository('RJPApiBundle:SecurityToken')->findOneByToken($apiKey);

        if (empty($this->userToken))
        {
            // Temporary until I can get a custom exception handler setup
            return null;
        }

        try
        {
            return $this->userToken->getSecurityUser()->getName();
        }
        catch (\Exception $e)
        {
            throw new AccessDeniedException();
        }
    }

    public function loadUserByUsername($username)
    {
        $roles = array();

        /** @var \RJP\ApiBundle\Entity\SecurityUserRole $role */
        foreach ($this->userToken->getSecurityUser()->getRoles() as $role)
        {
            $roles[] = $role->getRole();
        }

        return new ApiUser(
            $username,
            null,
            null,
            $roles,
            $this->userToken->getSecurityUser()
        );
    }

    public function refreshUser(UserInterface $user)
    {
        // this is used for storing authentication in the session
        // but in this example, the token is sent in each request,
        // so authentication can be stateless. Throwing this exception
        // is proper to make things stateless
        throw new UnsupportedUserException();
    }

    public function supportsClass($class)
    {
        return 'RJP\ApiBundle\Security\ApiUser' === $class;
    }
}