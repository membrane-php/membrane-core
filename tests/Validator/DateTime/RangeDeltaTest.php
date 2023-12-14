<?php

declare(strict_types=1);

namespace Membrane\Tests\Validator\DateTime;

use DateInterval;
use DateTime;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\DateTime\RangeDelta;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RangeDelta::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class RangeDeltaTest extends TestCase
{
    public static function dataSetsToConvertToString(): array
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

    #[DataProvider('dataSetsToConvertToString')]
    #[Test]
    public function toStringTest(?DateInterval $min, ?DateInterval $max, string $expected): void
    {
        $sut = new RangeDelta($min, $max);

        $actual = $sut->__toString();

        self::assertStringMatchesFormat($expected, $actual);
    }

    public static function dataSetsToConvertToPHPString(): array
    {
        return [
            'no minimum, no maximum' => [null, null,],
            'minimum, no maximum' => [new DateInterval('P1Y2M3DT4H5M6S'), null],
            'maximum, no minimum' => [null, new DateInterval('P9Y8M7DT6H5M4S')],
            'minimum and maximum' => [new DateInterval('P1Y2M3DT4H5M6S'), new DateInterval('P9Y8M7DT6H5M4S')],
        ];
    }

    #[DataProvider('dataSetsToConvertToPHPString')]
    #[Test]
    public function toPHPTest(?DateInterval $min, ?DateInterval $max): void
    {
        $sut = new RangeDelta($min, $max);

        $actual = $sut->__toPHP();

        self::assertEqualsWithDelta($sut, eval('return ' . $actual . ';'), 2);
    }

    #[Test]
    public function noMinAndMaxReturnsValid(): void
    {
        $input = new DateTime('1970-01-01 00:00:00 UTC');
        $expected = Result::valid($input);
        $range = new RangeDelta();

        $result = $range->validate($input);

        self::assertEquals($expected, $result);
    }

    public static function dataSetsWithDatesEarlierThanMin(): array
    {
        $now = new DateTime();

        return [
            [$now->sub(new DateInterval('P15Y')), new DateInterval('P10Y')],
            [$now->sub(new DateInterval('P3D')), new DateInterval('P2D')],
        ];
    }

    #[DataProvider('dataSetsWithDatesEarlierThanMin')]
    #[Test]
    public function datesEarlierThanMinReturnInvalid(DateTime $input, DateInterval $min): void
    {
        $now = new DateTime();
        $expectedMessage = new Message('DateTime is expected to be after %s', [$now->sub($min)->format(DATE_ATOM)]);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $range = new RangeDelta($min);

        $result = $range->validate($input);

        self::assertEqualsWithDelta($expected, $result, 2);
    }

    public static function dataSetsWithDatesLaterThanMax(): array
    {
        $now = new DateTime();

        return [
            [$now->add(new DateInterval('P5Y1M')), new DateInterval('P5Y')],
            [$now->add(new DateInterval('P1M4D')), new DateInterval('P1M3D')],
        ];
    }

    #[DataProvider('dataSetsWithDatesLaterThanMax')]
    #[Test]
    public function datesLaterThanMaxReturnInvalid(DateTime $input, DateInterval $max): void
    {
        $now = new DateTime();
        $expectedMessage = new Message('DateTime is expected to be before %s', [$now->add($max)->format(DATE_ATOM)]);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $range = new RangeDelta(null, $max);

        $result = $range->validate($input);

        self::assertEqualsWithDelta($expected, $result, 2);
    }

    public static function dataSetsWithDatesWithinRange(): array
    {
        $now = new DateTime();

        return [
            [$now, new DateInterval('P1Y'), new DateInterval('P1Y')],
        ];
    }

    #[DataProvider('dataSetsWithDatesWithinRange')]
    #[Test]
    public function datesWithinRangeReturnValid(DateTime $input, DateInterval $min, DateInterval $max): void
    {
        $expected = Result::valid($input);
        $range = new RangeDelta($min, $max);

        $result = $range->validate($input);

        self::assertEquals($expected, $result);
    }
}
