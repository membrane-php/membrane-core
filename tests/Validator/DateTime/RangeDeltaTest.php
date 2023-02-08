<?php

declare(strict_types=1);

namespace Validator\DateTime;

use DateInterval;
use DateTime;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\DateTime\RangeDelta;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\DateTime\RangeDelta
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class RangeDeltaTest extends TestCase
{
    public function dataSetsToConvertToString(): array
    {
        return [
            'no minimum or maximum' => [
                null,
                null,
                'will return valid',
            ],
            'minimum' => [
                new DateInterval('P1Y'),
                null,
                'is after %s, %d %s %d %d:%d:%d',
            ],
            'maximum' => [
                null,
                new DateInterval('P1Y2M3D'),
                'is before %s, %d %s %d %d:%d:%d',
            ],
            'minimum and maximum' => [
                new DateInterval('P1Y'),
                new DateInterval('P1Y2M3D'),
                'is after %s, %d %s %d %d:%d:%d and before %s, %d %s %d %d:%d:%d',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToConvertToString
     */
    public function toStringTest(?DateInterval $min, ?DateInterval $max, string $expected): void
    {
        $sut = new RangeDelta($min, $max);

        $actual = $sut->__toString();

        self::assertStringMatchesFormat($expected, $actual);
    }

    /**
     * @test
     */
    public function noMinAndMaxReturnsValid(): void
    {
        $input = new DateTime('1970-01-01 00:00:00 UTC');
        $expected = Result::valid($input);
        $range = new RangeDelta();

        $result = $range->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsWithDatesEarlierThanMin(): array
    {
        $now = new DateTime();

        return [
            [$now->sub(new DateInterval('P15Y')), new DateInterval('P10Y')],
            [$now->sub(new DateInterval('P3D')), new DateInterval('P2D')],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithDatesEarlierThanMin
     */
    public function datesEarlierThanMinReturnInvalid(DateTime $input, DateInterval $min): void
    {
        $now = new DateTime();
        $expectedMessage = new Message('DateTime is expected to be after %s', [$now->sub($min)]);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $range = new RangeDelta($min);

        $result = $range->validate($input);

        self::assertEqualsWithDelta($expected, $result, 2);
    }

    public function dataSetsWithDatesLaterThanMax(): array
    {
        $now = new DateTime();

        return [
            [$now->add(new DateInterval('P5Y1M')), new DateInterval('P5Y')],
            [$now->add(new DateInterval('P1M4D')), new DateInterval('P1M3D')],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithDatesLaterThanMax
     */
    public function datesLaterThanMaxReturnInvalid(DateTime $input, DateInterval $max): void
    {
        $now = new DateTime();
        $expectedMessage = new Message('DateTime is expected to be before %s', [$now->add($max)]);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $range = new RangeDelta(null, $max);

        $result = $range->validate($input);

        self::assertEqualsWithDelta($expected, $result, 2);
    }

    public function dataSetsWithDatesWithinRange(): array
    {
        $now = new DateTime();

        return [
            [$now, new DateInterval('P1Y'), new DateInterval('P1Y')],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithDatesWithinRange
     */
    public function datesWithinRangeReturnValid(DateTime $input, DateInterval $min, DateInterval $max): void
    {
        $expected = Result::valid($input);
        $range = new RangeDelta($min, $max);

        $result = $range->validate($input);

        self::assertEquals($expected, $result);
    }
}
