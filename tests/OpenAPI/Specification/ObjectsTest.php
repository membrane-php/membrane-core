<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Specification;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\Specification\APISchema;
use Membrane\OpenAPI\Specification\Objects;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\{Identifier, V30, V31};
use Membrane\OpenAPIReader\ValueObject\Value;
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
        self::expectExceptionObject(CannotProcessSpecification::mismatchedType(['object'], []));

        new Objects(
            '',
            (new V30\Schema(new Identifier('test'), new Partial\Schema()))->value
        );
    }

    #[Test]
    public function throwsExceptionForIncorrectType(): void
    {
        self::expectExceptionObject(CannotProcessSpecification::mismatchedType(['object'], ['string']));

        new Objects(
            '',
            (new V30\Schema(new Identifier('test'), new Partial\Schema(type: 'string')))->value,
        );
    }

    public static function dataSetsToConstruct(): array
    {
        return [
            'default values' => [
                new V30\Schema(new Identifier('test'), new Partial\Schema(type: 'object')),
                [
                    'additionalProperties' => new V30\Schema(
                        new Identifier('test', 'additionalProperties'),
                        true,
                    ),
                    'properties' => [],
                    'required' => [],
                    'enum' => null,
                    'format' => '',
                ],
            ],
            'additionalProperties assigned false' => [
                new V30\Schema(new Identifier('test'), new Partial\Schema(
                    type: 'object',
                    additionalProperties: false,
                )),
                [
                    'additionalProperties' => new V30\Schema(
                        new Identifier('test', 'additionalProperties'),
                        false,
                    ),
                    'properties' => [],
                    'required' => [],
                    'enum' => null,
                    'format' => '',
                ],
            ],
            'all relevant keywords assigned values' => [
                new V30\Schema(new Identifier('test'), new Partial\Schema(
                    type: 'object',
                    enum: [new Value(['id' => 5]), new Value(['id' => 10])],
                    required: ['id'],
                    properties: ['id' => new Partial\Schema(type: 'integer')],
                    additionalProperties: new Partial\Schema(type: 'string'),
                    format: 'you cannot say yes',
                )),
                [
                    'additionalProperties' => new V30\Schema(
                        new Identifier('test', 'additionalProperties'),
                        new Partial\Schema(type: 'string')
                    ),
                    'properties' => ['id' => new V30\Schema(
                        new Identifier('test', 'properties(id)'),
                        new Partial\Schema(type: 'integer')
                    )],
                    'required' => ['id'],
                    'enum' => [['id' => 5], ['id' => 10]],
                    'format' => 'you cannot say yes',
                ],
            ],
        ];
    }

    #[DataProvider('dataSetsToConstruct')]
    #[Test]
    public function constructTest(
        V30\Schema | V31\Schema $schema,
        array $expected
    ): void {
        $sut = new Objects('', $schema->value);

        foreach ($expected as $key => $value) {
            self::assertEquals($value, $sut->$key, sprintf('%s does not meet expected value', $key));
        }
    }
}
