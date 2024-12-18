<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Specification;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\Specification\APISchema;
use Membrane\OpenAPI\Specification\Strings;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\{Identifier, V30, V31};
use Membrane\OpenAPIReader\ValueObject\Value;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Strings::class)]
#[CoversClass(APISchema::class)]
#[CoversClass(CannotProcessSpecification::class)]
class StringsTest extends TestCase
{
    #[Test]
    public function throwsExceptionForMissingType(): void
    {
        self::expectExceptionObject(CannotProcessSpecification::unspecifiedType(Strings::class, 'string'));

        new Strings(OpenAPIVersion::Version_3_0, '', new V30\Schema(new Identifier('test'), new Partial\Schema()));
    }

    #[Test]
    public function throwsExceptionForIncorrectType(): void
    {
        self::expectExceptionObject(CannotProcessSpecification::mismatchedType(Strings::class, 'string', 'integer'));

        new Strings(
            OpenAPIVersion::Version_3_0,
            '',
            new V30\Schema(new Identifier('test'), new Partial\Schema(type: 'integer')),
        );
    }

    public static function dataSetsToConstruct(): array
    {
        return [
            'default values' => [
                OpenAPIVersion::Version_3_0,
                new V30\Schema(new Identifier('test'), new Partial\Schema(type: 'string')),
                [
                    'maxLength' => null,
                    'minLength' => 0,
                    'pattern' => null,
                    'enum' => null,
                    'format' => '',
                    'nullable' => false,
                ],
            ],
            'assigned values' => [
                OpenAPIVersion::Version_3_0,
                new V30\Schema(new Identifier('test'), new Partial\Schema(
                    type: 'string',
                    enum: [new Value('This is a string'), new Value('So is this')],
                    nullable: true,
                    maxLength: 20,
                    minLength: 6,
                    pattern: '.+',
                    format: 'arbitrary',
                )),
                [
                    'maxLength' => 20,
                    'minLength' => 6,
                    'pattern' => '.+',
                    'enum' => ['This is a string', 'So is this'],
                    'format' => 'arbitrary',
                    'nullable' => true,
                ],
            ],
        ];
    }

    #[DataProvider('dataSetsToConstruct')]
    #[Test]
    public function constructTest(OpenAPIVersion $openAPIVersion, V30\Schema|V31\Schema $schema, array $expected): void
    {
        $sut = new Strings($openAPIVersion, '', $schema);

        foreach ($expected as $key => $value) {
            self::assertSame($value, $sut->$key, sprintf('%s does not meet expected value', $key));
        }
    }
}
