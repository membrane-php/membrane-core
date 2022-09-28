<?php

declare(strict_types=1);

namespace Validator\String;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\String\Regex;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\String\Regex
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class RegexTest extends TestCase
{
    public function dataSetsThatPass(): array
    {
        return [
            ['//', ''],
            ['/[abc]/i', 'B'],
            ['/\d{3}/', '123'],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatPass
     */
    public function stringsThatMatchPatternReturnValid(string $pattern, string $input): void
    {
        $regex = new Regex($pattern);
        $expected = Result::valid($input);

        $result = $regex->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsThatFail(): array
    {
        return [
            ['/abc/', 'ABC'],
            ['/[abc]/', 'd'],
            ['/d{3}/', '12'],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatFail
     */
    public function stringsThatDoNotMatchPatternReturnInvalid(string $pattern, string $input): void
    {
        $regex = new Regex($pattern);
        $expectedMessage = new Message('String does not match the required pattern %s', [$pattern]);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $regex->validate($input);

        self::assertEquals($expected, $result);
    }
}
