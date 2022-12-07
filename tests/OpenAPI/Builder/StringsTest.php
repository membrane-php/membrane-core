<?php

declare(strict_types=1);

namespace OpenAPI\Builder;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Builder\Strings;
use Membrane\OpenAPI\Processor\AnyOf;
use Membrane\OpenAPI\Specification;
use Membrane\Processor;
use Membrane\Processor\Field;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\String\DateString;
use Membrane\Validator\String\Length;
use Membrane\Validator\String\Regex;
use Membrane\Validator\Type\IsNull;
use Membrane\Validator\Type\IsString;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Builder\Strings
 * @covers \Membrane\OpenAPI\Builder\APIBuilder
 * @uses   \Membrane\OpenAPI\Processor\AnyOf
 * @uses   \Membrane\OpenAPI\Specification\Strings
 * @uses   \Membrane\Processor\Field
 * @uses   \Membrane\Validator\Collection\Contained
 * @uses   \Membrane\Validator\String\DateString
 * @uses   \Membrane\Validator\String\Length
 * @uses   \Membrane\Validator\String\Regex
 */
class StringsTest extends TestCase
{
    public function specificationsToSupport(): array
    {
        return [
            [
                new class() implements \Membrane\Builder\Specification {
                },
                false,
            ],
            [self::createStub(Specification\Strings::class), true],
        ];
    }

    /**
     * @test
     * @dataProvider specificationsToSupport
     */
    public function supportsTest(\Membrane\Builder\Specification $specification, bool $expected): void
    {
        $sut = new Strings();

        self::assertSame($expected, $sut->supports($specification));
    }

    public function specificationsToBuild(): array
    {
        return [
            'minimum input' => [
                new Specification\Strings('', new Schema(['type' => 'string'])),
                new Field('', new IsString()),
            ],
            'date input' => [
                new Specification\Strings('', new Schema(['type' => 'string', 'format' => 'date',])),
                new Field('', new IsString(), new DateString('Y-m-d')),
            ],
            'date-time input' => [
                new Specification\Strings('', new Schema(['type' => 'string', 'format' => 'date-time',])),
                new Field('', new IsString(), new DateString('Y-m-d\TH:i:sP')),
            ],
            'detailed input' => [
                new Specification\Strings(
                    '',
                    new Schema(
                        [
                            'type' => 'string',
                            'maxLength' => 100,
                            'minLength' => 0,
                            'pattern' => '#.+#',
                            'format' => 'date',
                            'enum' => ['1970/01/01', null],
                            'nullable' => true,
                        ]
                    )
                ),
                new AnyOf(
                    '',
                    new Field('', new IsNull()),
                    new Field(
                        '',
                        new IsString(),
                        new Contained(['1970/01/01', null]),
                        new DateString('Y-m-d'),
                        new Length(0, 100),
                        new Regex('#.+#')
                    )
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider specificationsToBuild
     */
    public function buildTest(Specification\Strings $specification, Processor $expected): void
    {
        $sut = new Strings();

        $actual = $sut->build($specification);

        self::assertEquals($expected, $actual);
    }
}
