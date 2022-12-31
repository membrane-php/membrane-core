<?php

declare(strict_types=1);

namespace OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Specification\TrueFalse;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Specification\TrueFalse
 * @covers \Membrane\OpenAPI\Specification\APISchema
 * @covers \Membrane\OpenAPI\Exception\CannotProcessOpenAPI
 */
class TrueFalseTest extends TestCase
{
    /** @test */
    public function throwsExceptionForMissingType(): void
    {
        self::expectExceptionObject(CannotProcessOpenAPI::mismatchedType(TrueFalse::class, 'boolean', 'no type'));

        new TrueFalse('', new Schema([]));
    }

    /** @test */
    public function throwsExceptionForInvalidType(): void
    {
        self::expectExceptionObject(CannotProcessOpenAPI::mismatchedType(TrueFalse::class, 'boolean', 'string'));

        new TrueFalse('', new Schema(['type' => 'string']));
    }

    public function dataSetsToConstruct(): array
    {
        return [
            'default values' => [
                new Schema(['type' => 'boolean',]),
                [
                    'enum' => null,
                    'format' => null,
                    'nullable' => false,
                ],
            ],
            'assigned values' => [
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

    /**
     * @test
     * @dataProvider dataSetsToConstruct
     */
    public function constructTest(Schema $schema, array $expected): void
    {
        $sut = new TrueFalse('', $schema);

        foreach ($expected as $key => $value) {
            self::assertSame($value, $sut->$key, sprintf('%s does not meet expected value', $key));
        }
    }
}
