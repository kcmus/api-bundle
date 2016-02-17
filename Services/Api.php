<?php

namespace RJP\ApiBundle\Services;

use RJP\ApiBundle\Entity\Errors;
use RJP\ApiBundle\Contract;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Class Api
 * @package RJP\ApiBundle\Services
 */
class Api
{
    private $apiDoc;
    private $request;
    private $security;
    private $serializer;
    private $doctrine;
    private $connection;
    private $container;

    public function __construct(RequestStack $request, SecurityContextInterface $security, $apiDoc, $serializer, $container, $doctrine = null)
    {
        $this->apiDoc = $apiDoc;
        $this->request = $request;
        $this->security = $security;
        $this->serializer = $serializer;
        $this->doctrine = $doctrine;
        $this->container = $container;
    }

    /**
     * @param mixed $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return mixed
     */
    public function getDoctrine()
    {
        return $this->doctrine;
    }

    /**
     * @return mixed
     */
    public function getConnection()
    {
        return $this->connection;
    }

    private $errorContainer;
    /**
     * Raw API request
     *
     * @var string
     */
    private $rawApiRequest;
    /**
     * Interpreted API request
     *
     * @var \RJP\ApiBundle\Contract
     */
    private $apiRequest;
    /**
     * Array of errors associated with current request
     *
     * @var array
     */
    private $apiRequestErrors;
    /**
     * Current API version of request
     *
     * @var string
     */
    private $apiRequestVersion = null;
    /**
     * API version requested as the response
     *
     * @var string
     */
    private $apiResponseVersion = null;
    /**
     * Flag for whether or not we want to persi
     *
     * @var bool
     */
    private $validateOnly = false;

    /**
     * @return boolean
     */
    public function isValidateOnly()
    {
        return $this->validateOnly;
    }

    /**
     * @param boolean $validateOnly
     */
    public function setValidateOnly($validateOnly)
    {
        $this->validateOnly = $validateOnly;
    }

    /**
     * Sets the current raw HTTP POST|PUT|PATCH request
     *
     * @param string $rawApiRequest Raw POST|PUT|PATCH request
     */
    public function setRawApiRequest($rawApiRequest)
    {
        $this->rawApiRequest = $rawApiRequest;
    }

    /**
     * Returns the current raw POST|PUT|PATCH request
     *
     * @return string Raw POST|PUT|PATCH request
     */
    public function getRawApiRequest()
    {
        return $this->rawApiRequest;
    }

    /**
     * Sets the current interpreted request
     *
     * @param \RJP\ApiBundle\Contract $apiRequest API request
     */
    public function setApiRequest(\RJP\ApiBundle\Contract $apiRequest)
    {
        $this->apiRequest = $apiRequest;
    }

    /**
     * Get the current interpreted request
     *
     * @return \RJP\ApiBundle\Contract API request
     */
    public function getApiRequest()
    {
        return $this->apiRequest;
    }

    /**
     * Sets the current API request version
     *
     * @param null $apiRequestVersion
     */
    public function setApiRequestVersion($apiRequestVersion)
    {
        $this->apiRequestVersion = $apiRequestVersion;
    }

    /**
     * Returns the current API request version
     *
     * @return string
     */
    public function getApiRequestVersion()
    {
        return $this->apiRequestVersion;
    }

    /**
     * Sets the API response version
     *
     * @param string $apiResponseVersion
     */
    public function setApiResponseVersion($apiResponseVersion)
    {
        $this->apiResponseVersion = $apiResponseVersion;
    }

    /**
     * Gets the API response version
     *
     * @return string
     */
    public function getApiResponseVersion()
    {
        return $this->apiResponseVersion;
    }

    /**
     * Sets the current API validation errors
     *
     * @param \Symfony\Component\Validator\ConstraintViolationList $apiRequestErrors List of API errors
     */
    public function setApiRequestErrors($apiRequestErrors)
    {
        $this->apiRequestErrors = $apiRequestErrors;
    }

    /**
     * Returns the current API validation errors
     *
     * @return \Symfony\Component\Validator\ConstraintViolationList
     */
    public function getApiRequestErrors()
    {
        return $this->apiRequestErrors;
    }

    /**
     * Returns the current API user
     *
     * @return \RJP\ApiBundle\Entity\User
     */
    public function getApiUser()
    {
        return $this->security->getToken()->getUser()->getUser();
    }


    /**
     * Validates an API contract request.  Returns true if valid, false if errors.
     *
     * @param Contract $request
     * @return bool
     */
    public function isValid(Contract $request = null)
    {
        if ($request === null)
        {
            $request = $this->getApiRequest();
        }

        $this->setApiRequestErrors($request->validate());

        return ($this->getApiRequestErrors()->count() ? false : true);
    }

    /**
     * This recursive function takes a Symfony ConstraintViolation and adds it to the error tree.
     *
     * @param array $tree
     * @param $reference
     * @param \Symfony\Component\Validator\ConstraintViolation $error
     */
    protected function recurseErrorsTree(array &$tree, &$reference, ConstraintViolation &$error)
    {
        if (count($tree))
        {
            $limb = array_shift($tree);

            if (is_array($reference) && !array_key_exists($limb, $reference))
            {
                $reference[$limb] = array();
            }

            return $this->recurseErrorsTree($tree, $reference[$limb], $error);
        }
        else
        {
            if (!empty($reference))
            {
                $currentError = $reference;
                $reference = array();
                $reference[] = $currentError;
                $reference[] = $error->getMessage();
            }
            else
            {
                $reference = $error->getMessage();
            }
        }
    }

    /**
     * Moves HATEOAS embedded elements back into root array
     *
     * @param array $root
     * @return array
     */
    protected function mapEmbedded(&$root)
    {
        if (is_array($root) && isset($root['_embedded']) && is_array($root['_embedded']))
        {
            foreach ($root['_embedded'] as $property => $data)
            {
                $root[$property] = $data;
            }
            $this->mapEmbedded($root['_embedded']);
        }

        return $root;
    }

    /**
     * Moves HATEOAS embedded resources after being converted to contracts back into _embedded
     *
     * @param mixed $root Object to be mapped to
     * @param mixed $embedded Current embed being processed
     */
    protected function mapEmbeddedToObject(&$root, $embedded = null)
    {
        if ($embedded === null)
        {
            if (is_object($root))
            {
                $embedded = $root->_embedded;
            }
            else if (is_array($root) && isset($root['_embedded']))
            {
                $embedded = $root['_embedded'];
            }
        }

        if ($embedded !== null)
        {
            foreach ($embedded as $property => &$data)
            {
                $rootProperty = $root->{'get'.ucfirst($property)}();

                if (isset($data['_embedded']))
                {
                    $this->mapEmbeddedToObject($rootProperty, $data['_embedded']);
                }

                if (isset($data['_links']))
                {
                    $previousLinks = $data['_links'];
                }
                if (isset($root->_embedded[$property]['_embedded']))
                {
                    $previousEmbedded = $root->_embedded[$property]['_embedded'];
                }

                $root->_embedded[$property] = $rootProperty;

                if (isset($previousLinks))
                {
                    $root->_embedded[$property]->_links = $previousLinks;
                }
                if (isset($previousEmbedded))
                {
                    $root->_embedded[$property]->_embdedded = $previousEmbedded;
                }
            }
        }
    }

    /**
     * Serialize a return object for display.  This could be either a contract payload, or constraint violations.
     *
     * @param object|array $object What to serialize
     * @param bool $merge
     * @param null $mergeClass
     * @return object
     * @throws \Exception
     */
    public function serialize($object, $merge = false, $mergeClass = null)
    {
        if ($object instanceof ConstraintViolationList)
        {
            $errors = array('errors' => array());

            foreach ($object as $error)
            {
                $errors['errors']['paths'][$error->getPropertyPath()] = $error->getMessage();
                $errorTree = explode('.', preg_replace('/\[(\d+)\]/', '.$1', $error->getPropertyPath()));
                $this->recurseErrorsTree($errorTree, $this->errorContainer, $error);
            }

            if (isset($this->errorContainer[""]))
            {
                $errors['errors']['global'] = $this->errorContainer[""];
                unset($this->errorContainer[""]);
            }

            $errors['errors']['field'] = $this->errorContainer;

            $errorsContainer = new Errors();
            $errorsContainer->setErrors($errors['errors']);

            return $errorsContainer;
        }
        else
        {
            $route = $this->request->getCurrentRequest()->attributes->get('_route');
            $annotation = $this->apiDoc->get($this->request->getCurrentRequest()->attributes->get('_controller'), $route);

            if (is_object($object) || is_array($object))
            {
                if (is_array($object))
                {
                    $object = $this->mapEmbedded($object);
                }

                $serializationContext = SerializationContext::create();
                $serializationContext->setGroups(array('Default', 'Association'));
                if ($this->getApiResponseVersion() !== null)
                {
                    $serializationContext->setVersion($this->getApiResponseVersion());
                }

                $serialized = $this->serializer->serialize($object, 'json', $serializationContext);
            }
            else
            {
                throw new \Exception('unsupported_serialization_type');
            }

            if ($annotation->getOutput())
            {
                $deserializationContext = DeserializationContext::create();
                $deserializationContext->setGroups(array('Default', 'Embedded', 'Links', 'Association'));
                if ($this->getApiResponseVersion() !== null)
                {
                    $deserializationContext->setVersion($this->getApiResponseVersion());
                }

                if ($merge)
                {
                    $output = $mergeClass ? $mergeClass : $annotation->getInput();
                }
                else
                {
                    $output = $annotation->getOutput();
                }

                $deserialized = $this->serializer->deserialize(
                    $serialized,
                    (is_array($output) ? $output['class'] : $output),
                    'json',
                    $deserializationContext
                );


                $this->mapEmbeddedToObject($deserialized);

                if ($merge && method_exists($deserialized, 'setValidator'))
                {
                    $deserialized->setValidator($this->container->get('validator'));
                }

                return $deserialized;
            }
        }
    }

    /**
     * Maps API request data to an entity
     *
     * @param object $object Object to map to
     * @param null $dataObject Object to map from
     * @throws \Exception
     */
    public function setData(&$object, &$dataObject = null)
    {
        if ($dataObject === null)
        {
            $dataObject = $this->getApiRequest();
        }

        if (is_array($dataObject))
        {
            throw new \Exception('array_not_supported');
        }

        $this->mapObject($object, $dataObject);
    }

    /**
     * Walk the array of unset values and remove them from the deserialized request object. This is done
     * by reference - no values will be returned.
     *
     * @param object $toPointer Where the values will be mapped to
     * @param object $fromPointer Where values will be mapped from
     */
    public function mapObject(&$toPointer, &$fromPointer)
    {
        $properties = $fromPointer->getProperties();
        foreach ($properties as &$property)
        {
            if (method_exists($toPointer, 'set' . ucfirst($property)) && method_exists($fromPointer, 'get'.ucfirst($property)))
            {
                $propertyValue = $fromPointer->{'get'.ucfirst($property)}();

                if (is_array($propertyValue))
                {
                    foreach ($propertyValue as $arrayKey => &$arrayValue)
                    {
                        // TODO: These need cleaned up to be more readable
                        if (method_exists($arrayValue, 'get'.ucfirst($this->getPrimaryKeyField($this->getType($toPointer, $property))))
                            && in_array($this->getPrimaryKeyField($this->getType($toPointer, $property)), $arrayValue->getProperties())
                        )
                        {
                            $value = $arrayValue->{'get'.ucfirst($this->getPrimaryKeyField($this->getType($toPointer, $property)))}();
                            if (!empty($value))
                            {
                                $record = $this->findRecord(
                                    $this->getPrimaryKeyField($this->getType($toPointer, $property)),
                                    $arrayValue->{'get'.ucfirst($this->getPrimaryKeyField($this->getType($toPointer, $property)))}(),
                                    $toPointer->{'get'.ucfirst($property)}()
                                );

                                if (!empty($record))
                                {
                                    $this->mapObject($record, $arrayValue);
                                }
                            }
                            else
                            {
                                $class = $this->getType($toPointer, $property);
                                if ($class !== null)
                                {
                                    $new = new $class();
                                    $this->mapObject($new, $arrayValue);
                                    $toPointer->{'add'.ucfirst($property)}($new);
                                }
                            }
                        }
                        else if (!in_array($this->getPrimaryKeyField($this->getType($toPointer, $property)), $arrayValue->getProperties()))
                        {
                            $class = $this->getType($toPointer, $property);
                            $new = new $class();
                            if ($class !== null)
                            {
                                $this->mapObject($new, $arrayValue);
                                $toPointer->{'add' . ucfirst($property)}($new);
                            }
                        }
                    }
                }
                else if (is_object($propertyValue) && $propertyValue instanceof Contract)
                {
                    $pointer = $toPointer->{'get'.ucfirst($property)}();
                    if ($pointer === null && method_exists($toPointer, 'set'.ucfirst($property)))
                    {
                        $class = $this->getType($toPointer, $property);
                        $pointer = new $class();
                        if ($class !== null)
                        {
                            $toPointer->{'set' . ucfirst($property)}($pointer);
                        }
                    }
                    $this->mapObject($pointer, $propertyValue);
                }
                else if (is_object($propertyValue))
                {
                    $toPointer->{'set'.ucfirst($property)}($fromPointer->{'get'.ucfirst($property)}());
                }
                else if (!is_object($propertyValue) && $property != 'validator' && method_exists($toPointer, 'set'.ucfirst($property)))
                {
                    $toPointer->{'set'.ucfirst($property)}($fromPointer->{'get'.ucfirst($property)}());
                }
            }
        }
    }

    /**
     * Loop through a collection to see if the record contains a specific one based on $id.
     *
     * @param mixed $id Id to look for
     * @param mixed $value Value to look for
     * @param mixed $collection Collection to loop through - getters are required for this to work.
     *
     * @return array|object
     */
    public function findRecord($id, $value, $collection)
    {
        foreach ($collection as $record)
        {
            if ($record->{'get'.ucfirst($id)}() == $value)
            {
                return $record;
            }
        }
    }

    /**
     * Check the metadata of a doctrine ORM object to see what type a property shouold be.
     *
     * @param object $object ORM Entity class
     * @param string $property Property to check
     *
     * @return string
     */
    public function getType($object, $property)
    {
        try
        {
            return $this->getMetadata($object)->associationMappings[$property]['targetEntity'];
        }
        catch (\Exception $e)
        {
            // Not found in doctrine
        }

        return null;
    }

    /**
     * Get doctrine metadata from an object
     *
     * @param mixed $mixed Either a doctrine enabled ORM entity, or a string
     *
     * @return object
     */
    public function getMetadata($mixed)
    {
        if ($this->doctrine !== null)
        {
            try
            {
                return $this->getDoctrine()->getManager($this->getConnection())->getClassMetadata((is_object($mixed) ? get_class($mixed) : $mixed));
            }
            catch (\Exception $e)
            {
                // Not found
            }
        }

        return null;
    }

    /**
     * Check if a field is the primary key of an entity
     *
     * @param array $metadata Metadata to use
     * @param string $field Field to look for
     *
     * @return bool
     */
    public function isPrimaryKey($metadata, $field)
    {
        if (isset($metadata['fieldMappings'][$field])
            && isset($metadata['fieldMappings'][$field]['id'])
            && $metadata['fieldMappings'][$field]['id'])
        {
            return true;
        }

        return false;
    }

    /**
     * Get the primary key field for an ORM Entity
     *
     * @param object $object Dotrine ORM entity to check
     *
     * @return bool|int|string
     */
    public function getPrimaryKeyField($object)
    {
        try
        {
            foreach ($this->getMetadata($object)->fieldMappings as $key => $data)
            {
                if (isset($data['id']) && $data['id'])
                {
                    return $key;
                }
            }
        }
        catch (\Exception $e)
        {
            // Not a doctrine object
        }

        return false;
    }
}