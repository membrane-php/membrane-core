<?php
declare(strict_types=1);

namespace Validator\Utility;

use Membrane\Result\Result;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Utility\Passes
 * @uses \Membrane\Result\Result
 */
class PassesTest extends TestCase
{
    public function dataSets(): array
    {
        /**
         * @return array
         */
        return [
            [1, Result::VALID],
            [1.1, Result::VALID],
            ['one', Result::VALID],
            [false, Result::VALID],
            [null, Result::VALID],
        ];
    }

    /**
     * @test
     * @dataProvider dataSets
     */
    public function PassesAlwaysReturnsValid(mixed $input, int $expected): void
    {
        $pass = new Passes;

        $result = $pass->validate($input);

        self::assertEquals($expected, $result->result);
    }
}