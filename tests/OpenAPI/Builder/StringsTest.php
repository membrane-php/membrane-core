<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Builder;

use Membrane\Filter\String\ToUpperCase;
use Membrane\OpenAPI\Builder\APIBuilder;
use Membrane\OpenAPI\Builder\Strings;
use Membrane\OpenAPI\Processor\AnyOf;
use Membrane\OpenAPI\Specification;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\{Identifier, V30};
use Membrane\OpenAPIReader\ValueObject\Value;
use Membrane\Processor;
use Membrane\Processor\Field;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\String\DateString;
use Membrane\Validator\String\Length;
use Membrane\Validator\String\Regex;
use Membrane\Validator\Type\IsNull;
use Membrane\Validator\Type\IsString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Strings::class)]
#[CoversClass(APIBuilder::class)]
#[UsesClass(AnyOf::class)]
#[UsesClass(Specification\Strings::class)]
#[UsesClass(Field::class)]
#[UsesClass(Contained::class)]
#[UsesClass(DateString::class)]
#[UsesClass(Length::class)]
#[UsesClass(Regex::class)]
#[UsesClass(\Membrane\Validator\Utility\AnyOf::class)]
class StringsTest extends TestCase
{
    #[Test]
    public function supportsStringsSpecification(): void
    {
        $specification = self::createStub(Specification\Strings::class);
        $sut = new Strings();

        self::assertTrue($sut->supports($specification));
    }

    #[Test]
    public function doesNotSupportNonStringsSpecification(): void
    {
        $specification = self::createStub(\Membrane\Builder\Specification::class);
        $sut = new Strings();

        self::assertFalse($sut->supports($specification));
    }

    public static function specificationsToBuild(): array
    {
        return [
            'minimum input' => [
                new Specification\Strings(
                    OpenAPIVersion::Version_3_0,
                    '',
                    (new V30\Schema(new Identifier('test'), new Partial\Schema(type: 'string')))->value,
                ),
                new Field('', new IsString()),
            ],
            'date input' => [
                new Specification\Strings(
                    OpenAPIVersion::Version_3_0,
                    '',
                    (new V30\Schema(new Identifier(''), new Partial\Schema(type: 'string', format: 'date')))->value,
                ),
                new Field('', new IsString(), new DateString('Y-m-d', true)),
            ],
            'date-time input' => [
                new Specification\Strings(
                    OpenAPIVersion::Version_3_0,
                    '',
                    (new V30\Schema(new Identifier(''), new Partial\Schema(type: 'string', format: 'date-time')))->value,
                ),
                new Field(
                    '',
                    new IsString(),
                    new ToUpperCase(),
                    new \Membrane\Validator\Utility\AnyOf(
                        new DateString('Y-m-d\TH:i:sP', true),
                        new DateString('Y-m-d\TH:i:sp', true),
                    ),
                ),
            ],
            'detailed input' => [
                new Specification\Strings(
                    OpenAPIVersion::Version_3_0,
                    '',
                    (new V30\Schema(
                        new Identifier(''), new Partial\Schema(
                        type: 'string',
                        enum: [new Value('1970/01/01'), new Value(null)],
                        maxLength: 100,
                        minLength: 0,
                        pattern: '.+',
                        format: 'date',
                    )
                    ))->value,
                ),
                new Field(
                    '',
                    new IsString(),
                    new Contained(['1970/01/01', null]),
                    new DateString('Y-m-d', true),
                    new Length(0, 100),
                    new Regex('#.+#u'),
                )
            ],
        ];
    }

    #[DataProvider('specificationsToBuild')]
    #[Test]
    public function buildTest(Specification\Strings $specification, Processor $expected): void
    {
        $sut = new Strings();

        $actual = $sut->build($specification);

        self::assertEquals($expected, $actual);
    }
}
