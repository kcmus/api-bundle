<?php

namespace RJP\ApiBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * User
 *
 * @ORM\Table(name="security_user")
 * @ORM\Entity
 */
class SecurityUser extends AbstractSecurityUser
{
    public function __construct()
    {
        parent::__construct();
    }
}
