<?php

namespace RJP\ApiBundle\Security;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

use RJP\ApiBundle\Security\ApiUser;
use RJP\ApiBundle\Entity\Token;

class ApiKeyUserProvider implements UserProviderInterface
{
    private $doctrine;
    private $userToken;

    public function __construct($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function getUsernameForApiKey($apiKey)
    {
        $this->userToken = $this->doctrine->getRepository('RJPApiBundle:Token')->findOneByToken($apiKey);

        if (empty($this->userToken))
        {
            // Temporary until I can get a custom exception handler setup
            throw new AccessDeniedException();
        }

        try
        {
            return $this->userToken->getUser()->getEmail();
        }
        catch (\Exception $e)
        {
            throw new AccessDeniedException();
        }
    }

    public function loadUserByUsername($username)
    {
        $roles = array();

        foreach ($this->userToken->getUser()->getRole()->toArray() as $role)
        {
            $roles[] = $role->getName();
        }

        if (empty($roles))
        {
            $roles[] = "admin.user";
        }

        return new ApiUser(
            $username,
            null,
            null,
            $roles,
            $this->userToken->getUser()
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