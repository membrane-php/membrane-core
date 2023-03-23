<?php

declare(strict_types=1);

namespace OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\Specification\APISchema;
use Membrane\OpenAPI\Specification\Arrays;
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
        self::expectExceptionObject(CannotProcessSpecification::mismatchedType(Arrays::class, 'array', 'no type'));

        new Arrays('', new Schema([]));
    }

    #[Test]
    public function throwsExceptionForInvalidType(): void
    {
        self::expectExceptionObject(CannotProcessSpecification::mismatchedType(Arrays::class, 'array', 'string'));

        new Arrays('', new Schema(['type' => 'string']));
    }

    public static function dataSetsToConstruct(): array
    {
        return [
            'default values' => [
                new Schema(['type' => 'array',]),
                [
                    'items' => null,
                    'maxItems' => null,
                    'minItems' => 0,
                    'uniqueItems' => false,
                    'enum' => null,
                    'format' => null,
                    'nullable' => false,
                ],
            ],
            'assigned values' => [
                new Schema([
                    'type' => 'array',
                    'items' => new Schema(['type' => 'integer']),
                    'maxItems' => 5,
                    'minItems' => 2,
                    'uniqueItems' => true,
                    'enum' => [[1, 2, 3], [5, 6, 7]],
                    'format' => 'array of ints',
                    'nullable' => true,
                ]),
                [
                    'items' => new Schema(['type' => 'integer']),
                    'maxItems' => 5,
                    'minItems' => 2,
                    'uniqueItems' => true,
                    'enum' => [[1, 2, 3], [5, 6, 7]],
                    'format' => 'array of ints',
                    'nullable' => true,
                ],
            ],
        ];
    }

    #[DataProvider('dataSetsToConstruct')]
    #[Test]
    public function constructTest(Schema $schema, array $expected): void
    {
        $sut = new Arrays('', $schema);

        foreach ($expected as $key => $value) {
            if ($key === 'items') {
                self::assertEquals($value, $sut->$key, sprintf('%s does not meet expected value', $key));
            } else {
                self::assertSame($value, $sut->$key, sprintf('%s does not meet expected value', $key));
            }
        }
    }
}
