<?php

declare(strict_types=1);

namespace OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Exception;
use Membrane\OpenAPI\Specification\TrueFalse;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Specification\TrueFalse
 * @covers \Membrane\OpenAPI\Specification\APISchema
 */
class TrueFalseTest extends TestCase
{
    /** @test */
    public function throwsExceptionForInvalidType(): void
    {
        self::expectExceptionObject(
            new Exception('TrueFalse Specification requires specified type of boolean')
        );

        new TrueFalse('', new Schema([]));
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
