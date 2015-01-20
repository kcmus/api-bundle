<?php

namespace RJP\ApiBundle\EventListener;

use RJP\ApiBundle\Entity\RJPEntity;
use JMS\DiExtraBundle\Annotation\Service;
use JMS\DiExtraBundle\Annotation\Tag;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use Hateoas\Factory\EmbeddedsFactory;
use Hateoas\Factory\LinksFactory;
use Hateoas\Serializer\JsonSerializerInterface;
use Hateoas\Serializer\Metadata\InlineDeferrer;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;

/**
 * @Service("rjp.event_subscriber.serialize")
 * @Tag("jms_serializer.event_subscriber")
 */
class SerializationListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            array(
                'event' => Events::PRE_SERIALIZE,
                'method' => 'onPreSerialize',
            )
        );
    }

    public function onPreSerialize(PreSerializeEvent $event)
    {
        if (method_exists($event->getObject(), 'mapContractData'))
        {
            $event->getObject()->mapContractData();
        }
    }
}
