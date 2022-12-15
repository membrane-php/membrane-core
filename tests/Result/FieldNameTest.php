<?php

declare(strict_types=1);

namespace Result;

use Membrane\Result\FieldName;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Result\FieldName
 */
class FieldNameTest extends TestCase
{
    /**
     * @test
     */
    public function pushTest(): void
    {
        $expected = new FieldName('new field', 'original field');
        $fieldName = new FieldName('original field');

        $result = $fieldName->push(new FieldName('new field'));

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function fieldNameIsAlwaysMergableByItself(): void
    {
        $fieldName = new FieldName('test field');

        $result = $fieldName->mergable(null);

        self::assertTrue($result);
    }

    public function dataSetsWithEqualStringRepresentations(): array
    {
        return [
            [
                new FieldName(''),
                new FieldName(''),
                true,
            ],
            [
                new FieldName('test field'),
                new FieldName('test field'),
                true,
            ],
            [
                new FieldName('test field', 'this', 'is', 'a'),
                new FieldName('test field', 'this', 'is', 'a'),
                true,
            ],
            [
                new FieldName('test field', 'this', 'is', 'a'),
                new FieldName('test field', 'this', 'is', 'a'),
                true,
            ],
        ];
    }

    public function dataSetsWithDifferentStringRepresentations(): array
    {
        return [
            [
                new FieldName(''),
                new FieldName(' '),
                false,
            ],
            [
                new FieldName('test field'),
                new FieldName('field test'),
                false,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithEqualStringRepresentations
     * @dataProvider dataSetsWithDifferentStringRepresentations
     */
    public function mergableTest(FieldName $firstFieldName, FieldName $secondFieldName, bool $expected): void
    {
        $equals = $firstFieldName->equals($secondFieldName);
        $mergable = $firstFieldName->mergable($secondFieldName);

        self::assertEquals($expected, $equals);
        self::assertEquals($expected, $mergable);
    }

    public function dataSetsForStringRepresentation(): array
    {
        return [
            'empty string ignored' => [[''], ''],
            'non-empty string included' => [['a'], 'a'],
            'multiple non-empty strings separated correctly' => [['d', 'a', 'b', 'c'], 'a->b->c->d'],
            'empty strings between non-empty strings ignored' => [['', 'a', '', 'b', ''], 'a->b'],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsForStringRepresentation
     */
    public function stringRepresentationTest(array $input, string $expected): void
    {
        $fieldName = new FieldName(...$input);

        $result = $fieldName->getStringRepresentation();

        self::assertEquals($expected, $result);
    }
}
