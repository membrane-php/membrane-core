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
        $regex = new Regex('');
        $expected = Result::invalid(
            $input,
            new MessageSet(
                null,
                new Message('Regex Validator requires a string, %s given', [$expectedVars])
            )
        );

        $result = $regex->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsThatPass(): array
    {
        return [
            'empty regex' => ['', ''],
            'one digit' => ['\d', '1'],
            'one lower-case letter' => ['[a-z]', 'f'],
            'one letter' => ['[a-zA-Z]', 'F'],
            '? quantifier, none provided' => ['^\d?$', ''],
            '? quantifier, one provided' => ['^\d?$', '1'],
            '* quantifier, none provided' => ['^\d*$', ''],
            '* quantifier, many provided' => ['^\d*$', '123456789'],
            '+ quantifier, one provided' => ['^\d+$', '1'],
            '+ quantifier, many provided' => ['^\d+$', '123456789'],
            '{min,max} quantifier, one provided' => ['^\d{2,5}$', '12'],
            '{min,max} quantifier, many provided' => ['^\d{2,5}$', '12345'],
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
            ['abc', 'ABC'],
            ['[abc]', 'd'],
            ['\d{3}', '12'],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatFail
     */
    public function stringsThatDoNotMatchPatternReturnInvalid(string $pattern, string $input): void
    {
        $regex = new Regex($pattern);
        $expectedPattern = sprintf('#%s#u', str_replace('#', '\#', $pattern));
        $expectedMessage = new Message('String does not match the required pattern %s', [$expectedPattern]);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $regex->validate($input);

        self::assertEquals($expected, $result);
    }
}
