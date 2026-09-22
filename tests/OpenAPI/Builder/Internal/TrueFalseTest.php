<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Builder\Internal;

use Membrane\Filter\Type\ToBool;
use Membrane\OpenAPI\Builder\Internal\Schema;
use Membrane\OpenAPI\Builder\Internal\TrueFalse;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\{Identifier, V30, V31};
use Membrane\OpenAPIReader\ValueObject\Value;
use Membrane\Processor;
use Membrane\Processor\AnyOf;
use Membrane\Processor\Field;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\String\BoolString;
use Membrane\Validator\Type\IsBool;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TrueFalse::class)]
#[CoversClass(Schema::class)]
#[UsesClass(AnyOf::class)]
#[UsesClass(Field::class)]
#[UsesClass(Contained::class)]
class TrueFalseTest extends TestCase
{
    public static function specificationsToBuild(): array
    {
        return [
            'MVP' => [
                new Field('mvp', new IsBool()),
                'mvp',
                new V30\Schema(
                    new Identifier('test'),
                    new Partial\Schema(type: 'boolean')
                )->value,
            ],
            'MVP from string' => [
                new Field('mvp-from-string', new BoolString(), new ToBool()),
                'mvp-from-string',
                new V30\Schema(
                    new Identifier('test'),
                    new Partial\Schema(type: 'boolean')
                )->value,
                true,
            ],
            'max' => [
                new Field('max', new IsBool(), new Contained([true, null])),
                'max',
                new V30\Schema(
                    new Identifier('test'),
                    new Partial\Schema(
                        type: 'boolean',
                        enum: [new Value(true), new Value(null)],
                    )
                )->value,
            ],
            'max from string' => [
                new Field('max-from-string', new BoolString(), new ToBool(), new Contained([true, null])),
                'max-from-string',
                new V30\Schema(
                    new Identifier('test'),
                    new Partial\Schema(
                        type: 'boolean',
                        enum: [new Value(true), new Value(null)],
                    ),
                )->value,
                true,
            ],
        ];
    }

    #[Test]
    #[DataProvider('specificationsToBuild')]
    public function buildTest(
        Processor $expected,
        string $fieldName,
        V30\Keywords | V31\Keywords $keywords,
        bool $convertFromString = false,
        bool $convertFromArray = false,
        ?string $style = null,
    ): void {
        self::assertEquals(
            $expected,
            new TrueFalse()->build(
                $fieldName,
                $keywords,
                $convertFromString,
                $convertFromArray,
                $style,
            ),
        );
    }
}
