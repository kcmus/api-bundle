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
    /** @var \Doctrine\ORM\EntityManager $em */
    private $doctrine;

    /** @var \RJP\ApiBundle\Entity\AbstractSecurityToken userToken */
    private $userToken;

    /** @var \Symfony\Component\DependencyInjection\Container $container */
    private $container;

    private $tokenClass;
    private $securityUserClass;
    private $securityRoleClass;

    public function __construct($doctrine, $container)
    {
        $this->doctrine = $doctrine;
        $this->container = $container;
    }

    private function setupClasses()
    {
        if (empty($this->tokenClass))
        {
            $this->tokenClass = $this->container->getParameter('rjp.api.token_class');
        }

        if (empty($this->securityUserClass))
        {
            $this->securityUserClass = $this->container->getParameter('rjp.api.security_user_class');
        }

        if (empty($this->securityRoleClass))
        {
            $this->securityRoleClass = $this->container->getParameter('rjp.api.security_role_class');
        }
    }

    /**
     * @param $apiKey
     * @return string
     */
    public function getUsernameForApiKey($apiKey)
    {
        $this->setupClasses();

        try
        {
            $this->userToken = $this->doctrine->createQuery('
                select t
                from '.$this->tokenClass.' t
                where t.token = :token
            ')->setParameter('token', $apiKey)->getSingleResult();

            return $this->userToken->getSecurityUser()->getName();
        }
        catch (\Exception $e)
        {
            return null;
        }
    }

    public function loadUserByUsername($username)
    {
        $this->setupClasses();

        $roles = array();

        /** @var \RJP\ApiBundle\Entity\AbstractSecurityUserRole $role */
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