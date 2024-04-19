<?php

declare(strict_types=1);

namespace Membrane\Tests;

use Generator;
use Membrane\Attribute\ClassWithAttributes;
use Membrane\Builder\Specification;
use Membrane\Membrane;
use Membrane\OpenAPI\Specification\Request;
use Membrane\OpenAPI\Specification\Response;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
use Membrane\Result\Result;
use Membrane\Tests\Fixtures\Attribute\EmptyClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(Membrane::class)]
#[UsesClass(\Membrane\Attribute\Builder::class)]
#[UsesClass(\Membrane\Filter\String\Explode::class)]
#[UsesClass(\Membrane\OpenAPI\Builder\APIBuilder::class)]
#[UsesClass(\Membrane\OpenAPI\Builder\Arrays::class)]
#[UsesClass(\Membrane\OpenAPI\Builder\Numeric::class)]
#[UsesClass(\Membrane\OpenAPI\Builder\Objects::class)]
#[UsesClass(\Membrane\OpenAPI\Builder\OpenAPIRequestBuilder::class)]
#[UsesClass(\Membrane\OpenAPI\Builder\OpenAPIResponseBuilder::class)]
#[UsesClass(\Membrane\OpenAPI\Builder\ParameterBuilder::class)]
#[UsesClass(\Membrane\OpenAPI\Builder\RequestBuilder::class)]
#[UsesClass(\Membrane\OpenAPI\Builder\ResponseBuilder::class)]
#[UsesClass(\Membrane\OpenAPI\Builder\Strings::class)]
#[UsesClass(\Membrane\OpenAPI\ExtractPathParameters\PathMatcher::class)]
#[UsesClass(\Membrane\OpenAPI\Filter\PathMatcher::class)]
#[UsesClass(\Membrane\OpenAPI\Processor\Request::class)]
#[UsesClass(\Membrane\OpenAPI\Processor\AllOf::class)]
#[UsesClass(\Membrane\OpenAPI\Specification\APISchema::class)]
#[UsesClass(\Membrane\OpenAPI\Specification\Arrays::class)]
#[UsesClass(\Membrane\OpenAPI\Specification\Numeric::class)]
#[UsesClass(\Membrane\OpenAPI\Specification\Objects::class)]
#[UsesClass(\Membrane\OpenAPI\Specification\OpenAPIRequest::class)]
#[UsesClass(\Membrane\OpenAPI\Specification\OpenAPIResponse::class)]
#[UsesClass(\Membrane\OpenAPI\Specification\Parameter::class)]
#[UsesClass(\Membrane\OpenAPI\Specification\Strings::class)]
#[UsesClass(\Membrane\Processor\BeforeSet::class)]
#[UsesClass(\Membrane\Processor\Collection::class)]
#[UsesClass(\Membrane\Processor\Field::class)]
#[UsesClass(\Membrane\Processor\FieldSet::class)]
#[UsesClass(\Membrane\Result\FieldName::class)]
#[UsesClass(\Membrane\Result\Message::class)]
#[UsesClass(\Membrane\Result\MessageSet::class)]
#[UsesClass(\Membrane\Result\Result::class)]
#[UsesClass(\Membrane\Validator\FieldSet\RequiredFields::class)]
#[UsesClass(\Membrane\Validator\Type\IsList::class)]
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
