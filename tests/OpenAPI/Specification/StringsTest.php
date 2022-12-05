<?php

declare(strict_types=1);

namespace OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Exception;
use Membrane\OpenAPI\Specification\Strings;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Specification\Strings
 * @covers \Membrane\OpenAPI\Specification\APISchema
 */
class StringsTest extends TestCase
{
    /** @test */
    public function throwsExceptionForInvalidType(): void
    {
        self::expectExceptionObject(
            new Exception('Strings Specification requires specified type of string')
        );

        new Strings('', new Schema([]));
    }

    public function dataSetsToConstruct(): array
    {
        return [
            'default values' => [
                new Schema(['type' => 'string',]),
                [
                    'maxLength' => null,
                    'minLength' => 0,
                    'pattern' => null,
                    'enum' => null,
                    'format' => null,
                    'nullable' => false,
                ],
            ],
            'assigned values' => [
                new Schema([
                    'type' => 'string',
                    'maxLength' => 20,
                    'minLength' => 6,
                    'pattern' => '#.+#',
                    'enum' => ['This is a string', 'So is this'],
                    'format' => 'arbitrary',
                    'nullable' => true,
                ]),
                [
                    'maxLength' => 20,
                    'minLength' => 6,
                    'pattern' => '#.+#',
                    'enum' => ['This is a string', 'So is this'],
                    'format' => 'arbitrary',
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
        $sut = new Strings('', $schema);

        foreach ($expected as $key => $value) {
            self::assertSame($value, $sut->$key, sprintf('%s does not meet expected value', $key));
        }
    }
}
