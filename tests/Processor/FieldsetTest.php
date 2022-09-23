<?php

declare(strict_types=1);

namespace Processor;

use Membrane\Processor\Fieldset;
use Membrane\Result\Fieldname;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Processor\Fieldset
 * @uses   \Membrane\Processor\AfterSet
 * @uses   \Membrane\Processor\BeforeSet
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 * @uses   \Membrane\Processor\Field
 * @uses   \Membrane\Result\Fieldname
 */
class FieldsetTest extends TestCase
{
    /**
     * @test
     */
    public function ProcessesMethodReturnsProcessesString(): void
    {
        $fieldname = 'test field';
        $fieldset = new FieldSet($fieldname);

        $output = $fieldset->processes();

        self::assertEquals($output, $fieldname);
    }

    /**
     * @test
     */
    public function ProcessMethodWithNoChainReturnsNoResult(): void
    {
        $fieldname = 'value';
        $expected = Result::noResult($fieldname);
        $fieldset = new FieldSet('Child field');

        $result = $fieldset->process(new Fieldname('Parent field'), $fieldname);

        self::assertEquals($expected, $result);
    }

//    /**
//     * @test
//     */
//    public function ProcessMethodCallsFieldProcessMethod(): void
//    {
//        $input = 'value';
//        $field = self::createMock(Field::class);
//        $field->expects(self::once())
//            ->method('process')
//            ->with(new Fieldname('Parent field'), $input);
//        $fieldset = new FieldSet('Child field', $field);
//
//        $fieldset->process(new Fieldname('Parent field'), $input);
//    }
}
