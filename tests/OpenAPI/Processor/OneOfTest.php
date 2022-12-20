<?php

declare(strict_types=1);

namespace OpenAPI\Processor;

use Membrane\Exception\InvalidProcessorArguments;
use Membrane\OpenAPI\Processor\OneOf;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\Type\IsArray;
use Membrane\Validator\Type\IsInt;
use Membrane\Validator\Type\IsString;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Indifferent;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Processor\OneOf
 * @covers \Membrane\Exception\InvalidProcessorArguments
 * @uses   \Membrane\Processor\BeforeSet
 * @uses   \Membrane\Processor\Field
 * @uses   \Membrane\Processor\FieldSet
 * @uses   \Membrane\Result\FieldName
 * @uses   \Membrane\Result\Message
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Validator\FieldSet\RequiredFields
 * @uses   \Membrane\Validator\Type\IsArray
 * @uses   \Membrane\Validator\Type\IsInt
 * @uses   \Membrane\Validator\Type\IsString
 * @uses   \Membrane\Validator\Utility\Fails
 * @uses   \Membrane\Validator\Utility\Indifferent
 * @uses   \Membrane\Validator\Utility\Passes
 */
class OneOfTest extends TestCase
{
    /** @test */
    public function throwsExceptionIfLessThanTwoProcessors(): void
    {
        self::expectExceptionObject(InvalidProcessorArguments::redundantProcessor(OneOf::class));

        new OneOf('');
    }

    /** @test */
    public function processesTest(): void
    {
        $processes = 'test';
        $sut = new OneOf($processes, new Field(''), new Field(''));

        self::assertEquals($processes, $sut->processes());
    }

    public function dataSetsToProcess(): array
    {
        return [
            'two Fields with valid results' => [
                '',
                [new Field('', new Passes()), new Field('', new Passes())],
                new FieldName(''),
                5,
                Result::invalid(5),
            ],
            'two Fields with invalid results' => [
                '',
                [new Field('', new Fails()), new Field('', new Fails())],
                new FieldName(''),
                5,
                Result::invalid(
                    5,
                    new MessageSet(
                        new FieldName('', ''),
                        new Message('I always fail', [])
                    ),
                    new MessageSet(
                        new FieldName('', ''),
                        new Message('I always fail', [])
                    ),
                ),
            ],
            'two Fields with no results' => [
                '',
                [new Field('', new Indifferent()), new Field('', new Indifferent())],
                new FieldName(''),
                5,
                Result::noResult(5),
            ],
            'one valid result, one invalid result' => [
                '',
                [new Field('', new Fails()), new Field('', new Passes())],
                new FieldName(''),
                5,
                Result::valid(5),
            ],
            'one valid result, one no result' => [
                '',
                [new Field('', new Indifferent()), new Field('', new Passes())],
                new FieldName(''),
                5,
                Result::valid(5),
            ],
            'expects an object which may have integer id and string name (valid input)' => [
                '',
                [
                    new FieldSet(
                        '',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray())
                    ),
                    new FieldSet(
                        '',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray())
                    ),
                ],
                new FieldName(''),
                ['id' => 'Harley', 'name' => 'Ben'],
                Result::valid(['id' => 'Harley', 'name' => 'Ben']),
            ],
            'expects an object which may have integer id and string name (invalid input)' => [
                '',
                [
                    new FieldSet(
                        '',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray())
                    ),
                    new FieldSet(
                        '',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray())
                    ),
                ],
                new FieldName(''),
                [],
                Result::invalid([]),

            ],
            'expects an object which must have integer id and string name (valid input)' => [
                '',
                [
                    new FieldSet(
                        '',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray(), new RequiredFields('id'))
                    ),
                    new FieldSet(
                        '',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    ),
                ],
                new FieldName(''),
                ['id' => 'Blink', 'name' => 'Harley'],
                Result::valid(['id' => 'Blink', 'name' => 'Harley']),
            ],
            'expects an object which must have integer id and string name (invalid input)' => [
                '',
                [
                    new FieldSet(
                        '',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray(), new RequiredFields('id'))
                    ),
                    new FieldSet(
                        '',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    ),
                ],
                new FieldName(''),
                ['id' => 5, 'name' => 'Harley'],
                Result::invalid(['id' => 5, 'name' => 'Harley']),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToProcess
     */
    public function processTest(
        string $processes,
        array $processors,
        FieldName $fieldName,
        mixed $value,
        Result $expected
    ): void {
        $sut = new OneOf($processes, ...$processors);

        $actual = $sut->process($fieldName, $value);

        self::assertEquals($expected, $actual);
    }
}
