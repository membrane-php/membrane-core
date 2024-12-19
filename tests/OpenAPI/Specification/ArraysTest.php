<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Specification;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\Specification\APISchema;
use Membrane\OpenAPI\Specification\Arrays;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\{Identifier, V30, V31};
use Membrane\OpenAPIReader\ValueObject\Value;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Arrays::class)]
#[CoversClass(APISchema::class)]
#[CoversClass(CannotProcessSpecification::class)]
class ArraysTest extends TestCase
{
    #[Test]
    public function throwsExceptionForMissingType(): void
    {
        self::expectExceptionObject(CannotProcessSpecification::mismatchedType(['array'], []));

        new Arrays(
            OpenAPIVersion::Version_3_0,
            '',
            (new V30\Schema(new Identifier('test'), new Partial\Schema()))->value
        );
    }

    #[Test]
    public function throwsExceptionForInvalidType(): void
    {
        self::expectExceptionObject(CannotProcessSpecification::mismatchedType(['array'], ['string']));

        new Arrays(
            OpenAPIVersion::Version_3_0,
            '',
            (new V30\Schema(new Identifier('test'), new Partial\Schema(type: 'string')))->value
        );
    }

    public static function dataSetsToConstruct(): array
    {
        return [
            'default values' => [
                OpenAPIVersion::Version_3_0,
                new V30\Schema(new Identifier('test'), new Partial\Schema(type: 'array')),
                [
                    'items' => new V30\Schema(
                        new Identifier('test', 'items'),
                        true
                    ),
                    'maxItems' => null,
                    'minItems' => 0,
                    'uniqueItems' => false,
                    'enum' => null,
                    'format' => '',
                ],
            ],
            'assigned values' => [
                OpenAPIVersion::Version_3_0,
                new V30\Schema(new Identifier('test'), new Partial\Schema(
                    type: 'array',
                    enum: [new Value([1, 2, 3]), new Value([5, 6, 7])],
                    maxItems: 5,
                    minItems: 2,
                    uniqueItems: true,
                    items: new Partial\Schema(type: 'integer'),
                    format: 'array of ints',
                )),
                [
                    'items' => new V30\Schema(
                        new Identifier('test', 'items'),
                        new Partial\Schema(type: 'integer'),
                    ),
                    'maxItems' => 5,
                    'minItems' => 2,
                    'uniqueItems' => true,
                    'enum' => [[1, 2, 3], [5, 6, 7]],
                    'format' => 'array of ints',
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataSetsToConstruct')]
    public function constructTest(
        OpenAPIVersion $openAPIVersion,
        V30\Schema | V31\Schema $schema,
        array $expected
    ): void {
        $sut = new Arrays($openAPIVersion, '', $schema->value);

        foreach ($expected as $key => $value) {
            if ($key === 'items') {
                self::assertEquals($value, $sut->$key, sprintf('%s does not meet expected value', $key));
            } else {
                self::assertSame($value, $sut->$key, sprintf('%s does not meet expected value', $key));
            }
        }
    }
}
