<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\Specification\APISchema;
use Membrane\OpenAPI\Specification\TrueFalse;
use Membrane\OpenAPIReader\OpenAPIVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TrueFalse::class)]
#[CoversClass(APISchema::class)]
#[CoversClass(CannotProcessSpecification::class)]
class TrueFalseTest extends TestCase
{
    #[Test]
    public function throwsExceptionForMissingType(): void
    {
        self::expectExceptionObject(CannotProcessSpecification::mismatchedType(TrueFalse::class, 'boolean', 'no type'));

        new TrueFalse(OpenAPIVersion::Version_3_0, '', new Schema([]));
    }

    #[Test]
    public function throwsExceptionForInvalidType(): void
    {
        self::expectExceptionObject(CannotProcessSpecification::mismatchedType(TrueFalse::class, 'boolean', 'string'));

        new TrueFalse(OpenAPIVersion::Version_3_0, '', new Schema(['type' => 'string']));
    }

    public static function dataSetsToConstruct(): array
    {
        return [
            'default values' => [
                OpenAPIVersion::Version_3_0,
                new Schema(['type' => 'boolean',]),
                [
                    'enum' => null,
                    'format' => null,
                    'nullable' => false,
                ],
            ],
            'assigned values' => [
                OpenAPIVersion::Version_3_0,
                new Schema([
                    'type' => 'boolean',
                    'enum' => [false, null],
                    'format' => 'you cannot say yes',
                    'nullable' => true,
                ]),
                [
                    'enum' => [false, null],
                    'format' => 'you cannot say yes',
                    'nullable' => true,
                ],
            ],
        ];
    }

    #[DataProvider('dataSetsToConstruct')]
    #[Test]
    public function constructTest(
        OpenAPIVersion $openAPIVersion,
        Schema $schema,
        array $expected
    ): void {
        $sut = new TrueFalse($openAPIVersion, '', $schema);

        foreach ($expected as $key => $value) {
            self::assertSame($value, $sut->$key, sprintf('%s does not meet expected value', $key));
        }
    }
}
