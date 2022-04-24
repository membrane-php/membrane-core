<?php

namespace Membrane\Attribute;

use Membrane\Exception\CannotProcessProperty;
use Membrane\Processor;
use Membrane\Processor\Collection;
use Membrane\Processor\Field;
use Membrane\Processor\Fieldset;
use Membrane\Processor\ProcessorType;
use function array_map;

class Builder
{
    public function fromObject(object $object): Processor
    {
        return $this->fromClass(get_class($object));
    }

    public function fromClass(string $class, string $processes = ''): Processor
    {
        $refl = new \ReflectionClass($class);

        $processors = $this->makeBeforeAfterSets(
            $processes,
            ...$refl->getAttributes(SetFilterOrValidator::class, \ReflectionAttribute::IS_INSTANCEOF)
        );

        foreach ($refl->getProperties() as $property) {
            if (current($property->getAttributes(Ignored::class)) !== false) {
                continue;
            }

            $type = $property->getType();

            if ($type === null) {
                throw CannotProcessProperty::noTypeHint($property->getName());
            }

            if (!($type instanceof \ReflectionNamedType)) {
                throw CannotProcessProperty::compoundPropertyType($property->getName());
            }

            // @TODO Allow forcing a property processor type?
            $processorType = $this->getProcessorTypeFromPropertyType($type->getName());
            $processorTypeAttribute = current($property->getAttributes(OverrideProcessorType::class));
            if ($processorTypeAttribute !== false) {
                $processorType = $processorTypeAttribute->newInstance()->type;
            }

            $processors[] = match ($processorType) {
                ProcessorType::Field => $this->makeField($property),
                ProcessorType::Fieldset => $this->fromClass($type->getName(), $property->getName()),
                ProcessorType::Collection => $this->makeCollection($property),
            };
        }

        return new Fieldset($processes, ...$processors);
    }

    private function getProcessorTypeFromPropertyType(string $type): ProcessorType
    {
        return match ($type) {
            'string', 'int', 'bool', 'float' => ProcessorType::Field,
            \DateTime::class, \DateTimeImmutable::class, \DateTimeInterface::class => ProcessorType::Field,
            'array' => ProcessorType::Collection,
            default => ProcessorType::Fieldset
        };
    }

    private function makeField(\ReflectionProperty $property): Field
    {
        $attributes = $property->getAttributes(
            FilterOrValidator::class,
            \ReflectionAttribute::IS_INSTANCEOF
        );

        return new Field(
            $property->getName(),
            ...array_map(fn($reflectionAttribute) => $reflectionAttribute->newInstance()->class, $attributes)
        );
    }

    private function makeCollection(\ReflectionProperty $property): Processor
    {
        $subtype = (current($property->getAttributes(Subtype::class)) ?: null)
            ?->newInstance()
            ?->type;

        if ($subtype === null) {
            throw CannotProcessProperty::noSubtypeHint($property->getName());
        }

        $subProcessorType = $this->getProcessorTypeFromPropertyType($subtype);

        $processors = $this->makeBeforeAfterSets(
            $property->getName(),
            ...$property->getAttributes(
                SetFilterOrValidator::class,
                \ReflectionAttribute::IS_INSTANCEOF
            )
        );

        $processors[] = match ($subProcessorType) {
            ProcessorType::Fieldset => $this->fromClass($subtype, $property->getName()),
            ProcessorType::Field => $this->makeField($property),
            ProcessorType::Collection =>
                throw CannotProcessProperty::nestedCollection($property->getName())
        };

        return new Collection(
            $property->getName(),
            ...$processors
        );
    }

    private function makeBeforeAfterSets(string $processes, \ReflectionAttribute ...$attributes): array
    {
        $attributes = array_map(
            fn (\ReflectionAttribute $attr) => $attr->newInstance(),
            $attributes
        );
        $beforeSet = array_filter(
            $attributes,
            fn (SetFilterOrValidator $attr) => $attr->placement === Placement::BEFORE
        );
        $afterSet = array_filter(
            $attributes,
            fn (SetFilterOrValidator $attr) => $attr->placement === Placement::AFTER
        );

        $processors = [];

        if (count($beforeSet) > 0) {
             $field = new Field(
                $processes,
                ...array_map(fn(SetFilterOrValidator $attr) => $attr->class, $beforeSet)
            );

            $processors[] = new Processor\BeforeSet($field);
        }

        if (count($afterSet) > 0) {
            $field = new Field(
                $processes,
                ...array_map(fn(SetFilterOrValidator $attr) => $attr->class, $afterSet)
            );

            $processors[] = new Processor\AfterSet($field);
        }

        return $processors;
    }
}