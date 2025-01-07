<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Specification;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\Specification\APISchema;
use Membrane\OpenAPI\Specification\Strings;
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
        self::expectExceptionObject(CannotProcessSpecification::mismatchedType(['string'], []));

        new Strings(
            '',
            (new V30\Schema(new Identifier('test'), new Partial\Schema()))->value,
        );
    }

    #[Test]
    public function throwsExceptionForIncorrectType(): void
    {
        self::expectExceptionObject(CannotProcessSpecification::mismatchedType(['string'], ['integer']));

        new Strings(
            '',
            (new V30\Schema(new Identifier('test'), new Partial\Schema(type: 'integer')))->value,
        );
    }

    public static function dataSetsToConstruct(): array
    {
        return [
            'default values' => [
                new V30\Schema(new Identifier('test'), new Partial\Schema(type: 'string')),
                [
                    'maxLength' => null,
                    'minLength' => 0,
                    'pattern' => null,
                    'enum' => null,
                    'format' => '',
                ],
            ],
            'assigned values' => [
                new V30\Schema(new Identifier('test'), new Partial\Schema(
                    type: 'string',
                    enum: [new Value('This is a string'), new Value('So is this')],
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
                ],
            ],
        ];
    }

    #[DataProvider('dataSetsToConstruct')]
    #[Test]
    public function constructTest(V30\Schema|V31\Schema $schema, array $expected): void
    {
        $sut = new Strings('', $schema->value);

        foreach ($expected as $key => $value) {
            self::assertSame($value, $sut->$key, sprintf('%s does not meet expected value', $key));
        }
    }
}
