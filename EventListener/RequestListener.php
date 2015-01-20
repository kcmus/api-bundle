<?php

namespace RJP\ApiBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;

class RequestListener
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $this->container->get("request");

        if (preg_match('/application\/vnd.rjp-v((\d+\\.)?(\d+\\.)?(\\*|\d+))\+(\w+)/', $request->headers->get('Accept'), $matches))
        {
            $request->headers->set('Accept', 'application/'.$matches[count($matches) - 1]);
            $request->headers->set('api-accept-version', $matches[1]);
        }

        if (preg_match('/application\/vnd.rjp-v((\d+\\.)?(\d+\\.)?(\\*|\d+))\+(\w+)/', $request->headers->get('Content-Type'), $matches))
        {
            $request->headers->set('Content-Type', 'application/'.$matches[count($matches) - 1]);
            $request->headers->set('api-content-type-version', $matches[1]);
        }
    }
}