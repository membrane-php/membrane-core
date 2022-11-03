<?php

declare(strict_types=1);

namespace OpenAPI\Processor;

use Membrane\OpenAPI\Processor\Discriminator;
use Membrane\Processor;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Processor\Discriminator
 * @uses   \Membrane\Result\FieldName
 * @uses   \Membrane\Result\Message
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Result
 */
class DiscriminatorTest extends TestCase
{
    public function dataSetsToMatch(): array
    {
        return [
            ['PetType', 'Cat', ['HatType' => 'Fedora'], false],
            ['PetType', 'Cat', ['PetType' => 'Dog'], false],
            ['PetType', 'Cat', ['PetType' => 'Cat'], true],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToMatch
     */
    public function matchesTest(string $propertyName, string $propertyValue, array $value, bool $expected): void
    {
        $sut = new Discriminator($propertyName, $propertyValue, self::createStub(Processor::class));

        self::assertSame($expected, $sut->matches($value));
    }

    /** @test */
    public function processesTest(): void
    {
        $processor = self::createStub(Processor::class);
        $processor->method('processes')->willReturn('foo');
        $sut = new Discriminator('PetType', 'Cat', $processor);

        self::assertSame('foo', $sut->processes());
    }

    public function dataSetsToProcess(): array
    {
        return [
            [
                'PetType',
                'Cat',
                ['HatType' => 'Fedora'],
                false,
                Result::invalid(
                    ['HatType' => 'Fedora'],
                    new MessageSet(new FieldName(''), new Message('%s is expected to match %s', ['PetType', 'Cat']))
                ),
            ],
            [
                'PetType',
                'Cat',
                ['PetType' => 'Degu'],
                false,
                Result::invalid(
                    ['PetType' => 'Degu'],
                    new MessageSet(new FieldName(''), new Message('%s is expected to match %s', ['PetType', 'Cat']))
                ),
            ],
            ['PetType', 'Cat', ['PetType' => 'Cat'], true, Result::valid(['PetType' => 'Cat'])],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToProcess
     */
    public function processTest(
        string $propertyName,
        string $propertyValue,
        array $value,
        bool $expectedToMatch,
        Result $expected
    ): void {
        $processor = self::createStub(Processor::class);
        $processor->method('process')->willReturn(Result::valid($value));
        $sut = new Discriminator($propertyName, $propertyValue, $processor);

        self::assertSame($expectedToMatch, $sut->matches($value));
        self::assertEquals($expected, $sut->process(new FieldName(''), $value));
    }
}
