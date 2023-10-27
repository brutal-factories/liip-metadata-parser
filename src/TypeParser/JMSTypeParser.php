<?php

declare(strict_types=1);

namespace Liip\MetadataParser\TypeParser;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Type\Parser;
use Liip\MetadataParser\Exception\InvalidTypeException;
use Liip\MetadataParser\Metadata\DateTimeOptions;
use Liip\MetadataParser\Metadata\PropertyType;
use Liip\MetadataParser\Metadata\PropertyTypeClass;
use Liip\MetadataParser\Metadata\PropertyTypeDateTime;
use Liip\MetadataParser\Metadata\PropertyTypeIterable;
use Liip\MetadataParser\Metadata\PropertyTypePrimitive;
use Liip\MetadataParser\Metadata\PropertyTypeUnknown;

final class JMSTypeParser
{
    private const TYPE_ARRAY = 'array';
    private const TYPE_ARRAY_COLLECTION = 'ArrayCollection';
    private const TYPE_DATETIME_INTERFACE = 'DateTimeInterface';

    /**
     * @var Parser
     */
    private $jmsTypeParser;

    public function __construct()
    {
        $this->jmsTypeParser = new Parser();
    }

    public function parse(string $rawType): PropertyType
    {
        if ('' === $rawType) {
            return new PropertyTypeUnknown(true);
        }

        return $this->parseType($this->jmsTypeParser->parse($rawType));
    }

    private function parseType(array $typeInfo, bool $isSubType = false): PropertyType
    {
        $typeInfo = array_merge(
            [
                'name' => null,
                'params' => [],
            ],
            $typeInfo
        );

        // JMS types are nullable except if it's a sub type (part of array)
        $nullable = !$isSubType;

        if (0 === \count($typeInfo['params'])) {
            if (self::TYPE_ARRAY === $typeInfo['name']) {
                return new PropertyTypeIterable(new PropertyTypeUnknown(false), false, $nullable);
            }

            if (PropertyTypePrimitive::isTypePrimitive($typeInfo['name'])) {
                return new PropertyTypePrimitive($typeInfo['name'], $nullable);
            }
            if (PropertyTypeDateTime::isTypeDateTime($typeInfo['name'])) {
                return PropertyTypeDateTime::fromDateTimeClass($typeInfo['name'], $nullable);
            }

            return new PropertyTypeClass($typeInfo['name'], $nullable);
        }

        $collectionClass = $this->getCollectionClass($typeInfo['name']);
        if (self::TYPE_ARRAY === $typeInfo['name'] || $collectionClass) {
            if (1 === \count($typeInfo['params'])) {
                return new PropertyTypeIterable($this->parseType($typeInfo['params'][0], true), false, $nullable, $collectionClass);
            }
            if (2 === \count($typeInfo['params'])) {
                return new PropertyTypeIterable($this->parseType($typeInfo['params'][1], true), true, $nullable, $collectionClass);
            }

            throw new InvalidTypeException(sprintf('JMS property type array can\'t have more than 2 parameters (%s)', var_export($typeInfo, true)));
        }

        if (PropertyTypeDateTime::isTypeDateTime($typeInfo['name']) || (self::TYPE_DATETIME_INTERFACE === $typeInfo['name'])) {
            // the case of datetime without params is already handled above, we know we have params
            $deserializeFormats = ($typeInfo['params'][2] ?? null) ?: null;
            $deserializeFormats = is_string($deserializeFormats) ? [$deserializeFormats] : $deserializeFormats;
            // Jms uses DateTime when given DateTimeInterface, {@see \JMS\Serializer\Handler\DateHandler} in jms/serializer
            $className = ($typeInfo['name'] === self::TYPE_DATETIME_INTERFACE) ? DateTime::class : $typeInfo['name'];
            $deserializeFormats ??= [];

            return PropertyTypeDateTime::fromDateTimeClass(
                $className,
                $nullable,
                new DateTimeOptions(
                    $typeInfo['params'][0] ?: null,
                    ($typeInfo['params'][1] ?? null) ?: null,
                    is_array($deserializeFormats) ? reset($deserializeFormats) : $deserializeFormats,
                    is_array($deserializeFormats) ? $deserializeFormats : [$deserializeFormats],
                )
            );
        }

        throw new InvalidTypeException(sprintf('Unknown JMS property found (%s)', var_export($typeInfo, true)));
    }

    private function getCollectionClass(string $name): ?string
    {
        switch ($name) {
            case self::TYPE_ARRAY_COLLECTION:
                return ArrayCollection::class;
            default:
                return is_a($name, Collection::class, true) ? $name : null;
        }
    }
}
