<?php

declare(strict_types=1);

namespace Inc\Entity;

use DomainException;
use ReflectionNamedType;
use ReflectionProperty;
use stdClass;
use TypeError;

abstract class AbstractEntity
{
    public static function fromArray(array $array): self
    {
        if (static::class == self::class) {
            throw new DomainException('AbstractEntity can\'t be maked');
        }

        $entity = new static();

        foreach (array_keys(get_class_vars(get_class($entity))) as $field) {
            $entity->setValue($field, $array[$field] ?? null, $array);
        }

        return $entity;
    }

    /**
     * @param object $object
     * @return static
     */
    public static function fromObject(object $object): self
    {
        if (static::class == self::class) {
            throw new DomainException('AbstractEntity can\'t be maked');
        }

        $entity  = new static();

        foreach (array_keys(get_class_vars(get_class($entity))) as $field) {
            $entity->setValue($field, $object->$field ?? null, $object);
        }

        return $entity;
    }

    protected function mapping(): array
    {
        return [];
    }

    private function map(string $field)
    {
        return $this->mapping()[$field] ?? null;
    }

    private function setValue(string $field, $value, $origin): void
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $property = new ReflectionProperty(static::class, $field);

        if ($mapValue = $this->map($field)) {
            if (is_callable($mapValue)) {
                $this->$field = $mapValue($value, $origin);
                return;
            } elseif (is_string($mapValue)) {
                if(class_exists($mapValue) and $value) {
                    $this->$field = $this->genereateFromClass($mapValue, $value);
                    return;
                } else {
//                    $entityField = $mapValue;
                }
            }
        }

        $this->$field =
            $this->typeOf($value) == $property->getType()->getName()
                ? $value
                : $this->$field = $this->defaultType($property->getType());
    }

    private function genereateFromClass($className, $value)
    {
        $array3d = is_array($value) && (is_array(current($value)) || is_object(current($value)));

        if($array3d) {
            return array_map(
                function($item) use ($className) {
                    return $this->genereateFromClass($className, $item);
                },
                $value
            );
        }

        if(is_array($value)) {
            return $className::fromArray($value);
        }

        /** @var self $className */
        return $className::fromObject($value);
    }

    private function defaultType(ReflectionNamedType $reflectionType)
    {
        if($reflectionType->allowsNull()) {
            return null;
        }

        $type = $reflectionType->getName();

        switch($type) {
            case 'bool':
                return false;
            case 'int':
            case 'float':
                return 0;
            case 'string':
                return '';
            case 'object':
                return new stdClass();
            case 'array':
                return [];
            default:
                if ($type) {
                    /** @var self $type */
                    return $type::fromArray([]);
                } else {
                    throw new TypeError('Invalid property type: ' . $type);
                }
        }
    }

    private function typeOf($value): string
    {
        return str_replace('eger', '', gettype($value));
    }
}