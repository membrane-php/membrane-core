<?php
declare(strict_types=1);

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
            [[''], ''],
            [['test field'], 'test field'],
            [['test field', 'this', 'is', 'a'], 'this->is->a->test field'],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsForStringRepresentation
     */
    public function StringRepresentationTest(array $input, string $expected) : void
    {
        $fieldname = new Fieldname(...$input);

        $result = $fieldname->getStringRepresentation();

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
    public function EqualPairsAreMergable(Fieldname $firstFieldname, Fieldname $secondFieldname, bool $expected) : void
    {
        $equals = $firstFieldname->equals($secondFieldname);
        $mergable = $firstFieldname->mergable($secondFieldname);

        self::assertEquals($expected, $equals);
        self::assertEquals($expected, $mergable);
    }
    
    /**
     * @test
     */
    public function FieldnameIsAlwaysMergableByItself () : void
    {
        $fieldname = new Fieldname('test field');

        $result = $fieldname->mergable(null);

        self::assertTrue($result);
    }


}