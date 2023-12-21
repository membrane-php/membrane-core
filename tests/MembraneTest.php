<?php

declare(strict_types=1);

namespace Membrane\Tests;

use Generator;
use Membrane\Attribute\ClassWithAttributes;
use Membrane\Attribute;
use Membrane\Filter\String\Explode;
use Membrane\OpenAPI;
use Membrane\Builder\Specification;
use Membrane\Membrane;
use Membrane\OpenAPI\Specification\Request;
use Membrane\OpenAPI\Specification\Response;
use Membrane\OpenAPIReader\Method;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Collection;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
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

use function React\Promise\map;

#[CoversClass(Membrane::class)]
#[UsesClass(Attribute\Builder::class)]
#[UsesClass(FieldSet::class)]
#[UsesClass(FieldName::class)]
#[UsesClass(Result::class)]
#[UsesClass(OpenAPI\Builder\APIBuilder::class)]
#[UsesClass(OpenAPI\Builder\Arrays::class)]
#[UsesClass(OpenAPI\Builder\Numeric::class)]
#[UsesClass(OpenAPI\Builder\Objects::class)]
#[UsesClass(OpenAPI\Builder\OpenAPIRequestBuilder::class)]
#[UsesClass(OpenAPI\Builder\OpenAPIResponseBuilder::class)]
#[UsesClass(OpenAPI\Builder\ParameterBuilder::class)]
#[UsesClass(OpenAPI\Builder\RequestBuilder::class)]
#[UsesClass(OpenAPI\Builder\ResponseBuilder::class)]
#[UsesClass(OpenAPI\Builder\Strings::class)]
#[UsesClass(OpenAPI\ExtractPathParameters\PathMatcher::class)]
#[UsesClass(OpenAPI\Processor\AllOf::class)]
#[UsesClass(OpenAPI\Specification\APISchema::class)]
#[UsesClass(OpenAPI\Specification\Arrays::class)]
#[UsesClass(OpenAPI\Specification\Numeric::class)]
#[UsesClass(OpenAPI\Specification\Objects::class)]
#[UsesClass(OpenAPI\Specification\OpenAPIRequest::class)]
#[UsesClass(OpenAPI\Specification\OpenAPIResponse::class)]
#[UsesClass(OpenAPI\Specification\Parameter::class)]
#[UsesClass(OpenAPI\Specification\Strings::class)]
#[UsesClass(BeforeSet::class)]
#[UsesClass(Collection::class)]
#[UsesClass(Field::class)]
#[UsesClass(OpenAPI\Processor\Request::class)]
#[UsesClass(Message::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Explode::class)]
#[UsesClass(OpenAPI\Filter\PathMatcher::class)]
#[UsesClass(RequiredFields::class)]
#[UsesClass(IsList::class)]
class MembraneTest extends TestCase
{
    private const __FIXTURES__ = __DIR__ . '/fixtures/';

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
}
