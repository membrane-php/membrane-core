<?php

declare(strict_types=1);

namespace Validator\String;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\String\DateString;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\String\DateString
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class DateStringTest extends TestCase
{
    public function dataSetsWithIncorrectTypes(): array
    {
        return [
            [123, 'integer'],
            [1.23, 'double'],
            [[], 'array'],
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
        $dateString = new DateString('');
        $expected = Result::invalid($input, new MessageSet(
                null,
                new Message('DateString Validator requires a string, %s given', [$expectedVars])
            )
        );

        $result = $dateString->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsThatPass(): array
    {
        return [
            ['', ''],
            ['Y-m-d', '1970-01-01'],
            ['d-M-y', '20-feb-22'],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatPass
     */
    public function stringsThatMatchFormatReturnValid(string $format, string $input): void
    {
        $dateString = new DateString($format);
        $expected = Result::valid($input);

        $result = $dateString->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsThatFail(): array
    {
        return [
            ['Y-m-d', '1990 June 15'],
            ['Y-m', '01-April'],
            ['Y', ''],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatFail
     */
    public function stringsThatDoNotMatchFormatReturnInvalid(string $format, string $input): void
    {
        $dateString = new DateString($format);
        $expectedMessage = new Message('String does not match the required format %s', [$format]);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $dateString->validate($input);

        self::assertEquals($expected, $result);
    }
}
