<?php

namespace RJP\ApiBundle\Serializer;

use Hateoas\Serializer\JsonHalSerializer;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\DiExtraBundle\Annotation\Service;
use JMS\DiExtraBundle\Annotation\Tag;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use JMS\Serializer\Handler\SubscribingHandlerInterface;

/**
 * @Service("rjp.serializer.json_hal")
 */
class RJPJsonHalSerializer extends JsonHalSerializer
{
    public function getSerializer()
    {
        return \JMS\Serializer\SerializerBuilder::create()->build();
    }

    /**
     * {@inheritdoc}
     */
    public function serializeEmbeddeds(array $embeddeds, JsonSerializationVisitor $visitor, SerializationContext $context)
    {
        $serializedEmbeddeds = array();
        foreach ($embeddeds as $embedded) {
            $serializedObject = $context->accept($embedded->getData());
            if (!empty($serializedObject))
            {
                $serializedObject = $this->cast($serializedObject, 'RJP\ApiBundle\Contracts\PlayerConfiguration');
            }
            $serializedEmbeddeds[$embedded->getRel()] = $serializedObject;
        }

        $visitor->addData('_embedded', $serializedEmbeddeds);
    }

    protected function cast($object, $typeTo)
    {
        //$serializationContext->setGroups(array('Default', 'Embedded', 'Links'));

        $serializationContext = SerializationContext::create();
        $serialized = $this->getSerializer()->serialize($object, 'json', $serializationContext);

        $deserializationContext = DeserializationContext::create();
        $deserialized = $this->getSerializer()->deserialize(
            $serialized,
            $typeTo,
            'json',
            $deserializationContext
        );

        $serializationContext = SerializationContext::create();
        $serialized = $this->getSerializer()->serialize($deserialized, 'json', $serializationContext);

        $deserializationContext = DeserializationContext::create();
        $deserialized = $this->getSerializer()->deserialize(
            $serialized,
            'array',
            'json',
            $deserializationContext
        );

        return $deserialized;
    }
}
