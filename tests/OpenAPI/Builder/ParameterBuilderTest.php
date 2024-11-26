<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Builder;

use Membrane\Builder\Specification;
use Membrane\Filter\String\Explode;
use Membrane\OpenAPI\Builder as OpenAPIBuilder;
use Membrane\OpenAPI\Filter\FormatStyle\SpaceDelimited;
use Membrane\OpenAPI\Specification as OpenAPISpecification;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30};
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\Processor;
use Membrane\Validator\Type\IsInt;
use Membrane\Validator\Type\IsList;
use Membrane\Validator\Type\IsString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpenAPIBuilder\ParameterBuilder::class)]
#[CoversClass(OpenAPIBuilder\APIBuilder::class)]
#[UsesClass(OpenAPIBuilder\Arrays::class)]
#[UsesClass(OpenAPIBuilder\Numeric::class)]
#[UsesClass(OpenAPIBuilder\Strings::class)]
#[UsesClass(OpenAPISpecification\Parameter::class)]
#[UsesClass(OpenAPISpecification\APISchema::class)]
#[UsesClass(OpenAPISpecification\Arrays::class)]
#[UsesClass(OpenAPISpecification\Numeric::class)]
#[UsesClass(OpenAPISpecification\Strings::class)]
#[UsesClass(Explode::class)]
#[UsesClass(Processor\BeforeSet::class)]
#[UsesClass(Processor\Collection::class)]
#[UsesClass(Processor\Field::class)]
class ParameterBuilderTest extends TestCase
{
    private OpenAPIBuilder\ParameterBuilder $sut;

    protected function setUp(): void
    {
        $this->sut = new OpenAPIBuilder\ParameterBuilder();
    }

    #[Test, TestDox('It does not support any Specifications other than Parameter')]
    public function doesNotSupportSpecificationsOtherThanParameter(): void
    {
        self::assertFalse($this->sut->supports(self::createStub(Specification::class)));
    }

    #[Test, TestDox('It supports the Parameter Specification')]
    public function supportsTheParameterSpecification(): void
    {
        self::assertTrue($this->sut->supports(self::createStub(OpenAPISpecification\Parameter::class)));
    }

    public static function provideParameterSpecificationsToBuildFrom(): array
    {
        return [
            [
                OpenAPIVersion::Version_3_0,
                new V30\Parameter(new Identifier('test'), new Partial\Parameter(
                    name: 'id',
                    in: 'path',
                    required: true,
                    schema: new Partial\Schema(type: 'integer'),
                )),
                new Processor\Field('id', new IsInt()),
            ],
            [
                OpenAPIVersion::Version_3_0,
                new V30\Parameter(new Identifier('test'), new Partial\Parameter(
                    name: 'tags',
                    in: 'query',
                    style: 'spaceDelimited',
                    explode: false,
                    schema: new Partial\Schema(
                        type: 'array',
                        items: new Partial\Schema(type: 'string'),
                    ),
                )),
                new Processor\Collection(
                    'tags',
                    new Processor\BeforeSet(new SpaceDelimited(), new IsList()),
                    new Processor\Field('', new IsString())
                ),
            ],
        ];
    }

    #[Test, TestDox('It Builds a Processor that can validate against the Parameter Specification')]
    #[DataProvider('provideParameterSpecificationsToBuildFrom')]
    public function itBuildsProcessorsForParameters(
        OpenAPIVersion $openApiVersion,
        V30\Parameter|V31\Parameter $parameter,
        Processor $expectedProcessor
    ): void {
        $actualProcessor = $this->sut->build(new OpenAPISpecification\Parameter(
            $openApiVersion,
            $parameter
        ));

        self::assertEquals($expectedProcessor, $actualProcessor);
    }
}
