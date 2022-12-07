<?php

declare(strict_types=1);

namespace OpenAPI\Builder;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Builder\Objects;
use Membrane\OpenAPI\Processor\AnyOf;
use Membrane\OpenAPI\Specification;
use Membrane\Processor;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\Type\IsArray;
use Membrane\Validator\Type\IsInt;
use Membrane\Validator\Type\IsNull;
use Membrane\Validator\Type\IsString;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Builder\Objects
 * @covers \Membrane\OpenAPI\Builder\APIBuilder
 * @uses   \Membrane\OpenAPI\Builder\Numeric
 * @uses   \Membrane\OpenAPI\Builder\Strings
 * @uses   \Membrane\OpenAPI\Processor\AnyOf
 * @uses   \Membrane\OpenAPI\Specification\APISchema
 * @uses   \Membrane\OpenAPI\Specification\Numeric
 * @uses   \Membrane\OpenAPI\Specification\Strings
 * @uses   \Membrane\Processor\BeforeSet
 * @uses   \Membrane\Processor\Field
 * @uses   \Membrane\Processor\FieldSet
 * @uses   \Membrane\Validator\Collection\Contained
 * @uses   \Membrane\Validator\FieldSet\RequiredFields
 */
class ObjectsTest extends TestCase
{
    public function specificationsToSupport(): array
    {
        return [
            [
                new class() implements \Membrane\Builder\Specification {
                },
                false,
            ],
            [self::createStub(Specification\Objects::class), true],
        ];
    }

    /**
     * @test
     * @dataProvider specificationsToSupport
     */
    public function supportsTest(\Membrane\Builder\Specification $specification, bool $expected): void
    {
        $sut = new Objects();

        self::assertSame($expected, $sut->supports($specification));
    }

    public function specificationsToBuild(): array
    {
        return [
            'minimum input' => [
                new Specification\Objects('', new Schema(['type' => 'object'])),
                new FieldSet('', new BeforeSet(new IsArray())),
            ],
            'detailed input' => [
                new Specification\Objects(
                    '',
                    new Schema(
                        [
                            'type' => 'object',
                            'properties' => [
                                'id' => new Schema(['type' => 'integer']),
                                'name' => new Schema(['type' => 'string']),
                            ],
                            'required' => ['id', 'name'],
                            'format' => 'pet',
                            'enum' => [['id' => 5, 'name' => 'Blink'], null],
                            'nullable' => true,
                        ]
                    )
                ),
                new AnyOf(
                    '',
                    new Field('', new IsNull()),
                    new FieldSet(
                        '',
                        new BeforeSet(
                            new IsArray(),
                            new Contained([['id' => 5, 'name' => 'Blink'], null]),
                            new RequiredFields('id', 'name')
                        ),
                        new Field('id', new IsInt()),
                        new Field('name', new IsString())
                    )
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider specificationsToBuild
     */
    public function buildTest(Specification\Objects $specification, Processor $expected): void
    {
        $sut = new Objects();

        $actual = $sut->build($specification);

        self::assertEquals($expected, $actual);
    }
}
