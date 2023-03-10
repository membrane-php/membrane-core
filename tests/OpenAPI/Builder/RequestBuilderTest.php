<?php

declare(strict_types=1);

namespace OpenAPI\Builder;

use Exception;
use GuzzleHttp\Psr7\ServerRequest;
use Membrane\Builder\Specification;
use Membrane\Filter\Type\ToInt;
use Membrane\OpenAPI\Builder\APIBuilder;
use Membrane\OpenAPI\Builder\Arrays;
use Membrane\OpenAPI\Builder\Numeric;
use Membrane\OpenAPI\Builder\RequestBuilder;
use Membrane\OpenAPI\Builder\Strings;
use Membrane\OpenAPI\ExtractPathParameters\PathMatcher as PathMatcherClass;
use Membrane\OpenAPI\Filter\HTTPParameters;
use Membrane\OpenAPI\Filter\PathMatcher;
use Membrane\OpenAPI\Method;
use Membrane\OpenAPI\Processor\Json;
use Membrane\OpenAPI\Processor\Request as RequestProcessor;
use Membrane\OpenAPI\Reader\OpenAPIFileReader;
use Membrane\OpenAPI\Specification\APISchema;
use Membrane\OpenAPI\Specification\APISpec;
use Membrane\OpenAPI\Specification\Request;
use Membrane\Processor;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Collection;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\Numeric\Maximum;
use Membrane\Validator\Type\IsFloat;
use Membrane\Validator\Type\IsInt;
use Membrane\Validator\Type\IsList;
use Membrane\Validator\Type\IsString;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(RequestBuilder::class)]
#[CoversClass(APIBuilder::class)]
#[UsesClass(Arrays::class)]
#[UsesClass(Numeric::class)]
#[UsesClass(Strings::class)]
#[UsesClass(HTTPParameters::class)]
#[UsesClass(PathMatcher::class)]
#[UsesClass(PathMatcherClass::class)]
#[UsesClass(Json::class)]
#[UsesClass(RequestProcessor::class)]
#[UsesClass(OpenAPIFileReader::class)]
#[UsesClass(APISchema::class)]
#[UsesClass(APISpec::class)]
#[UsesClass(\Membrane\OpenAPI\Specification\Arrays::class)]
#[UsesClass(\Membrane\OpenAPI\Specification\Numeric::class)]
#[UsesClass(\Membrane\OpenAPI\Specification\Strings::class)]
#[UsesClass(Request::class)]
#[UsesClass(ToInt::class)]
#[UsesClass(BeforeSet::class)]
#[UsesClass(Collection::class)]
#[UsesClass(Field::class)]
#[UsesClass(FieldSet::class)]
#[UsesClass(FieldName::class)]
#[UsesClass(Message::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Result::class)]
#[UsesClass(RequiredFields::class)]
#[UsesClass(Maximum::class)]
#[UsesClass(IsInt::class)]
#[UsesClass(IsList::class)]
#[UsesClass(IsString::class)]
#[UsesClass(Passes::class)]
class RequestBuilderTest extends TestCase
{
    public const DIR = __DIR__ . '/../../fixtures/OpenAPI/';

    #[Test]
    public function throwsExceptionIfParameterHasContentThatIsNotJson(): void
    {
        $specification = new Request(
            self::DIR . 'noReferences.json',
            '/requestpathexceptions',
            Method::POST
        );
        $sut = new RequestBuilder();

        self::expectExceptionObject(
            new Exception('APISpec requires application/json content')
        );

        $sut->build($specification);
    }

    #[Test]
    public function throwsExceptionIfParameterHasNoSchemaNorContent(): void
    {
        $specification = new Request(self::DIR . 'noReferences.json', '/requestpathexceptions', Method::GET);
        $sut = new RequestBuilder();

        self::expectExceptionObject(
            new Exception('A parameter MUST contain either a schema property, or a content property, but not both')
        );

        $sut->build($specification);
    }

    #[Test]
    public function supportsNumericSpecification(): void
    {
        $specification = self::createStub(Request::class);
        $sut = new RequestBuilder();

        self::assertTrue($sut->supports($specification));
    }

    #[Test]
    public function doesNotSupportNonNumericSpecification(): void
    {
        $specification = self::createStub(APISpec::class);
        $sut = new RequestBuilder();

        self::assertFalse($sut->supports($specification));
    }

    public static function dataSetsForBuild(): array
    {
        return [
            'no path params, no operation params, no requestBody' => [
                new Request(
                    self::DIR . 'noReferences.json',
                    'http://www.test.com/path/path',
                    Method::GET
                ),
                new RequestProcessor(
                    '',
                    '',
                    Method::GET,
                    [
                        'path' => new FieldSet(
                            'path',
                            new BeforeSet(
                                new PathMatcher(new PathMatcherClass('http://www.test.com/path', '/path'))
                            )
                        ),
                        'query' => new FieldSet('query', new BeforeSet(new HTTPParameters())),
                        'header' => new FieldSet('header'),
                        'cookie' => new FieldSet('cookie'),
                        'body' => new Json(new Field('requestBody', new Passes())),
                    ]

                ),
            ],
            'path param in path, no operation params, no requestBody' => [
                new Request(
                    self::DIR . 'noReferences.json',
                    'http://www.test.com/requestpathone/{id}',
                    Method::GET
                ),
                new RequestProcessor(
                    '',
                    '',
                    Method::GET,
                    [
                        'path' => new FieldSet(
                            'path',
                            new BeforeSet(
                                new PathMatcher(new PathMatcherClass('http://www.test.com', '/requestpathone/{id}')),
                                new RequiredFields('id')
                            ),
                            new Field('id', new ToInt(), new IsInt())
                        ),
                        'query' => new FieldSet('query', new BeforeSet(new HTTPParameters())),
                        'header' => new FieldSet('header'),
                        'cookie' => new FieldSet('cookie'),
                        'body' => new Json(new Field('requestBody', new Passes())),
                    ]

                ),
            ],
            'path param in path, operation param in query not required, no requestBody' => [
                new Request(self::DIR . 'noReferences.json', 'http://www.test.com/requestpathone/{id}', Method::POST),
                new RequestProcessor(
                    '',
                    '',
                    Method::POST,
                    [
                        'path' => new FieldSet(
                            'path',
                            new BeforeSet(
                                new PathMatcher(new PathMatcherClass('http://www.test.com', '/requestpathone/{id}')),
                                new RequiredFields('id')
                            ),
                            new Field('id', new ToInt(), new IsInt())
                        ),
                        'query' => new FieldSet(
                            'query',
                            new BeforeSet(new HTTPParameters()),
                            new Collection('names', new BeforeSet(new IsList()))
                        ),
                        'header' => new FieldSet('header'),
                        'cookie' => new FieldSet('cookie'),
                        'body' => new Json(new Field('requestBody', new Passes())),
                    ]
                ),
            ],
            'path param in path, operation param in query required, no requestBody' => [
                new Request(
                    self::DIR . 'noReferences.json',
                    'http://www.test.com/requestpathone/{id}',
                    Method::PUT
                ),
                new RequestProcessor(
                    '',
                    '',
                    Method::PUT,
                    [
                        'path' => new FieldSet(
                            'path',
                            new BeforeSet(
                                new PathMatcher(new PathMatcherClass('http://www.test.com', '/requestpathone/{id}')),
                                new RequiredFields('id')
                            ),
                            new Field('id', new ToInt(), new IsInt())
                        ),
                        'query' => new FieldSet(
                            'query',
                            new BeforeSet(new HTTPParameters(), new RequiredFields('name')),
                            new Field('name', new IsString())
                        ),
                        'header' => new FieldSet('header'),
                        'cookie' => new FieldSet('cookie'),
                        'body' => new Json(new Field('requestBody', new Passes())),
                    ]

                ),
            ],
            'path param in path, operation param in query with json content, required, no requestBody' => [
                new Request(
                    self::DIR . 'noReferences.json',
                    'http://www.test.com/requestpathone/{id}',
                    Method::DELETE
                ),
                new RequestProcessor(
                    '',
                    '',
                    Method::DELETE,
                    [
                        'path' => new FieldSet(
                            'path',
                            new BeforeSet(
                                new PathMatcher(new PathMatcherClass('http://www.test.com', '/requestpathone/{id}')),
                                new RequiredFields('id')
                            ),
                            new Field('id', new ToInt(), new IsInt())
                        ),
                        'query' => new FieldSet(
                            'query',
                            new BeforeSet(new HTTPParameters(), new RequiredFields('name')),
                            new Field('name', new IsString())
                        ),
                        'header' => new FieldSet('header'),
                        'cookie' => new FieldSet('cookie'),
                        'body' => new Json(new Field('requestBody', new Passes())),
                    ]
                ),
            ],
            'path param in header, no requestBody' => [
                new Request(
                    self::DIR . 'noReferences.json',
                    'http://www.test.com/requestpathtwo',
                    Method::GET
                ),
                new RequestProcessor(
                    '',
                    '',
                    Method::GET,
                    [
                        'path' => new FieldSet(
                            'path',
                            new BeforeSet(
                                new PathMatcher(new PathMatcherClass('http://www.test.com', '/requestpathtwo'))
                            )
                        ),
                        'query' => new FieldSet('query', new BeforeSet(new HTTPParameters())),
                        'header' => new FieldSet('header', new Field('id', new ToInt(), new IsInt())),
                        'cookie' => new FieldSet('cookie'),
                        'body' => new Json(new Field('requestBody', new Passes())),
                    ]

                ),
            ],
            'path param in header, operation param in cookie, no requestBody' => [
                new Request(self::DIR . 'noReferences.json', 'http://www.test.com/requestpathtwo', Method::POST),
                new RequestProcessor(
                    '',
                    '',
                    Method::POST,
                    [
                        'path' => new FieldSet(
                            'path',
                            new BeforeSet(
                                new PathMatcher(new PathMatcherClass('http://www.test.com', '/requestpathtwo'))
                            )
                        ),
                        'query' => new FieldSet('query', new BeforeSet(new HTTPParameters())),
                        'header' => new FieldSet('header', new Field('id', new ToInt(), new IsInt())),
                        'cookie' => new FieldSet('cookie', new Field('name', new IsString())),
                        'body' => new Json(new Field('requestBody', new Passes())),
                    ]

                ),
            ],
            'identical param in header and query, no requestBody' => [
                new Request(
                    self::DIR . 'noReferences.json',
                    'http://www.test.com/requestpathtwo',
                    Method::PUT
                ),
                new RequestProcessor(
                    '',
                    '',
                    Method::PUT,
                    [
                        'path' => new FieldSet(
                            'path',
                            new BeforeSet(
                                new PathMatcher(new PathMatcherClass('http://www.test.com', '/requestpathtwo'))
                            )
                        ),
                        'query' => new FieldSet(
                            'query',
                            new BeforeSet(new HTTPParameters()),
                            new Field('id', new ToInt(), new IsInt())
                        ),
                        'header' => new FieldSet('header'),
                        'cookie' => new FieldSet('cookie'),
                        'body' => new Json(new Field('requestBody', new Passes())),
                    ]

                ),
            ],
            'same param in path and operation with different types, no requestBody' => [
                new Request(
                    self::DIR . 'noReferences.json',
                    'http://www.test.com/requestpathtwo',
                    Method::DELETE
                ),
                new RequestProcessor(
                    '',
                    '',
                    Method::DELETE,
                    [
                        'path' => new FieldSet(
                            'path',
                            new BeforeSet(
                                new PathMatcher(new PathMatcherClass('http://www.test.com', '/requestpathtwo'))
                            )
                        ),
                        'query' => new FieldSet('query', new BeforeSet(new HTTPParameters())),
                        'header' => new FieldSet('header', new Field('id', new IsString())),
                        'cookie' => new FieldSet('cookie'),
                        'body' => new Json(new Field('requestBody', new Passes())),
                    ]

                ),
            ],
            'requestBody param' => [
                new Request(
                    self::DIR . 'noReferences.json',
                    'http://www.test.com/requestbodypath',
                    Method::GET
                ),
                new RequestProcessor(
                    '',
                    '',
                    Method::GET,
                    [
                        'path' => new FieldSet(
                            'path',
                            new BeforeSet(
                                new PathMatcher(new PathMatcherClass('http://www.test.com', '/requestbodypath'))
                            )
                        ),
                        'query' => new FieldSet('query', new BeforeSet(new HTTPParameters())),
                        'header' => new FieldSet('header'),
                        'cookie' => new FieldSet('cookie'),
                        'body' => new Json(new Field('requestBody', new IsInt())),
                    ]

                ),
            ],
            'operation param in query, requestBody param' => [
                new Request(
                    self::DIR . 'noReferences.json',
                    'http://www.test.com/requestbodypath',
                    Method::POST
                ),
                new RequestProcessor(
                    '',
                    '',
                    Method::POST,
                    [
                        'path' => new FieldSet(
                            'path',
                            new BeforeSet(
                                new PathMatcher(new PathMatcherClass('http://www.test.com', '/requestbodypath'))
                            )
                        ),
                        'query' => new FieldSet(
                            'query',
                            new BeforeSet(new HTTPParameters()),
                            new Field('id', new IsString())
                        ),
                        'header' => new FieldSet('header'),
                        'cookie' => new FieldSet('cookie'),
                        'body' => new Json(new Field('requestBody', new IsInt())),
                    ]

                ),
            ],
            'path param in path, operation param in query, header, cookie, requestBody param' => [
                new Request(self::DIR . 'noReferences.json', 'http://www.test.com/requestbodypath/{id}', Method::GET),
                new RequestProcessor(
                    '',
                    '',
                    Method::GET,
                    [
                        'path' => new FieldSet(
                            'path',
                            new BeforeSet(
                                new PathMatcher(new PathMatcherClass('http://www.test.com', '/requestbodypath/{id}')),
                                new RequiredFields('id')
                            ),
                            new Field('id', new ToInt(), new IsInt())
                        ),
                        'query' => new FieldSet(
                            'query',
                            new BeforeSet(new HTTPParameters()),
                            new Field('name', new IsString())
                        ),
                        'header' => new FieldSet('header', new Field('species', new IsString())),
                        'cookie' => new FieldSet('cookie', new Field('subspecies', new IsString())),
                        'body' => new Json(new Field('requestBody', new IsFloat())),
                    ]
                ),
            ],
        ];
    }

    #[DataProvider('dataSetsForBuild')]
    #[Test]
    public function buildTest(Specification $spec, Processor $expected): void
    {
        $sut = new RequestBuilder();

        $actual = $sut->build($spec);

        self::assertEquals($expected, $actual);
    }


    public static function dataSetsForDocExamples(): array
    {
        $api = self::DIR . '/docs/petstore.yaml';
        $expanded = self::DIR . '/docs/petstore-expanded.json';

        return [
            'petstore /pets get, minimal (valid)' => [
                new Request($api, 'http://petstore.swagger.io/v1/pets', Method::GET),
                new ServerRequest('get', 'http://petstore.swagger.io/v1/pets'),
                Result::valid(
                    [
                        'request' => ['method' => 'get', 'operationId' => 'listPets'],
                        'path' => [],
                        'query' => [],
                        'header' => [],
                        'cookie' => [],
                        'body' => '',
                    ],
                ),
            ],
            'petstore /pets/{petid} get, minimal (valid)' => [
                new Request($api, 'http://petstore.swagger.io/v1/pets/Blink', Method::GET),
                new ServerRequest('get', 'http://petstore.swagger.io/v1/pets/Harley'),
                Result::valid(
                    [
                        'request' => ['method' => 'get', 'operationId' => 'showPetById'],
                        'path' => ['petId' => 'Harley'],
                        'query' => [],
                        'header' => [],
                        'cookie' => [],
                        'body' => '',
                    ],
                ),
            ],
            'petstore expanded /pets get (invalid)' => [
                new Request(
                    $expanded,
                    'http://petstore.swagger.io/api/pets',
                    Method::GET
                ),
                new ServerRequest('get', 'http://petstore.swagger.io/api/pets?limit=five'),
                Result::invalid(
                    [
                        'request' => ['method' => 'get', 'operationId' => 'findPets'],
                        'path' => [],
                        'query' => ['limit' => 'five'],
                        'header' => [],
                        'cookie' => [],
                        'body' => '',
                    ],
                    new MessageSet(
                        new FieldName('limit', '', 'query'),
                        new Message('ToInt filter only accepts numeric strings', [])
                    )
                ),
            ],
            'petstore expanded /pets get, minimal (valid)' => [
                new Request(
                    $expanded,
                    'http://petstore.swagger.io/api/pets',
                    Method::GET
                ),
                new ServerRequest('get', 'http://petstore.swagger.io/api/pets?limit=5&tags[]=cat&tags[]=tabby'),
                Result::valid(
                    [
                        'request' => ['method' => 'get', 'operationId' => 'findPets'],
                        'path' => [],
                        'query' => ['limit' => 5, 'tags' => ['cat', 'tabby']],
                        'header' => [],
                        'cookie' => [],
                        'body' => '',
                    ]
                ),
            ],
        ];
    }

    #[DataProvider('dataSetsForDocExamples')]
    #[Test]
    public function docsTest(
        Request $specification,
        array|ServerRequestInterface $serverRequest,
        Result $expected
    ): void {
        $sut = new RequestBuilder();

        $processor = $sut->build($specification);

        $actual = $processor->process(new FieldName(''), $serverRequest);

        self::assertEquals($expected, $actual);
        self::assertSame($expected->value, $actual->value);
    }
}
