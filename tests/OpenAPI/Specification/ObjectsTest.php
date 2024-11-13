<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\Specification\APISchema;
use Membrane\OpenAPI\Specification\Objects;
use Membrane\OpenAPIReader\OpenAPIVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Objects::class)]
#[CoversClass(APISchema::class)]
#[CoversClass(CannotProcessSpecification::class)]
class ObjectsTest extends TestCase
{
    #[Test]
    public function throwsExceptionForMissingType(): void
    {
        self::expectExceptionObject(CannotProcessSpecification::mismatchedType(Objects::class, 'object', 'no type'));

        new Objects(OpenAPIVersion::Version_3_0, '', new Schema([]));
    }

    #[Test]
    public function throwsExceptionForIncorrectType(): void
    {
        self::expectExceptionObject(CannotProcessSpecification::mismatchedType(Objects::class, 'object', 'string'));

        new Objects(OpenAPIVersion::Version_3_0, '', new Schema(['type' => 'string']));
    }

    public static function dataSetsToConstruct(): array
    {
        return [
            'default values' => [
                OpenAPIVersion::Version_3_0,
                new Schema(['type' => 'object',]),
                [
                    'additionalProperties' => true,
                    'properties' => [],
                    'required' => null,
                    'enum' => null,
                    'format' => null,
                    'nullable' => false,
                ],
            ],
            'additionalProperties assigned false' => [
                OpenAPIVersion::Version_3_0,
                new Schema(['type' => 'object', 'additionalProperties' => false]),
                [
                    'additionalProperties' => false,
                    'properties' => [],
                    'required' => null,
                    'enum' => null,
                    'format' => null,
                    'nullable' => false,
                ],
            ],
            'all relevant keywords assigned values' => [
                OpenAPIVersion::Version_3_0,
                new Schema([
                    'type' => 'object',
                    'additionalProperties' => new Schema(['type' => 'string']),
                    'properties' => ['id' => new Schema(['type' => 'integer'])],
                    'required' => ['id'],
                    'enum' => [false, null],
                    'format' => 'you cannot say yes',
                    'nullable' => true,
                ]),
                [
                    'additionalProperties' => new Schema(['type' => 'string']),
                    'properties' => ['id' => new Schema(['type' => 'integer'])],
                    'required' => ['id'],
                    'enum' => [false, null],
                    'format' => 'you cannot say yes',
                    'nullable' => true,
                ],
            ],
        ];
    }

    #[DataProvider('dataSetsToConstruct')]
    #[Test]
    public function constructTest(OpenAPIVersion $openAPIVersion, Schema $schema, array $expected): void
    {
        $sut = new Objects($openAPIVersion, '', $schema);

        foreach ($expected as $key => $value) {
            self::assertEquals($value, $sut->$key, sprintf('%s does not meet expected value', $key));
        }
    }
}
