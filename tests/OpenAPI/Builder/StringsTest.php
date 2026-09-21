<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Builder;

use Membrane\Filter\String\ToUpperCase;
use Membrane\OpenAPI\Builder\APIBuilder;
use Membrane\OpenAPI\Builder\Strings;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\{Identifier, V30, V31};
use Membrane\OpenAPIReader\ValueObject\Value;
use Membrane\Processor;
use Membrane\Processor\AnyOf;
use Membrane\Processor\Field;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\String\DateString;
use Membrane\Validator\String\Length;
use Membrane\Validator\String\Regex;
use Membrane\Validator\Type\IsString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Strings::class)]
#[CoversClass(APIBuilder::class)]
#[UsesClass(AnyOf::class)]
#[UsesClass(Field::class)]
#[UsesClass(Contained::class)]
#[UsesClass(DateString::class)]
#[UsesClass(Length::class)]
#[UsesClass(Regex::class)]
#[UsesClass(\Membrane\Validator\Utility\AnyOf::class)]
class StringsTest extends TestCase
{
    public static function specificationsToBuild(): array
    {
        return [
            'mvp' => [
                new Field('mvp', new IsString()),
                'mvp',
                new V30\Schema(
                    new Identifier('test'),
                    new Partial\Schema(type: 'string'),
                )->value,
            ],
            'date' => [
                new Field(
                    'date',
                    new IsString(),
                    new DateString('Y-m-d', true),
                ),
                'date',
                new V30\Schema(
                    new Identifier(''),
                    new Partial\Schema(type: 'string', format: 'date')
                )->value,
            ],
            'date-time' => [
                new Field(
                    'date-time',
                    new IsString(),
                    new ToUpperCase(),
                    new \Membrane\Validator\Utility\AnyOf(
                        new DateString('Y-m-d\TH:i:sP', true),
                        new DateString('Y-m-d\TH:i:sp', true),
                    ),
                ),
                'date-time',
                new V30\Schema(
                    new Identifier(''),
                    new Partial\Schema(type: 'string', format: 'date-time'),
                )->value,
            ],
            'detailed input' => [
                new Field(
                    'max',
                    new IsString(),
                    new Contained(['1970/01/01', null]),
                    new DateString('Y-m-d', true),
                    new Length(0, 100),
                    new Regex('#.+#u'),
                ),
                'max',
                new V30\Schema(
                    new Identifier(''),
                    new Partial\Schema(
                        type: 'string',
                        enum: [new Value('1970/01/01'), new Value(null)],
                        maxLength: 100,
                        minLength: 0,
                        pattern: '.+',
                        format: 'date',
                    ),
                )->value,
            ],
        ];
    }

    #[DataProvider('specificationsToBuild')]
    #[Test]
    public function buildTest(
        Processor $expected,
        string $fieldName,
        V30\Keywords | V31\Keywords $keywords,
        bool $convertFromArray = false,
        ?string $style = null,
    ): void {
        self::assertEquals(
            $expected,
            new Strings()->build(
                $fieldName,
                $keywords,
                $convertFromArray,
                $style,
            ),
        );
    }
}
