<?php

namespace RJP\ApiBundle\Entity;

use JMS\Serializer\Annotation as Serializer;

class Errors
{
    /**
     * @var array
     * @Serializer\XmlKeyValuePairs
     */
    private $errors;

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param array $errors
     */
    public function setErrors($errors)
    {
        $this->errors = $errors;
    }
}