<?php

declare(strict_types=1);

namespace Membrane\Result;

class FieldName
{
    /** @var array|string[] */
    public readonly array $stack;

    public function __construct(
        public readonly string $fieldname,
        string ...$stack
    ) {
        $this->stack = $stack;
    }

    public function push(FieldName $fieldname): self
    {
        return new self(
            $fieldname->fieldname,
            ...[...$this->stack, $this->fieldname]
        );
    }

    public function mergable(?FieldName $other): bool
    {
        if ($other === null) {
            return true;
        }

        return $this->equals($other);
    }

    public function equals(FieldName $other): bool
    {
        return $this->getStringRepresentation() === $other->getStringRepresentation();
    }

    public function getStringRepresentation(): string
    {
        $nonEmptyFieldNames = array_filter([...$this->stack, $this->fieldname], fn($fieldName) => $fieldName !== '');
        return implode('->', $nonEmptyFieldNames);
    }
}
