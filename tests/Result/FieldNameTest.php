<?php

declare(strict_types=1);

namespace Result;

use Membrane\Result\FieldName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FieldName::class)]
class FieldNameTest extends TestCase
{
    #[Test]
    public function pushTest(): void
    {
        $expected = new FieldName('new field', 'original field');
        $fieldName = new FieldName('original field');

        $result = $fieldName->push(new FieldName('new field'));

        self::assertEquals($expected, $result);
    }

    #[Test]
    public function fieldNameIsAlwaysMergableByItself(): void
    {
        $fieldName = new FieldName('test field');

        $result = $fieldName->mergable(null);

        self::assertTrue($result);
    }

    public static function dataSetsWithEqualStringRepresentations(): array
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

    public static function dataSetsWithDifferentStringRepresentations(): array
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

    #[DataProvider('dataSetsWithEqualStringRepresentations')]
    #[DataProvider('dataSetsWithDifferentStringRepresentations')]
    #[Test]
    public function mergableTest(FieldName $firstFieldName, FieldName $secondFieldName, bool $expected): void
    {
        $equals = $firstFieldName->equals($secondFieldName);
        $mergable = $firstFieldName->mergable($secondFieldName);

        self::assertEquals($expected, $equals);
        self::assertEquals($expected, $mergable);
    }

    public static function dataSetsForStringRepresentation(): array
    {
        return [
            'empty string ignored' => [[''], ''],
            'non-empty string included' => [['a'], 'a'],
            'multiple non-empty strings separated correctly' => [['d', 'a', 'b', 'c'], 'a->b->c->d'],
            'empty strings between non-empty strings ignored' => [['', 'a', '', 'b', ''], 'a->b'],
        ];
    }

    #[DataProvider('dataSetsForStringRepresentation')]
    #[Test]
    public function stringRepresentationTest(array $input, string $expected): void
    {
        $fieldName = new FieldName(...$input);

        $result = $fieldName->getStringRepresentation();

        self::assertEquals($expected, $result);
    }
}
