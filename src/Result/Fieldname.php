<?php

declare(strict_types=1);

namespace Membrane\Result;

class Fieldname
{
    public readonly array $stack;

    public function __construct(
        public readonly string $fieldname,
        string ...$stack
    ) {
        $this->stack = $stack;
    }

    public function push(Fieldname $fieldname): self
    {
        return new self(
            $fieldname->fieldname,
            ...[...$this->stack, $this->fieldname]
        );
    }

    public function mergable(?Fieldname $other): bool
    {
        if ($other === null) {
            return true;
        }

        return $this->equals($other);
    }

    public function equals(Fieldname $other): bool
    {
        return $this->getStringRepresentation() === $other->getStringRepresentation();
    }

    public function getStringRepresentation(): string
    {
        return implode('->', [...$this->stack, $this->fieldname]);
    }
}