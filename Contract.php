<?php

namespace RJP\ApiBundle;

use Symfony\Component\Validator;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\Serializer\Annotation as Serializer;

class Contract
{
    /**
     * @Serializer\Exclude
     */
    public $validator;

    /**
     * @Serializer\XmlKeyValuePairs
     * @Serializer\Type("array")
     * @Serializer\Groups({"Links"})
     */
    public $_links;

    /**
     * @Serializer\XmlKeyValuePairs
     * @Serializer\Type("array")
     * @Serializer\Groups({"Embedded"})
     */
    public $_embedded;

    public function __construct()
    {

    }

    /**
     * @param mixed $embedded
     */
    public function setEmbedded($embedded)
    {
        $this->_embedded = $embedded;
    }

    /**
     * @return mixed
     */
    public function getEmbedded()
    {
        return $this->_embedded;
    }

    /**
     * @param mixed $links
     */
    public function setLinks($links)
    {
        $this->_links = $links;
    }

    /**
     * @return mixed
     */
    public function getLinks()
    {
        return $this->_links;
    }

    /**
     * @param mixed $validator
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;
    }

    /**
     * @return mixed
     */
    public function getValidator()
    {
        return $this->validator;
    }

    public function validate()
    {
        return $this->validator->validate($this);
    }

    public function unsetProperty($property)
    {
        unset($this->$property);
    }

    public function getArray(&$arrayObject = null)
    {
        foreach ($this as $property => $value)
        {
            if (method_exists($this, 'get'.ucfirst($property)))
            {
                $propertyValue = $this->{'get'.ucfirst($property)}();

                if (is_array($propertyValue) && isset($propertyValue[0]) && method_exists($propertyValue[0], 'getArray'))
                {
                    foreach ($propertyValue as $arrayKey => &$arrayValue)
                    {
                        $arrayObject[$property][$arrayKey] = $arrayValue->getArray($arrayObject[$property][$arrayKey]);
                    }
                }
                else if (is_object($propertyValue) && $propertyValue instanceof Contract)
                {
                    $arrayObject[$property] = $propertyValue->getArray($arrayObject[$property]);
                }
                else if (!is_object($propertyValue) && $property != 'validator')
                {
                    $arrayObject[$property] = $propertyValue;
                }
            }
        }

        return $arrayObject;
    }

    public function getProperties()
    {
        $properties = array();

       foreach ($this as $key => $value)
       {
           $properties[] = $key;
       }

       return $properties;
    }
}