<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Builder;

use cebe\openapi\spec\Schema;
use Membrane\Filter\Type\ToBool;
use Membrane\OpenAPI\Builder\APIBuilder;
use Membrane\OpenAPI\Builder\TrueFalse;
use Membrane\OpenAPI\Processor\AnyOf;
use Membrane\OpenAPI\Specification;
use Membrane\Processor;
use Membrane\Processor\Field;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\String\BoolString;
use Membrane\Validator\Type\IsBool;
use Membrane\Validator\Type\IsNull;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TrueFalse::class)]
#[CoversClass(APIBuilder::class)]
#[UsesClass(AnyOf::class)]
#[UsesClass(Specification\TrueFalse::class)]
#[UsesClass(Field::class)]
#[UsesClass(Contained::class)]
class TrueFalseTest extends TestCase
{
    #[Test]
    public function supportsNumericSpecification(): void
    {
        $specification = self::createStub(Specification\TrueFalse::class);
        $sut = new TrueFalse();

        self::assertTrue($sut->supports($specification));
    }

    #[Test]
    public function doesNotSupportNonNumericSpecification(): void
    {
        $specification = self::createStub(\Membrane\Builder\Specification::class);
        $sut = new TrueFalse();

        self::assertFalse($sut->supports($specification));
    }

    public static function specificationsToBuild(): array
    {
        return [
            'input to convert from string' => [
                new Specification\TrueFalse('', new Schema(['type' => 'boolean']), true),
                new Field('', new BoolString(), new ToBool()),
            ],
            'strict input' => [
                new Specification\TrueFalse('', new Schema(['type' => 'boolean']), false),
                new Field('', new IsBool()),
            ],
            'detailed input to convert from string' => [
                new Specification\TrueFalse(
                    '',
                    new Schema(
                        [
                            'type' => 'boolean',
                            'enum' => [true, null],
                            'format' => 'rather pointless boolean',
                            'nullable' => true,
                        ]
                    ),
                    true
                ),
                new AnyOf(
                    '',
                    new Field('', new IsNull()),
                    new Field('', new BoolString(), new ToBool(), new Contained([true, null]))
                ),
            ],
            'strict detailed input' => [
                new Specification\TrueFalse(
                    '',
                    new Schema(
                        [
                            'type' => 'boolean',
                            'enum' => [true, null],
                            'format' => 'rather pointless boolean',
                            'nullable' => true,
                        ]
                    ),
                    false
                ),
                new AnyOf(
                    '',
                    new Field('', new IsNull()),
                    new Field('', new IsBool(), new Contained([true, null]))
                ),
            ],
        ];
    }

    #[DataProvider('specificationsToBuild')]
    #[Test]
    public function buildTest(Specification\TrueFalse $specification, Processor $expected): void
    {
        $sut = new TrueFalse();

        $actual = $sut->build($specification);

        self::assertEquals($expected, $actual);
    }
}
