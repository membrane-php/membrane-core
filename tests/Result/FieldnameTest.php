<?php

namespace Result;

use Membrane\Result\Fieldname;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Result\Fieldname
 */
class FieldnameTest extends TestCase
{
    public function dataSetsForStringRepresentation() : array
    {
        return [
            [new Fieldname(''), ''],
            [new Fieldname('test field'), 'test field'],
            [new Fieldname('test field', 'this', 'is', 'a'), 'this->is->a->test field'],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsForStringRepresentation
     */
    public function StringRepresentationTest($input, $expected) : void
    {

        $result = $input->getStringRepresentation();

        self::assertEquals($expected, $result);
    }

    public function dataSetsWithEqualStringRepresentations() : array
    {
        return [
            [
                new Fieldname(''),
                new Fieldname(''),
                true
            ],
            [
                new Fieldname('test field'),
                new Fieldname('test field'),
                true
            ],
            [
                new Fieldname('test field', 'this', 'is', 'a'),
                new Fieldname('test field', 'this', 'is', 'a'),
                true
            ],
            [
                new Fieldname('test field', 'this', 'is', 'a'),
                new Fieldname('test field', 'this', 'is', 'a'),
                true
            ],
        ];
    }

    public function dataSetsWithDifferentStringRepresentations() : array
    {
        return [
            [
                new Fieldname(''),
                new Fieldname(' '),
                false
            ],
            [
                new Fieldname('test field'),
                new Fieldname('field test'),
                false
            ],
//            [
//                new Fieldname('test field', 'this', 'is', 'a'),
//                new Fieldname('test field', 'this->is->a'),
//                false
//            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithEqualStringRepresentations
     * @dataProvider dataSetsWithDifferentStringRepresentations
     */
    public function EqualPairsAreMergable($firstInput, $secondInput, $expected) : void
    {

        $equals = $firstInput->equals($secondInput);
        $mergable = $firstInput->equals($secondInput);

        self::assertEquals($expected, $equals);
        self::assertEquals($expected, $mergable);
    }
    
    /**
     * @test
     */
    public function FieldnameIsAlwaysMergableByItself () {
        $sut = new Fieldname('test field');

        $result = $sut->mergable(null);

        self::assertTrue($result);
    }


}