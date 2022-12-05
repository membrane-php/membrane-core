<?php

declare(strict_types=1);

namespace OpenAPI\Builder;


use cebe\openapi\spec\Schema;
use Membrane\Filter\Type\ToBool;
use Membrane\OpenAPI\Builder\TrueFalse;
use Membrane\OpenAPI\Processor\AnyOf;
use Membrane\OpenAPI\Specification;
use Membrane\Processor;
use Membrane\Processor\Field;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\Type\IsBool;
use Membrane\Validator\Type\IsNull;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Builder\TrueFalse
 * @covers \Membrane\OpenAPI\Builder\APIBuilder
 * @uses   \Membrane\OpenAPI\Processor\AnyOf
 * @uses   \Membrane\OpenAPI\Specification\TrueFalse
 * @uses   \Membrane\Processor\Field
 * @uses   \Membrane\Validator\Collection\Contained
 */
class TrueFalseTest extends TestCase
{

    public function specificationsToSupport(): array
    {
        return [
            [
                new class() implements \Membrane\Builder\Specification {
                },
                false,
            ],
            [self::createStub(Specification\TrueFalse::class), true],
        ];
    }

    /**
     * @test
     * @dataProvider specificationsToSupport
     */
    public function supportsTest(\Membrane\Builder\Specification $specification, bool $expected): void
    {
        $sut = new TrueFalse();

        self::assertSame($expected, $sut->supports($specification));
    }

    public function specificationsToBuild(): array
    {
        return [
            'non-strict input' => [
                new Specification\TrueFalse('', new Schema(['type' => 'boolean']), false),
                new Field('', new ToBool(), new IsBool()),
            ],
            'strict input' => [
                new Specification\TrueFalse('', new Schema(['type' => 'boolean']), true),
                new Field('', new IsBool()),
            ],
            'detailed input' => [
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
                    new Field('', new ToBool(), new IsBool(), new Contained([true, null]))
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider specificationsToBuild
     */
    public function buildTest(Specification\TrueFalse $specification, Processor $expected): void
    {
        $sut = new TrueFalse();

        $actual = $sut->build($specification);

        self::assertEquals($expected, $actual);
    }
}
