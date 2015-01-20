<?php

namespace RJP\ApiBundle\Validator\Constraints;

use Doctrine\ORM\EntityManager;

use JMS\DiExtraBundle\Annotation\Validator;
use JMS\DiExtraBundle\Annotation\Service;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @Validator("dummy_class")
 */
class DummyClassValidator extends ConstraintValidator
{
    protected $em;

    /**
     * @InjectParams({
     *     "em" = @Inject("doctrine.orm.entity_manager")
     * })
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function validate($object, Constraint $constraint)
    {
        $this->context->addViolationAt(
            null,
            $constraint->message,
            array(),
            null
        );
    }
}

/**
 * @Annotation
 */
class DummyClass extends Constraint
{
    public $message = 'Dummy message';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy()
    {
        return 'dummy_class';
    }
}