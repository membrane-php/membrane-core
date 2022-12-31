<?php

declare(strict_types=1);

namespace OpenAPI\Processor;

use Membrane\Exception\InvalidProcessorArguments;
use Membrane\OpenAPI\Processor\AllOf;
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
 * @covers \Membrane\OpenAPI\Processor\AllOf
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
class AllOfTest extends TestCase
{
    /** @test */
    public function throwsExceptionIfLessThanTwoProcessors(): void
    {
        self::expectExceptionObject(InvalidProcessorArguments::redundantProcessor(AllOf::class));

        new AllOf('');
    }

    /** @test */
    public function processesTest(): void
    {
        $processes = 'test';
        $sut = new AllOf($processes, new Field(''), new Field(''));

        self::assertEquals($processes, $sut->processes());
    }

    public function dataSetsToProcess(): array
    {
        return [
            'two valid results' => [
                '',
                [new Field('', new Passes()), new Field('', new Passes())],
                new FieldName(''),
                5,
                Result::valid(5),
            ],
            'two invalid results' => [
                '',
                [new Field('', new Fails()), new Field('', new Fails())],
                new FieldName(''),
                5,
                Result::invalid(
                    5,
                    new MessageSet(new FieldName('', ''), new Message('I always fail', [])),
                    new MessageSet(new FieldName('', ''), new Message('I always fail', []))
                ),
            ],
            'two no results' => [
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
                Result::invalid(
                    5,
                    new MessageSet(new FieldName('', ''), new Message('I always fail', [])),
                ),
            ],
            'one valid result, one no result' => [
                '',
                [new Field('', new Indifferent()), new Field('', new Passes())],
                new FieldName(''),
                5,
                Result::valid(5),
            ],
            'one invalid result, one no result' => [
                '',
                [new Field('', new Fails()), new Field('', new Indifferent())],
                new FieldName(''),
                5,
                Result::invalid(
                    5,
                    new MessageSet(new FieldName('', ''), new Message('I always fail', [])),
                ),
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
                ['id' => 5],
                Result::valid(['id' => 5]),
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
                ['id' => '5', 'name' => 'Harley'],
                Result::invalid(['id' => '5', 'name' => 'Harley'],
                    new MessageSet(
                        new FieldName('id', '', ''),
                        new Message('IsInt validator expects integer value, %s passed instead', ['string'])
                    )),
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
                ['id' => 5, 'name' => 'Harley'],
                Result::valid(['id' => 5, 'name' => 'Harley']),
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
                ['id' => 5],
                Result::invalid(['id' => 5],
                    new MessageSet(new FieldName('', '', ''), new Message('%s is a required field', ['name']))),
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
        $sut = new AllOf($processes, ...$processors);

        $actual = $sut->process($fieldName, $value);

        self::assertEquals($expected, $actual);
    }

}
