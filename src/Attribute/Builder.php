<?php

declare(strict_types=1);

namespace Membrane\Attribute;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Membrane\Builder\Builder as BuilderInterface;
use Membrane\Builder\Specification;
use Membrane\Exception\CannotProcessProperty;
use Membrane\Filter;
use Membrane\Processor;
use Membrane\Processor\AfterSet;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Collection;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
use Membrane\Processor\ProcessorType;
use Membrane\Validator;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;

use function array_map;

class Builder implements BuilderInterface
{
    public function supports(Specification $specification): bool
    {
        return ($specification instanceof ClassWithAttributes);
    }

    public function build(Specification $specification): Processor
    {
        assert($specification instanceof ClassWithAttributes);

        return $this->fromClass($specification->className);
    }

    private function fromClass(string $class, string $processes = ''): Processor
    {
        assert(class_exists($class));

        $refl = new ReflectionClass($class);

        $processors = $this->makeBeforeAfterSets(
            ...$refl->getAttributes(SetFilterOrValidator::class, ReflectionAttribute::IS_INSTANCEOF)
        );

        foreach ($refl->getProperties() as $property) {
            if (current($property->getAttributes(Ignored::class)) !== false) {
                continue;
            }

            $processors[] = $this->getProcessorFromProperty($property);
        }

        return new FieldSet($processes, ...$processors);
    }

    private function getProcessorFromProperty(ReflectionProperty $property): Processor
    {
        $type = $property->getType();

        if ($type === null) {
            throw CannotProcessProperty::noTypeHint($property->getName());
        }

        if ($type instanceof \ReflectionIntersectionType) {
            throw CannotProcessProperty::intersectionTypeHint($property->getName());
        }

        if ($type instanceof \ReflectionUnionType) {
            $processors = [];

            foreach ($type->getTypes() as $subType) {
                if ($subType instanceof \ReflectionIntersectionType) {
                    throw CannotProcessProperty::intersectionTypeHint($property->getName());
                }

                if (!in_array($subType->getName(), ['bool', 'float', 'int', 'string', 'null', 'true', 'false'])) {
                    throw CannotProcessProperty::compoundPropertyType($property->getName());
                }

                $processors [] = $this->makeField($property->getName(), ...$this
                    ->getFiltersOrValidators($property, $subType->getName()));
            }

            // AnyOf is faster than OneOf since it performs less checks
            return new Processor\AnyOf($property->getName(), ...$processors);
        }

        assert($type instanceof \ReflectionNamedType); // proof by exhaustion (all alternatives have been checked above)

        $processorType = $this->getProcessorTypeFromPropertyType($type->getName());
        $processorTypeAttribute = current($property->getAttributes(OverrideProcessorType::class));
        if ($processorTypeAttribute !== false) {
            $processorType = $processorTypeAttribute->newInstance()->type;
        }

        return match ($processorType) {
            ProcessorType::Field => $this->makeField($property->getName(), ...$this
                ->getFiltersOrValidators($property, $type->getName())),
            ProcessorType::Fieldset => $this->fromClass($type->getName(), $property->getName()),
            ProcessorType::Collection => $this->makeCollection($property),
        };
    }

    /** @return array<Filter|Validator> */
    private function getFiltersOrValidators(
        ReflectionProperty $property,
        string $type
    ): array {
        $reflectionAttributes = $property->getAttributes();

        $result = [];
        foreach ($reflectionAttributes as $reflectionAttribute) {
            $attribute = $reflectionAttribute->newInstance();

            switch (true) {
                case $attribute instanceof When:
                    if ($attribute->typeIs === $type) {
                        $result [] = $attribute->filterOrValidator->class;
                    }
                    break;
                case $attribute instanceof FilterOrValidator:
                    $result [] = $attribute->class;
                    break;
            }
        }

        return $result;
    }

    private function getProcessorTypeFromPropertyType(string $type): ProcessorType
    {
        if (
            enum_exists($type)
            && (in_array(\BackedEnum::class, class_implements($type)))
        ) {
            return ProcessorType::Field;
        }

        return match ($type) {
            'string', 'int', 'bool', 'float' => ProcessorType::Field,
            DateTime::class, DateTimeImmutable::class, DateTimeInterface::class => ProcessorType::Field,
            'array' => ProcessorType::Collection,
            default => ProcessorType::Fieldset
        };
    }

    private function makeField(
        string $propertyName,
        Filter|Validator ...$filtersOrValidators
    ): Field {
        return new Field($propertyName, ...$filtersOrValidators);
    }

    private function makeCollection(ReflectionProperty $property): Processor
    {
        $subtype = current($property->getAttributes(Subtype::class));
        if ($subtype === false) {
            throw CannotProcessProperty::noSubtypeHint($property->getName());
        }
        $subtype = $subtype->newInstance()->type;

        $subProcessorType = $this->getProcessorTypeFromPropertyType($subtype);

        $processors = $this->makeBeforeAfterSets(
            ...$property->getAttributes(
                SetFilterOrValidator::class,
                ReflectionAttribute::IS_INSTANCEOF
            )
        );

        $processors[] = match ($subProcessorType) {
            ProcessorType::Fieldset => $this->fromClass($subtype, $property->getName()),
            ProcessorType::Field => $this->makeField($property->getName(), ...$this
                ->getFiltersOrValidators($property, $subtype)),
            ProcessorType::Collection =>
                throw CannotProcessProperty::nestedCollection($property->getName())
        };

        return new Collection(
            $property->getName(),
            ...$processors
        );
    }

    /**
     * @param ReflectionAttribute<SetFilterOrValidator> ...$attributes
     * @return Processor[]
     */
    private function makeBeforeAfterSets(ReflectionAttribute ...$attributes): array
    {
        $attributes = array_map(
            fn(ReflectionAttribute $attr) => $attr->newInstance(),
            $attributes
        );
        $beforeSet = array_filter(
            $attributes,
            fn(SetFilterOrValidator $attr) => $attr->placement === Placement::BEFORE
        );
        $afterSet = array_filter(
            $attributes,
            fn(SetFilterOrValidator $attr) => $attr->placement === Placement::AFTER
        );

        $processors = [];

        if (count($beforeSet) > 0) {
            $processors[] = new BeforeSet(...array_map(fn(SetFilterOrValidator $attr) => $attr->class, $beforeSet));
        }

        if (count($afterSet) > 0) {
            $processors[] = new AfterSet(...array_map(fn(SetFilterOrValidator $attr) => $attr->class, $afterSet));
        }

        return $processors;
    }
}
