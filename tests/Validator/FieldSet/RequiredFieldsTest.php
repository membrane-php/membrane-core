<?php

declare(strict_types=1);

namespace Validator\FieldSet;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\FieldSet\RequiredFields;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\FieldSet\RequiredFields
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class RequiredFieldsTest extends TestCase
{
    public function dataSetsWithIncorrectTypes(): array
    {
        return [
            [123, 'integer'],
            [1.23, 'double'],
            ['string', 'string'],
            [true, 'boolean'],
            [null, 'NULL'],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithIncorrectTypes
     */
    public function incorrectTypesReturnInvalidResults($input, $expectedVars): void
    {
        $requiredFields = new RequiredFields();
        $expected = Result::invalid(
            $input,
            new MessageSet(
                null,
                new Message('RequiredFields Validator requires an array, %s given', [$expectedVars])
            )
        );

        $result = $requiredFields->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsForValidResults(): array
    {
        return [
            'no required fields' => [
                [],
                [],
            ],
            'one required field is filled' => [
                ['required-1'],
                [
                    'required-1' => 'value-1',
                ],
            ],
            'multiple required fields are filled' => [
                ['required-1', 'required-2', 'required-3'],
                [
                    'required-1' => 'value-1',
                    'required-2' => 'value-2',
                    'required-3' => 'value-3',
                ],
            ],
            'non-required field has been filled' => [
                ['required-1', 'required-2', 'required-3'],
                [
                    'required-1' => 'value-1',
                    'required-2' => 'value-2',
                    'required-3' => 'value-3',
                    'optional-4' => 'value-4',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsForValidResults
     */
    public function ifRequiredFieldsAreFilledReturnValid(array $requiredFields, array $input): void
    {
        $expected = Result::valid($input);
        $requiredFields = new RequiredFields(...$requiredFields);

        $result = $requiredFields->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsForInvalidResults(): array
    {
        return [
            'one required field, none filled' => [
                ['required-1'],
                [],
                new Message('%s is a required field', ['required-1']),
            ],
            'one required field, none filled, one optional field filled' => [
                ['required-1'],
                ['optional-2' => 'value-2'],
                new Message('%s is a required field', ['required-1']),
            ],
            'two required fields, none filled' => [
                ['required-1', 'required-2'],
                [],
                new Message('%s is a required field', ['required-1']),
                new Message('%s is a required field', ['required-2']),
            ],
            'two required fields, one filled' => [
                ['required-1', 'required-2'],
                [
                    'required-1' => 'value-1',
                ],
                new Message('%s is a required field', ['required-2']),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsForInvalidResults
     */
    public function ifRequiredFieldsAreNotFilledReturnInvalid(
        array $requiredFields,
        array $input,
        Message ...$expectedMessages
    ): void {
        $expected = Result::invalid($input, new MessageSet(null, ...$expectedMessages));
        $requiredFields = new RequiredFields(...$requiredFields);

        $result = $requiredFields->validate($input);

        self::assertEquals($expected, $result);
    }
}
