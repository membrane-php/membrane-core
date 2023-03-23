<?php

declare(strict_types=1);

namespace OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\Specification\APISchema;
use Membrane\OpenAPI\Specification\Strings;
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
        self::expectExceptionObject(CannotProcessSpecification::mismatchedType(Strings::class, 'string', 'no type'));

        new Strings('', new Schema([]));
    }

    #[Test]
    public function throwsExceptionForIncorrectType(): void
    {
        self::expectExceptionObject(CannotProcessSpecification::mismatchedType(Strings::class, 'string', 'integer'));

        new Strings('', new Schema(['type' => 'integer']));
    }

    public static function dataSetsToConstruct(): array
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

    #[DataProvider('dataSetsToConstruct')]
    #[Test]
    public function constructTest(Schema $schema, array $expected): void
    {
        $sut = new Strings('', $schema);

        foreach ($expected as $key => $value) {
            self::assertSame($value, $sut->$key, sprintf('%s does not meet expected value', $key));
        }
    }
}
