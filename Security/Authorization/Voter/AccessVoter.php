<?php

namespace RJP\ApiBundle\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\Authorization\Voter\RoleVoter;

/**
 * RoleHierarchyVoter uses a RoleHierarchy to determine the roles granted to
 * the user before voting.
 */
class AccessVoter extends RoleVoter
{
    private $roleHierarchy;

    public function __construct(RoleHierarchyInterface $roleHierarchy, $prefix = 'admin.')
    {
        $this->roleHierarchy = $roleHierarchy;

        parent::__construct($prefix);
    }

    /**
     * {@inheritdoc}
     */
    protected function extractRoles(TokenInterface $token)
    {
        return $this->roleHierarchy->getReachableRoles($token->getRoles());
    }
}
