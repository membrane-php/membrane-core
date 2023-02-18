<?php

declare(strict_types=1);

namespace OpenAPI\Processor;

use Membrane\OpenAPI\Processor\Json;
use Membrane\Processor;
use Membrane\Processor\Field;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Processor\Json
 * @uses   \Membrane\Filter\String\JsonDecode
 * @uses   \Membrane\Processor\Field
 * @uses   \Membrane\Result\FieldName
 * @uses   \Membrane\Result\Message
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Result
 */
class JsonTest extends TestCase
{
    /** @test */
    public function toStringTest(): void
    {
        $expected = "\"pet\":\n\t- convert from json to a PHP value.\n\t- condition";
        $wrapped = self::createMock(Processor\Field::class);
        $sut = new Json($wrapped);

        $wrapped->expects($this->once())
            ->method('processes')
            ->willReturn('pet');

        $wrapped->expects(($this->once()))
            ->method('__toString')
            ->willReturn("\"pet\":\n\t- condition");

        $actual = (string)$sut;

        self::assertSame($expected, $actual);
    }

    /** @test */
    public function toPHPTest(): void
    {
        $sut = new Json(new Field('a'));

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    /** @test */
    public function processesTest(): void
    {
        $expected = 'fieldName that observer processes';
        $observer = self::createMock(Processor::class);
        $observer->expects($this->once())
            ->method('processes')
            ->willReturn($expected);
        $sut = new Json($observer);

        $actual = $sut->processes();

        self::assertSame($expected, $actual);
    }

    /** @test */
    public function processStopsEarlyIfJsonDecodeFails(): void
    {
        $expected = Result::invalid(
            5,
            new MessageSet(
                new FieldName('', ''),
                new Message('JsonDecode Filter expects a string value, %s passed instead', ['integer'])
            )
        );
        $observer = self::createMock(Processor::class);
        $observer->expects($this->never())
            ->method('process');
        $sut = new Json($observer);

        $actual = $sut->process(new FieldName(''), 5);

        self::assertEquals($expected, $actual);
    }

    /** @test */
    public function processTest(): void
    {
        $expected = Result::valid(['id' => 5]);
        $observer = self::createMock(Processor::class);
        $observer->expects($this->once())
            ->method('process')
            ->willReturn($expected);
        $sut = new Json($observer);

        $actual = $sut->process(new FieldName(''), '{"id" : 5}');

        self::assertEquals($expected, $actual);
    }
}
