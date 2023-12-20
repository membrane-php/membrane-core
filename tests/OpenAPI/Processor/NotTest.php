<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Processor;

use Generator;
use Membrane\OpenAPI\Processor\Not;
use Membrane\Processor;
use Membrane\Result\FieldName;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Indifferent;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Not::class)]
#[UsesClass(Fails::class)]
#[UsesClass(Indifferent::class)]
#[UsesClass(Passes::class)]
class NotTest extends TestCase
{
    public static function provideProcessorsToInvert(): Generator
    {
        yield 'field that passes' => [
            new Processor\Field('passes', new Passes()),
            'test value',
        ];

        yield 'field that fails' => [
            new Processor\Field('fails', new Fails()),
            'test value',
        ];

        yield 'field that is indifferent' => [
            new Processor\Field('indifferent', new Indifferent()),
            'test value',
        ];
    }

    #[Test, DataProvider('provideProcessorsToInvert')]
    public function itInvertsProcessorResults(Processor $processor, mixed $value): void
    {
        $result = $processor->process(new FieldName('normal'), $value);

        $sut = new Not('sut', $processor);

        $invertedResult = $sut->process(new FieldName('inverted'), $value);

        self::assertNotEquals($result->isValid(), $invertedResult->isValid());
    }
}
