<?php

declare(strict_types=1);

namespace Filter\Shape;

use Exception;
use Membrane\Filter\Shape\Truncate;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Filter\Shape\Truncate
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class TruncateTest extends TestCase
{
    /**
     * @test
     * @throws Exception
     */
    public function negativeMaxLengthThrowsError()
    {
        self::expectExceptionMessage('Truncate filter cannot take negative max lengths');

        $truncate = new Truncate(-1);
    }

    public function dataSetsWithIncorrectInputs(): array
    {
        $notArrayMessage = 'Truncate filter requires lists, %s given';
        $arrayMessage = 'Truncate filter requires lists, for arrays use Delete';
        return [
            [123, new Message($notArrayMessage, ['integer'])],
            [1.23, new Message($notArrayMessage, ['double'])],
            ['this is a string', new Message($notArrayMessage, ['string'])],
            [true, new Message($notArrayMessage, ['boolean'])],
            [['an' => 'array', 'with' => 'key', 'value' => 'pairs'], new Message($arrayMessage, [])],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithIncorrectInputs
     */
    public function incorrectInputsReturnInvalid(mixed $input, Message $expectedMessage): void
    {
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $truncate = new Truncate(3);

        $result = $truncate->filter($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function returnInputIfAlreadyBelowMaxLength(): void
    {
        $input = ['a', 'list'];
        $expected = Result::noResult($input);
        $truncate = new Truncate(3);

        $result = $truncate->filter($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsToFilter(): array
    {
        return [
            [
                ['a', 'list'],
                0,
                [],
            ],
            [
                ['a', 'list', 'of', 'five', 'values'],
                2,
                ['a', 'list'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToFilter
     */
    public function listsAreTruncatedToMatchMaxLength(array $input, int $maxLength, array $expectedValue): void
    {
        $expected = Result::noResult($expectedValue);
        $truncate = new Truncate($maxLength);

        $result = $truncate->filter($input);

        self::assertEquals($expected, $result);
    }
}
