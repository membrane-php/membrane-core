<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Builder;

use GuzzleHttp\Psr7\ServerRequest;
use Membrane\Builder\Specification;
use Membrane\Filter\String\Explode;
use Membrane\Filter\String\Implode;
use Membrane\Filter\Type\ToInt;
use Membrane\OpenAPI\Builder\APIBuilder;
use Membrane\OpenAPI\Builder\Arrays;
use Membrane\OpenAPI\Builder\Numeric;
use Membrane\OpenAPI\Builder\OpenAPIRequestBuilder;
use Membrane\OpenAPI\Builder\ParameterBuilder;
use Membrane\OpenAPI\Builder\RequestBuilder;
use Membrane\OpenAPI\Builder\Strings;
use Membrane\OpenAPI\ContentType;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\ExtractPathParameters\PathMatcher as PathMatcherClass;
use Membrane\OpenAPI\Filter\FormatStyle\Form;
use Membrane\OpenAPI\Filter\PathMatcher;
use Membrane\OpenAPI\Filter\QueryStringToArray;
use Membrane\OpenAPI\Processor\Request as RequestProcessor;
use Membrane\OpenAPI\Specification\APISchema;
use Membrane\OpenAPI\Specification\OpenAPIRequest;
use Membrane\OpenAPI\Specification\Parameter;
use Membrane\OpenAPI\Specification\Request;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\Reader;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
use Membrane\Processor;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Collection;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
use Membrane\Renderer\HumanReadable;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Tests\MembraneTestCase;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\Numeric\Maximum;
use Membrane\Validator\String\IntString;
use Membrane\Validator\Type\IsFloat;
use Membrane\Validator\Type\IsInt;
use Membrane\Validator\Type\IsList;
use Membrane\Validator\Type\IsString;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(RequestBuilder::class)]
#[CoversClass(CannotProcessSpecification::class)]
#[CoversClass(CannotProcessOpenAPI::class)]
#[UsesClass(HumanReadable::class)]
#[UsesClass(APIBuilder::class)]
#[UsesClass(OpenAPIRequestBuilder::class)]
#[UsesClass(OpenAPIRequest::class)]
#[UsesClass(Request::class)]
#[UsesClass(Arrays::class)]
#[UsesClass(Numeric::class)]
#[UsesClass(Strings::class)]
#[UsesClass(Parameter::class)]
#[UsesClass(ParameterBuilder::class)]
#[UsesClass(ParameterBuilder::class)]
#[UsesClass(QueryStringToArray::class)]
#[UsesClass(Form::class)]
#[UsesClass(PathMatcher::class)]
#[UsesClass(PathMatcherClass::class)]
#[UsesClass(RequestProcessor::class)]
#[UsesClass(APISchema::class)]
#[UsesClass(\Membrane\OpenAPI\Specification\Arrays::class)]
#[UsesClass(\Membrane\OpenAPI\Specification\Numeric::class)]
#[UsesClass(\Membrane\OpenAPI\Specification\Strings::class)]
#[UsesClass(Parameter::class)]
#[UsesClass(Request::class)]
#[UsesClass(ToInt::class)]
#[UsesClass(Explode::class)]
#[UsesClass(Implode::class)]
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
#[UsesClass(IntString::class)]
#[UsesClass(IsList::class)]
#[UsesClass(IsString::class)]
#[UsesClass(Passes::class)]
#[UsesClass(IntString::class)]
#[UsesClass(Explode::class)]
#[UsesClass(ContentType::class)]
class RequestBuilderTest extends MembraneTestCase
{
    public const DIR = __DIR__ . '/../../fixtures/OpenAPI/';

    #[Test, TestDox('It currently only supports application/json content')]
    public function throwsExceptionIfParameterHasContentThatIsNotJson(): void
    {
        $openAPIFilePath = self::DIR . 'noReferences.json';
        $specification = new Request($openAPIFilePath, '/requestpathexceptions', Method::POST);
        $sut = new RequestBuilder();

        $openApi = (new Reader([OpenAPIVersion::Version_3_0]))
            ->readFromAbsoluteFilePath($openAPIFilePath);

        $mediaTypes = array_keys($openApi->paths->getPath('/requestpathexceptions')->post->parameters[0]->content);

        self::expectExceptionObject(CannotProcessOpenAPI::unsupportedMediaTypes(...$mediaTypes));

        $sut->build($specification);
    }

    #[Test, TestDox('It will support the Request Specification')]
    public function supportsRequestSpecification(): void
    {
        $specification = self::createStub(Request::class);
        $sut = new RequestBuilder();

        self::assertTrue($sut->supports($specification));
    }

    #[Test, TestDox('It will not support any Specifications other than Request')]
    public function doesNotSupportSpecificationsOtherThanRequest(): void
    {
        $specification = self::createStub(\Membrane\Builder\Specification::class);
        $sut = new RequestBuilder();

        self::assertFalse($sut->supports($specification));
    }

    #[Test, TestDox('Throws an exception if it cannot find a matching path in the OpenAPI spec provided')]
    public function throwsExceptionIfPathCannotBeFound(): void
    {
        self::expectExceptionObject(CannotProcessSpecification::pathNotFound('noReferences.json', '/nonexistentpath'));

        $specification = new Request(
            self::DIR . 'noReferences.json',
            'http://www.test.com/nonexistentpath',
            Method::GET
        );

        (new RequestBuilder())->build($specification);
    }

    public static function dataSetsForBuild(): array
    {
        return [
            'Request: no path params, no operation params, no requestBody' => [
                new Request(
                    self::DIR . 'noReferences.json',
                    'http://www.test.com/path/path',
                    Method::GET
                ),
                new RequestProcessor(
                    '',
                    'path-get',
                    Method::GET,
                    [
                        'path' => new FieldSet(
                            'path',
                            new BeforeSet(
                                new PathMatcher(new PathMatcherClass('http://www.test.com/path', '/path'))
                            )
                        ),
                        'query' => new FieldSet('query', new BeforeSet(new QueryStringToArray([]))),
                        'header' => new FieldSet('header'),
                        'cookie' => new FieldSet('cookie'),
                        'body' => new Field('requestBody', new Passes()),
                    ]
                ),
            ],
            'Request: path param in path, no operation params, no requestBody' => [
                new Request(
                    self::DIR . 'noReferences.json',
                    'http://www.test.com/requestpathone/{id}',
                    Method::GET
                ),
                new RequestProcessor(
                    '',
                    'requestpathone-get',
                    Method::GET,
                    [
                        'path' => new FieldSet(
                            'path',
                            new BeforeSet(
                                new PathMatcher(new PathMatcherClass('http://www.test.com', '/requestpathone/{id}')),
                                new RequiredFields('id')
                            ),
                            new Field('id', new IntString(), new ToInt())
                        ),
                        'query' => new FieldSet('query', new BeforeSet(new QueryStringToArray([]))),
                        'header' => new FieldSet('header'),
                        'cookie' => new FieldSet('cookie'),
                        'body' => new Field('requestBody', new Passes()),
                    ]
                ),
            ],
            'Request: path param in path, operation param in query not required, no requestBody' => [
                new Request(
                    self::DIR . 'noReferences.json',
                    'http://www.test.com/requestpathone/{id}',
                    Method::POST
                ),
                new RequestProcessor(
                    '',
                    'requestpathone-post',
                    Method::POST,
                    [
                        'path' => new FieldSet(
                            'path',
                            new BeforeSet(
                                new PathMatcher(new PathMatcherClass('http://www.test.com', '/requestpathone/{id}')),
                                new RequiredFields('id')
                            ),
                            new Field('id', new IntString(), new ToInt())
                        ),
                        'query' => new FieldSet(
                            'query',
                            new BeforeSet(new QueryStringToArray(['age' => ['style' => 'form' , 'explode' => true]])),
                            new Field('age', new Form('integer', false), new IntString(), new ToInt())
                        ),
                        'header' => new FieldSet('header'),
                        'cookie' => new FieldSet('cookie'),
                        'body' => new Field('requestBody', new Passes()),
                    ]
                ),
            ],
            'Request: path param in path, operation param in query required, no requestBody' => [
                new Request(
                    self::DIR . 'noReferences.json',
                    'http://www.test.com/requestpathone/{id}',
                    Method::PUT
                ),
                new RequestProcessor(
                    '',
                    'requestpathone-put',
                    Method::PUT,
                    [
                        'path' => new FieldSet(
                            'path',
                            new BeforeSet(
                                new PathMatcher(new PathMatcherClass('http://www.test.com', '/requestpathone/{id}')),
                                new RequiredFields('id')
                            ),
                            new Field('id', new IntString(), new ToInt())
                        ),
                        'query' => new FieldSet(
                            'query',
                            new BeforeSet(
                                new QueryStringToArray(
                                    ['name' => ['style' => 'form' , 'explode' => true]]
                                ),
                                new RequiredFields('name')
                            ),
                            new Field('name', new Form('string', false), new IsString())
                        ),
                        'header' => new FieldSet('header'),
                        'cookie' => new FieldSet('cookie'),
                        'body' => new Field('requestBody', new Passes()),
                    ]
                ),
            ],
            'Request: path param in path, operation param in query with json content, required, no requestBody' => [
                new Request(
                    self::DIR . 'noReferences.json',
                    'http://www.test.com/requestpathone/{id}',
                    Method::DELETE
                ),
                new RequestProcessor(
                    '',
                    'requestpathone-delete',
                    Method::DELETE,
                    [
                        'path' => new FieldSet(
                            'path',
                            new BeforeSet(
                                new PathMatcher(new PathMatcherClass('http://www.test.com', '/requestpathone/{id}')),
                                new RequiredFields('id')
                            ),
                            new Field('id', new IntString(), new ToInt())
                        ),
                        'query' => new FieldSet(
                            'query',
                            new BeforeSet(
                                new QueryStringToArray(['name' => ['style' => 'form', 'explode' => true]]),
                                new RequiredFields('name')
                            ),
                            new Field('name', new Form('string', false), new IsString())
                        ),
                        'header' => new FieldSet('header'),
                        'cookie' => new FieldSet('cookie'),
                        'body' => new Field('requestBody', new Passes()),
                    ]
                ),
            ],
            'Request: path param in header, no requestBody' => [
                new Request(
                    self::DIR . 'noReferences.json',
                    'http://www.test.com/requestpathtwo',
                    Method::GET
                ),
                new RequestProcessor(
                    '',
                    'requestpathtwo-get',
                    Method::GET,
                    [
                        'path' => new FieldSet(
                            'path',
                            new BeforeSet(
                                new PathMatcher(new PathMatcherClass('http://www.test.com', '/requestpathtwo'))
                            )
                        ),
                        'query' => new FieldSet('query', new BeforeSet(new QueryStringToArray([]))),
                        'header' => new FieldSet('header', new Field(
                            'id',
                            new Implode(','),
                            new IntString(),
                            new ToInt()
                        )),
                        'cookie' => new FieldSet('cookie'),
                        'body' => new Field('requestBody', new Passes()),
                    ]
                ),
            ],
            'Request: path param in header, operation param in cookie, no requestBody' => [
                new Request(self::DIR . 'noReferences.json', 'http://www.test.com/requestpathtwo', Method::POST),
                new RequestProcessor(
                    '',
                    'requestpathtwo-post',
                    Method::POST,
                    [
                        'path' => new FieldSet(
                            'path',
                            new BeforeSet(
                                new PathMatcher(new PathMatcherClass('http://www.test.com', '/requestpathtwo'))
                            )
                        ),
                        'query' => new FieldSet('query', new BeforeSet(new QueryStringToArray([]))),
                        'header' => new FieldSet('header', new Field('id', new Implode(','), new IntString(), new ToInt())),
                        'cookie' => new FieldSet('cookie', new Field(
                            'name',
                            new Form('string', false),
                            new IsString()
                        )),
                        'body' => new Field('requestBody', new Passes()),
                    ]
                ),
            ],
            'Request: identical param in header and query, no requestBody' => [
                new Request(
                    self::DIR . 'noReferences.json',
                    'http://www.test.com/requestpathtwo',
                    Method::PUT
                ),
                new RequestProcessor(
                    '',
                    'requestpathtwo-put',
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
                            new BeforeSet(new QueryStringToArray(['id' => ['style' => 'form' , 'explode' => true]])),
                            new Field('id', new Form('integer', false), new IntString(), new ToInt())
                        ),
                        'header' => new FieldSet(
                            'header',
                            new Field('id', new Implode(','), new IntString(), new ToInt())
                        ),
                        'cookie' => new FieldSet('cookie'),
                        'body' => new Field('requestBody', new Passes()),
                    ]
                ),
            ],
            'Request: same param in path and operation with different types, no requestBody' => [
                new Request(
                    self::DIR . 'noReferences.json',
                    'http://www.test.com/requestpathtwo',
                    Method::DELETE
                ),
                new RequestProcessor(
                    '',
                    'requestpathtwo-delete',
                    Method::DELETE,
                    [
                        'path' => new FieldSet(
                            'path',
                            new BeforeSet(
                                new PathMatcher(new PathMatcherClass('http://www.test.com', '/requestpathtwo'))
                            )
                        ),
                        'query' => new FieldSet('query', new BeforeSet(new QueryStringToArray([]))),
                        'header' => new FieldSet('header', new Field('id', new Implode(','), new IsString())),
                        'cookie' => new FieldSet('cookie'),
                        'body' => new Field('requestBody', new Passes()),
                    ]
                ),
            ],
            'Request: requestBody param' => [
                new Request(
                    self::DIR . 'noReferences.json',
                    'http://www.test.com/requestbodypath',
                    Method::GET
                ),
                new RequestProcessor(
                    '',
                    'requestbodypath-get',
                    Method::GET,
                    [
                        'path' => new FieldSet(
                            'path',
                            new BeforeSet(
                                new PathMatcher(new PathMatcherClass('http://www.test.com', '/requestbodypath'))
                            )
                        ),
                        'query' => new FieldSet('query', new BeforeSet(new QueryStringToArray([]))),
                        'header' => new FieldSet('header'),
                        'cookie' => new FieldSet('cookie'),
                        'body' => new Field('requestBody', new IsInt()),
                    ]
                ),
            ],
            'Request: operation param in query, requestBody param' => [
                new Request(
                    self::DIR . 'noReferences.json',
                    'http://www.test.com/requestbodypath',
                    Method::POST
                ),
                new RequestProcessor(
                    '',
                    'requestbodypath-post',
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
                            new BeforeSet(new QueryStringToArray(['id' => ['style' => 'form' , 'explode' => true]])),
                            new Field('id', new Form('string', false), new IsString())
                        ),
                        'header' => new FieldSet('header'),
                        'cookie' => new FieldSet('cookie'),
                        'body' => new Field('requestBody', new IsInt()),
                    ]
                ),
            ],
            'Request: path param in path, operation param in query, header, cookie, requestBody param' => [
                new Request(
                    self::DIR . 'noReferences.json',
                    'http://www.test.com/requestbodypath/{id}',
                    Method::GET
                ),
                new RequestProcessor(
                    '',
                    'requestbodypath-id-get',
                    Method::GET,
                    [
                        'path' => new FieldSet(
                            'path',
                            new BeforeSet(
                                new PathMatcher(new PathMatcherClass('http://www.test.com', '/requestbodypath/{id}')),
                                new RequiredFields('id')
                            ),
                            new Field('id', new IntString(), new ToInt())
                        ),
                        'query' => new FieldSet(
                            'query',
                            new BeforeSet(new QueryStringToArray(['name' => ['style' => 'form', 'explode' => true]])),
                            new Field('name', new Form('string', false), new IsString())
                        ),
                        'header' => new FieldSet('header', new Field('species', new Implode(','), new IsString())),
                        'cookie' => new FieldSet('cookie', new Field(
                            'subspecies',
                            new Form('string', false),
                            new IsString()
                        )),
                        'body' => new Field('requestBody', new IsFloat()),
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
                        'header' => ['Host' => ['petstore.swagger.io']],
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
                        'header' => ['Host' => ['petstore.swagger.io']],
                        'cookie' => [],
                        'body' => '',
                    ],
                ),
            ],
            'petstore expanded /pets get (invalid)' => [
                new Request(
                    $expanded,
                    'https://petstore.swagger.io/v2/pets',
                    Method::GET
                ),
                new ServerRequest('get', 'https://petstore.swagger.io/v2/pets?limit=five'),
                Result::invalid(
                    [
                        'request' => ['method' => 'get', 'operationId' => 'findPets'],
                        'path' => [],
                        'query' => ['limit' => 'five'],
                        'header' => ['Host' => ['petstore.swagger.io']],
                        'cookie' => [],
                        'body' => '',
                    ],
                    new MessageSet(
                        new FieldName('limit', '', 'query'),
                        new Message('String value must be an integer.', [])
                    )
                ),
            ],
            'petstore expanded /pets get, minimal (valid)' => [
                new Request(
                    $expanded,
                    'https://petstore.swagger.io/v2/pets',
                    Method::GET
                ),
                new ServerRequest('get', 'https://petstore.swagger.io/v2/pets?limit=5&tags=cat,tabby'),
                Result::valid(
                    [
                        'request' => ['method' => 'get', 'operationId' => 'findPets'],
                        'path' => [],
                        'query' => ['limit' => 5, 'tags' => ['cat', 'tabby']],
                        'header' => ['Host' => ['petstore.swagger.io']],
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
        array | ServerRequestInterface $serverRequest,
        Result $expected
    ): void {
        $sut = new RequestBuilder();

        $processor = $sut->build($specification);

        $actual = $processor->process(new FieldName(''), $serverRequest);

        self::assertResultEquals($expected, $actual);
        self::assertSame($expected->value, $actual->value);
    }
}
