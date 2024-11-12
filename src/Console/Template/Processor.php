<?php

declare(strict_types=1);

namespace Membrane\Console\Template;

use Atto\CodegenTools\ClassDefinition\PHPClassDefinition;
use Membrane\Processor as MembraneProcessor;

final class Processor implements PHPClassDefinition
{
    private const TEMPLATE_CODE = <<<'END'
<?php 

declare(strict_types=1);
    
namespace %s;

use Membrane;
    
class %s implements Membrane\Processor
{
    public readonly Membrane\Processor $processor;
    
    public function __construct()
    {
        $this->processor = %s;
    }

    public function processes(): string
    {
        return $this->processor->processes();
    }

    public function process(Membrane\Result\FieldName $parentFieldName, mixed $value): Membrane\Result\Result
    {
        return $this->processor->process($parentFieldName, $value);
    }

    public function __toString()
    {
        return (string)$this->processor;
    }

    public function __toPHP(): string
    {
        return $this->processor->__toPHP();
    }
}
END;

    public function __construct(
        private readonly string $namespace,
        private readonly string $name,
        private readonly MembraneProcessor $processor
    ) {
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): string
    {
        return sprintf(self::TEMPLATE_CODE, $this->namespace, $this->name, $this->processor->__toPHP());
    }
}
