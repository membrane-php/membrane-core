<?php

declare(strict_types=1);

namespace Validator\FieldSet;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\FieldSet\FixedFields;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\FieldSet\FixedFields
 * @uses   \Membrane\Result\Message
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Result
 */
class FixedFieldsTest extends TestCase
{
    public function dataSetsToConvertToString(): array
    {
        return [
            'no fixed fields' => [
                [],
                'does not contain any fields',
            ],
            'single fixed field' => [
                ['a'],
                'only contains the following fields: "a"',
            ],
            'multiple fixed fields' => [
                ['a', 'b', 'c'],
                'only contains the following fields: "a", "b", "c"',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToConvertToString
     */
    public function toStringTest(array $fields, string $expected): void
    {
        $sut = new FixedFields(...$fields);

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    public function dataSetsToConvertToPHPString(): array
    {
        return [
            'no fields' => [new FixedFields()],
            '1 field' => [new FixedFields('a')],
            '3 fields' => [new FixedFields('a', 'b', 'c')],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToConvertToPHPString
     */
    public function toPHPTest(FixedFields $sut): void
    {
        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

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
    public function incorrectTypesReturnInvalidResults($input, $inputType): void
    {
        $expected = Result::invalid(
            $input,
            new MessageSet(
                null,
                new Message('FixedFields Validator requires an array, %s given', [$inputType])
            )
        );
        $sut = new FixedFields();

        $result = $sut->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsToValidate(): array
    {
        return [
            'no fixed fields, no input' => [
                [],
                [],
                Result::valid([]),
            ],
            'no fixed fields, additional input' => [
                [],
                ['a' => 1],
                Result::invalid(
                    ['a' => 1],
                    new MessageSet(null, new Message('%s is not a fixed field', ['a']))
                ),
            ],
            'fixed fields, no input' => [
                ['a', 'b', 'c'],
                [],
                Result::valid([]),
            ],
            'fixed fields, input for some fixed fields' => [
                ['a', 'b', 'c'],
                ['a' => 1, 'b' => 2],
                Result::valid(['a' => 1, 'b' => 2]),
            ],
            'fixed fields, input for all fixed fields' => [
                ['a', 'b', 'c'],
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::valid(['a' => 1, 'b' => 2, 'c' => 3]),
            ],
            'fixed fields, additional input only' => [
                ['a', 'b', 'c'],
                ['d' => 4, 'e' => 5],
                Result::invalid(['d' => 4, 'e' => 5],
                    new MessageSet(
                        null,
                        new Message('%s is not a fixed field', ['d']),
                        new Message('%s is not a fixed field', ['e'])
                    )),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToValidate
     */
    public function validateTest(array $fields, array $input, Result $expected): void
    {
        $sut = new FixedFields(...$fields);

        $actual = $sut->validate($input);

        self::assertEquals($expected, $actual);
    }
}
