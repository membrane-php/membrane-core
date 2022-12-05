<?php

declare(strict_types=1);

namespace OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Exception;
use Membrane\OpenAPI\Specification\Objects;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Specification\Objects
 * @covers \Membrane\OpenAPI\Specification\APISchema
 */
class ObjectsTest extends TestCase
{
    /** @test */
    public function throwsExceptionForInvalidType(): void
    {
        self::expectExceptionObject(
            new Exception('Objects Specification requires specified type of object')
        );

        new Objects('', new Schema([]));
    }

    public function dataSetsToConstruct(): array
    {
        return [
            'default values' => [
                new Schema(['type' => 'object',]),
                [
                    'properties' => [],
                    'required' => null,
                    'enum' => null,
                    'format' => null,
                    'nullable' => false,
                ],
            ],
            'assigned values' => [
                new Schema([
                    'type' => 'object',
                    'properties' => ['id' => new Schema(['type' => 'integer'])],
                    'required' => ['id'],
                    'enum' => [false, null],
                    'format' => 'you cannot say yes',
                    'nullable' => true,
                ]),
                [
                    'properties' => ['id' => new Schema(['type' => 'integer'])],
                    'required' => ['id'],
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
        $sut = new Objects('', $schema);

        foreach ($expected as $key => $value) {
            if ($key === 'properties') {
                self::assertEquals($value, $sut->$key, sprintf('%s does not meet expected value', $key));
            } else {
                self::assertSame($value, $sut->$key, sprintf('%s does not meet expected value', $key));
            }
        }
    }
}
