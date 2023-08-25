<?php

declare(strict_types=1);

namespace Membrane\Console\Template;

class Processor
{
    private const TEMPLATE_CODE =
        '<?php 

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
';

    public function createFromTemplate(string $namespace, string $className, \Membrane\Processor $processor): string
    {
        return sprintf(self::TEMPLATE_CODE, $namespace, $className, $processor->__toPHP());
    }
}
