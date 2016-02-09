<?php

namespace RJP\ApiBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use JMS\Serializer\DeserializationContext;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Sets up a controller for the API with necessary parameters. Much of this needs moved to a service.
 *
 * Class EventListener
 * @package RJP\ApiBundle\EventListener
 */
class EventListener
{
    private $container;
    private $deserialized;
    private $api;

    public function __construct($container, \RJP\ApiBundle\Services\Api $api)
    {
        $this->container = $container;
        $this->api = $api;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        // Grab the request
        $request = $this->container->get('request');

        // Initialize the ApiDoc bundle. We're going to piggyback off of the "input" parameter
        // to use to deserialize the object that is coming in
        $apiDoc = $this->container->get('nelmio_api_doc.extractor.api_doc_extractor');
        $route = $request->attributes->get('_route');
        $annotation = $apiDoc->get($request->attributes->get('_controller'), $route);

        $controller = $event->getController();

        if (method_exists($controller[0],'setApi'))
        {
            $controller[0]->setApi($this->api);
        }

        // Check if the client requested a versioned response
        $apiResponseVersion = null;
        $header = $request->headers->get('api-accept-version');
        if (!empty($header))
        {
            $apiResponseVersion = $request->headers->get('api-accept-version');
            $this->api->setApiResponseVersion($apiResponseVersion);
        }

        // Check for a versioned content type
        $apiRequestVersion = null;
        $header = $request->headers->get('api-content-type-version');
        if (!empty($header))
        {
            $apiRequestVersion = $request->headers->get('api-content-type-version');
            $this->api->setApiRequestVersion($apiRequestVersion);
        }

        if (method_exists($controller[0],'setEm'))
        {
            $controller[0]->setEm($this->container->get('doctrine')->getManager());
        }

        // We're only gonna do it if it's a post/put/patch/etc
        if (in_array($request->getMethod(), array("POST", "PUT", "PATCH", "DELETE")))
        {
            $serializer = $this->container->get('jms_serializer');

            try
            {
                if (is_array($annotation->getInput()) && count($annotation->getInput()))
                {
                    $input = $annotation->getInput();
                    $serializerGroups = $input['groups'];
                }
                else
                {
                    $serializerGroups = array('Default');
                }

                // Setup our deserialization context
                $context = DeserializationContext::create();
                $context->setGroups($serializerGroups);
                if ($apiRequestVersion !== null)
                {
                    $context->setVersion($apiRequestVersion);
                }

                // Holder for our input annotation
                $input = $annotation->getInput();

                // See if our content is a form, if it is, we have to first make it JSON
                $content = $request->getContentType() == 'form' ? json_encode($request->request->all(), JSON_FORCE_OBJECT) : $request->getContent();

                // See if the content type is form, if so we need to pretend the content type is JSON
                $contentType = $request->getContentType() == 'form' ? 'json' : $request->getContentType();

                // Deserialize into the input class
                $deserialized = $serializer->deserialize(
                    $content,
                    (is_array($input) ? $input['class'] : $input),
                    $contentType,
                    $context
                );

                $this->deserialized = $deserialized;

                if ($contentType != "xml")
                {
                    // This time deserialize into an array
                    $deserializedArray = $serializer->deserialize(
                        ($content != '' ? $content : '{}'),
                        'array',
                        $contentType
                    );
                }
                else
                {
                    $deserializedArray = (array) simplexml_load_string($content);
                }

                $this->api->setApiRequest($deserialized);
                $this->api->setRawApiRequest($content);

                // Compare the serialized request to the data that was sent
                // We need to know what was sent vs what wasn't
                if (is_array($deserialized))
                {
                    foreach ($deserialized as $key => $value)
                    {
                        $deserialized[$key]->setValidator($this->container->get('validator'));
                        $arrayDiff[$key] = $this->arrayRecursiveDiff($deserialized[$key]->getArray(), $deserializedArray[$key]);
                    }
                }
                else
                {
                    $deserialized->setValidator($this->container->get('validator'));
                    $arrayDiff = $this->arrayRecursiveDiff($deserialized->getArray(), $deserializedArray);
                }

                $unsetCallback = function($path) use (&$deserialized)
                {
                    $pointer = $deserialized;

                    if (is_array($path) && count($path))
                    {
                        for ($i = 0; $i < count($path) - 1; $i++)
                        {
                            if (is_numeric($path[$i]))
                            {
                                $pointer = $pointer[$i];
                            }
                            else if (is_numeric($path[$i+1]))
                            {
                                $pointer = $pointer->{'get'.ucfirst($path[$i])}();
                                $pointer = $pointer[$path[$i+1]];

                                $i++;
                            }
                            else
                            {
                                $pointer = $pointer->{'get'.ucfirst($path[$i])}();
                            }
                        }

                        $pointer->unsetProperty($path[count($path) -1]);
                    }
                };

                $this->buildObjectPath($unsetCallback, $arrayDiff);
            }
            catch (\Exception $e)
            {
                throw new HttpException(400, "bad_request");
            }
        }
    }

    /*
     * Walk the array of unset values and remove them from the deserialized request object
     */
    public function buildObjectPath($callback, array $array, array $path = array())
    {
        if (count($array))
        {
            foreach ($array as $key => $value)
            {
                if (is_array($value) && count($value) && !$this->isAssociative($value))
                {
                    array_push($path, $key);

                    for ($i = 0; $i < count($value); $i++)
                    {
                        $this->buildObjectPath($callback, $value[$i], $this->arrayPush($path, $i));
                    }

                    array_pop($path);
                }
                else if (is_array($value) && count($value) && $this->isAssociative($value))
                {
                    $this->buildObjectPath($callback, $value, $this->arrayPush($path, $key));
                }
                else if (!is_array($value))
                {
                    $callback($this->arrayPush($path, $key));
                }
            }
        }
    }

    public function arrayPush($array, $element)
    {
        array_push($array, $element);
        return $array;
    }

    public function isAssociative(array $array)
    {
        return (bool)count(array_filter(array_keys($array), 'is_string'));
    }

    // Totally stolen from php.net because I didn't feel like writing another recursive function today
    // Modified to remove the value check, we only want to compare keys
    public function arrayRecursiveDiff($aArray1, $aArray2)
    {
        $aReturn = array();

        if (is_array($aArray1))
        {
            foreach ($aArray1 as $mKey => $mValue)
            {
                if (array_key_exists($mKey, $aArray2))
                {
                    if (is_array($mValue))
                    {
                        $aRecursiveDiff = $this->arrayRecursiveDiff($mValue, $aArray2[$mKey]);
                        if (count($aRecursiveDiff))
                        {
                            $aReturn[$mKey] = $aRecursiveDiff;
                        }
                    }
                }
                else
                {
                    $aReturn[$mKey] = $mValue;
                }
            }
        }

        return $aReturn;
    }

}
