<?php

declare(strict_types=1);

namespace Membrane\Tests;

use Generator;
use Membrane\Attribute\Builder as AttributeBuilder;
use Membrane\Attribute\ClassWithAttributes;
use Membrane\Builder\Specification;
use Membrane\Filter\String\Explode;
use Membrane\Membrane;
use Membrane\OpenAPI\ExtractPathParameters\PathMatcher;
use Membrane\OpenAPI\Specification\Request;
use Membrane\OpenAPI\Specification\Response;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
use Membrane\Processor;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Tests\Fixtures\Attribute\EmptyClass;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\Type\IsList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Membrane\OpenAPI\Builder as Builder;
use Membrane\OpenAPI\Specification as OpenAPISpecification;

#[CoversClass(Membrane::class)]
#[UsesClass(AttributeBuilder::class)]
#[UsesClass(Builder\APIBuilder::class)]
#[UsesClass(Builder\Arrays::class)]
#[UsesClass(Builder\Numeric::class)]
#[UsesClass(Builder\Strings::class)]
#[UsesClass(Builder\Objects::class)]
#[UsesClass(Builder\ParameterBuilder::class)]
#[UsesClass(Builder\OpenAPIRequestBuilder::class)]
#[UsesClass(Builder\OpenAPIResponseBuilder::class)]
#[UsesClass(Builder\RequestBuilder::class)]
#[UsesClass(Builder\ResponseBuilder::class)]
#[UsesClass(OpenAPISpecification\Objects::class)]
#[UsesClass(OpenAPISpecification\Parameter::class)]
#[UsesClass(OpenAPISpecification\OpenAPIRequest::class)]
#[UsesClass(OpenAPISpecification\OpenAPIResponse::class)]
#[UsesClass(OpenAPISpecification\APISchema::class)]
#[UsesClass(OpenAPISpecification\Arrays::class)]
#[UsesClass(OpenAPISpecification\Numeric::class)]
#[UsesClass(OpenAPISpecification\Strings::class)]
#[UsesClass(Processor\FieldSet::class)]
#[UsesClass(Processor\BeforeSet::class)]
#[UsesClass(Processor\Collection::class)]
#[UsesClass(Processor\FieldSet::class)]
#[UsesClass(Processor\Field::class)]
#[UsesClass(\Membrane\OpenAPI\Processor\Request::class)]
#[UsesClass(\Membrane\OpenAPI\Processor\AllOf::class)]
#[UsesClass(Result::class)]
#[UsesClass(FieldName::class)]
#[UsesClass(Message::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Explode::class)]
#[UsesClass(PathMatcher::class)]
#[UsesClass(\Membrane\OpenAPI\Filter\PathMatcher::class)]
#[UsesClass(IsList::class)]
#[UsesClass(RequiredFields::class)]

class MembraneTest extends TestCase
{
    private const __FIXTURES__ = __DIR__ . '/fixtures/';

    #[Test, DataProvider('provideSpecifications')]
    public function itMayAllowDefaultBuilders(
        mixed $value,
        Specification $specification
    ): void {
        $sut = new Membrane();

        self::assertInstanceOf(
            Result::class,
            $sut->process($value, $specification)
        );
    }

    #[Test, DataProvider('provideSpecifications')]
    public function itMayDisallowDefaultBuilders(
        mixed $value,
        Specification $specification
    ): void {
        $sut = Membrane::withoutDefaults();

        self::expectException(RuntimeException::class);

        $sut->process($value, $specification);
    }

    public static function provideSpecifications(): Generator
    {
        yield 'Attributes' => [
            new class {
            },
            new ClassWithAttributes(EmptyClass::class),
        ];
        yield 'Request' => [
            new \GuzzleHttp\Psr7\Request('get', ''),
            new Request(self::__FIXTURES__ . 'OpenAPI/hatstore.json', '/hats', Method::GET),
        ];
        yield 'Response' => [
            new \GuzzleHttp\Psr7\Response(),
            new Response(self::__FIXTURES__ . 'OpenAPI/hatstore.json', '/hats', Method::GET, '200'),
        ];
    }
}
