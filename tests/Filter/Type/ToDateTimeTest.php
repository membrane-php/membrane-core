<?php

declare(strict_types=1);

namespace Filter\Type;

use DateTime;
use DateTimeImmutable;
use Membrane\Filter\Type\ToDateTime;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Filter\Type\ToDateTime
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class ToDateTimeTest extends TestCase
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
        $toDateTime = new ToDateTime('');
        $expected = Result::invalid($input, new MessageSet(
                null,
                new Message('ToDateTime filter requires a string, %s given', [$expectedVars])
            )
        );

        $result = $toDateTime->filter($input);

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
    public function stringsThatMatchFormatReturnImmutableDateTimes(string $format, string $input): void
    {
        $toDateTime = new ToDateTime($format);
        $expected = Result::noResult(DateTimeImmutable::createFromFormat($format, $input));

        $result = $toDateTime->filter($input);

        self::assertEqualsWithDelta($expected, $result, 2);
        self::assertTrue($result->value instanceof DateTimeImmutable);
    }

    /**
     * @test
     * @dataProvider dataSetsThatPass
     */
    public function stringsThatMatchFormatReturnDateTimesIfImmutableSetToFalse(string $format, string $input,): void
    {
        $toDateTime = new ToDateTime($format, false);
        $expected = Result::noResult(DateTime::createFromFormat($format, $input));

        $result = $toDateTime->filter($input);

        self::assertEqualsWithDelta($expected, $result, 2);
        self::assertTrue($result->value instanceof DateTime);
    }

    public function dataSetsThatFail(): array
    {
        // @TODO once min requirement is PHP 8.2: remove version_compare statements
        return [
            [
                'Y-m-d',
                '1990 June 15',
                [
                    'warning_count' => 0,
                    'warnings' => [],
                    'error_count' => 3,
                    'errors' => [
                        4 => 'Unexpected data found.',
                        12 => version_compare(PHP_VERSION, '8.1.7', '>=') ?
                            'Not enough data available to satisfy format'
                            :
                            'Data missing',
                    ],
                ],
            ],
            [
                'Y-m',
                '01-April',
                [
                    'warning_count' => 0,
                    'warnings' => [],
                    'error_count' => 2,
                    'errors' => [
                        3 => 'A two digit month could not be found',
                    ],
                ],
            ],
            [
                'Y',
                '',
                [
                    'warning_count' => 0,
                    'warnings' => [],
                    'error_count' => 1,
                    'errors' => [
                        0 => version_compare(PHP_VERSION, '8.1.7', '>=') ?
                            'Not enough data available to satisfy format'
                            :
                            'Data missing',
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatFail
     */
    public function stringsThatDoNotMatchFormatReturnInvalid(string $format, string $input, array $expectedVars): void
    {
        $toDateTime = new ToDateTime($format);
        $expectedMessage = new Message('String does not match the required format', [$expectedVars]);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $toDateTime->filter($input);

        self::assertEquals($expected, $result);
    }
}
