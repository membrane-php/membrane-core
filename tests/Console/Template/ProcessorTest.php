<?php

declare(strict_types=1);

namespace Membrane\Tests\Console\Template;

use Membrane\Console\Template;
use Membrane\Processor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Template\Processor::class)]
#[UsesClass(Processor\Field::class)]
class ProcessorTest extends TestCase
{
    public static function provideCasesToCreateFromTemplate(): array
    {
        return [
            'minimum viable input' => [
                'Membrane\\Cache',
                'ProcessorA',
                new Processor\Field('a'),
            ],
        ];
    }

    #[Test, TestDox('createFromTemplate will return a string of PHP code that can evaluate to a Processor')]
    #[DataProvider('provideCasesToCreateFromTemplate')]
    public function createFromTemplateReturnsPHPString(string $nameSpace, string $className, Processor $processor): void
    {
        $sut = new Template\Processor($nameSpace, $className, $processor);

        $phpString = $sut->getCode();
        eval('//' . $phpString);

        $createdProcessor = eval(sprintf('return new %s\\%s();', $nameSpace, $className));

        self::assertEquals($processor, $createdProcessor->processor);
    }
}
