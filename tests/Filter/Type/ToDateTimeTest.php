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
 * @covers ToDateTime
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class ToDateTimeTest extends TestCase
{
    public function DataSetsThatPass(): array
    {
        return [
            ['', ''],
            ['Y-m-d', '1970-01-01'],
            ['d-M-y', '20-feb-22'],
        ];
    }

    /**
     * @test
     * @dataProvider DataSetsThatPass
     */
    public function StringsThatMatchFormatReturnImmutableDateTimes(string $format, string $input): void
    {
        $toDateTime = new ToDateTime($format);
        $expected = Result::noResult(DateTimeImmutable::createFromFormat($format, $input));

        $result = $toDateTime->filter($input);

        self::assertEqualsWithDelta($expected, $result, 2);
        self::assertTrue($result->value instanceof DateTimeImmutable);
    }

    /**
     * @test
     * @dataProvider DataSetsThatPass
     */
    public function StringsThatMatchFormatReturnDateTimesIfImmutableSetToFalse(string $format, string $input,): void
    {
        $toDateTime = new ToDateTime($format, false);
        $expected = Result::noResult(DateTime::createFromFormat($format, $input));

        $result = $toDateTime->filter($input);

        self::assertEqualsWithDelta($expected, $result, 2);
        self::assertTrue($result->value instanceof DateTime);
    }

    public function DataSetsThatFail(): array
    {
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
                        12 => 'Not enough data available to satisfy format'
                    ]
                ]
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
                    ]
                ]
            ],
            [
                'Y',
                '',
                [
                    'warning_count' => 0,
                    'warnings' => [],
                    'error_count' => 1,
                    'errors' => [
                        0 => 'Not enough data available to satisfy format',
                    ]
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider DataSetsThatFail
     */
    public function StringsThatDoNotMatchFormatReturnInvalid(string $format, string $input, array $expectedVars): void
    {
        $toDateTime = new ToDateTime($format);
        $expectedMessage = new Message('String does not match the required format', [$expectedVars]);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $toDateTime->filter($input);

        self::assertEquals($expected, $result);
    }
}
