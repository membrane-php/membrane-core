<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Builder;

use Generator;
use GuzzleHttp\Psr7\ServerRequest;
use Membrane\Builder\Specification;
use Membrane\Filter\String\Explode;
use Membrane\Filter\String\Implode;
use Membrane\Filter\String\JsonDecode;
use Membrane\Filter\Type\ToBool;
use Membrane\Filter\Type\ToFloat;
use Membrane\Filter\Type\ToInt;
use Membrane\Filter\Type\ToNumber;
use Membrane\OpenAPI\Builder\APIBuilder;
use Membrane\OpenAPI\Builder\Arrays;
use Membrane\OpenAPI\Builder\Numeric;
use Membrane\OpenAPI\Builder\OpenAPIRequestBuilder;
use Membrane\OpenAPI\Builder\ParameterBuilder;
use Membrane\OpenAPI\Builder\RequestBuilder;
use Membrane\OpenAPI\Builder\Strings;
use Membrane\OpenAPI\Builder\TrueFalse;
use Membrane\OpenAPI\ContentType;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\ExtractPathParameters\PathMatcher as PathMatcherClass;
use Membrane\OpenAPI\Filter\HTTPParameters;
use Membrane\OpenAPI\Filter\PathMatcher;
use Membrane\OpenAPI\Processor\Request as RequestProcessor;
use Membrane\OpenAPI\Specification\APISchema;
use Membrane\OpenAPI\Specification\OpenAPIRequest;
use Membrane\OpenAPI\Specification\Parameter;
use Membrane\OpenAPI\Specification\Request;
use Membrane\OpenAPIReader\Method;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\Reader;
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
use Membrane\Validator\String\BoolString;
use Membrane\Validator\String\IntString;
use Membrane\Validator\String\NumericString;
use Membrane\Validator\Type\IsBool;
use Membrane\Validator\Type\IsFloat;
use Membrane\Validator\Type\IsInt;
use Membrane\Validator\Type\IsList;
use Membrane\Validator\Type\IsNumber;
use Membrane\Validator\Type\IsString;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(RequestBuilder::class)]
#[CoversClass(CannotProcessSpecification::class)]
#[CoversClass(CannotProcessOpenAPI::class)]
#[UsesClass(APIBuilder::class)]
#[UsesClass(OpenAPIRequestBuilder::class)]
#[UsesClass(OpenAPIRequest::class)]
#[UsesClass(ParameterBuilder::class)]
#[UsesClass(TrueFalse::class)]
#[UsesClass(Request::class)]
#[UsesClass(Arrays::class)]
#[UsesClass(Numeric::class)]
#[UsesClass(Strings::class)]
#[UsesClass(HTTPParameters::class)]
#[UsesClass(PathMatcher::class)]
#[UsesClass(PathMatcherClass::class)]
#[UsesClass(RequestProcessor::class)]
#[UsesClass(APISchema::class)]
#[UsesClass(\Membrane\OpenAPI\Specification\Arrays::class)]
#[UsesClass(\Membrane\OpenAPI\Specification\Numeric::class)]
#[UsesClass(\Membrane\OpenAPI\Specification\Strings::class)]
#[UsesClass(\Membrane\OpenAPI\Specification\TrueFalse::class)]
#[UsesClass(Parameter::class)]
#[UsesClass(Request::class)]
#[UsesClass(ToInt::class)]
#[UsesClass(ToBool::class)]
#[UsesClass(ToFloat::class)]
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
#[UsesClass(IsFloat::class)]
#[UsesClass(IsNumber::class)]
#[UsesClass(Passes::class)]
#[UsesClass(ContentType::class)]
#[UsesClass(IntString::class)]
#[UsesClass(Explode::class)]
#[UsesClass(Implode::class)]
#[UsesClass(JsonDecode::class)]
#[UsesClass(NumericString::class)]
#[UsesClass(BoolString::class)]

class RequestBuilderTest extends TestCase
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
            self::DIR . 'noReferences.json', 'http://www.test.com/nonexistentpath', Method::GET
        );

        (new RequestBuilder())->build($specification);
    }

    public static function provideRequestsToBuildForAndProcess(): Generator
    {
        $noParams = fn($result, $serverRequest) => [
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
                    'query' => new FieldSet('query', new BeforeSet(new HTTPParameters())),
                    'header' => new FieldSet('header'),
                    'cookie' => new FieldSet('cookie'),
                    'body' => new Field('requestBody', new Passes()),
                ]
            ),
            $result,
            new Request(
                self::DIR . 'noReferences.json',
                'http://www.test.com/path/path',
                Method::GET
            ),
            $serverRequest,
        ];

        yield 'Valid:No params' => $noParams(
            Result::valid([
                'request' => ['method' => 'get', 'operationId' => 'path-get'],
                'path' => [],
                'query' => [],
                'header' => ['Host' => ['www.test.com']],
                'cookie' => [],
                'body' => '',
            ]),
            new ServerRequest('get', 'http://www.test.com/path/path'),
        );

        $intPathParam = fn($result, $serverRequest) => [
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
                    'query' => new FieldSet('query', new BeforeSet(new HTTPParameters())),
                    'header' => new FieldSet('header'),
                    'cookie' => new FieldSet('cookie'),
                    'body' => new Field('requestBody', new Passes()),
                ]
            ),
            $result,
            new Request(
                self::DIR . 'noReferences.json',
                'http://www.test.com/requestpathone/{id}',
                Method::GET
            ),
            $serverRequest
        ];

        yield 'Valid:Int path param' => $intPathParam(
            Result::valid([
                'request' => [
                    'method' => 'get',
                    'operationId' => 'requestpathone-get'
                ],
                'path' => ['id' => 5],
                'query' => [],
                'header' => ['Host' => ['www.test.com']],
                'cookie' => [],
                'body' => '',
            ]),
            new ServerRequest(
                'get',
                'http://www.test.com/requestpathone/5'
            )
        );

        yield 'Invalid:Int path param, string given' => $intPathParam(
            Result::invalid(
                [
                    'request' => [
                        'method' => 'get',
                        'operationId' => 'requestpathone-get'
                    ],
                    'path' => ['id' => 'five'],
                    'query' => [],
                    'header' => ['Host' => ['www.test.com']],
                    'cookie' => [],
                    'body' => '',
                ],
                new MessageSet(
                    new FieldName('id', '', 'path'),
                    new Message('String value must be an integer.', [])
                )
            ),
            new ServerRequest(
                'get',
                'http://www.test.com/requestpathone/five'
            )
        );

        $optionalIntQueryParam = fn($result, $serverRequest) => [
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
                        new BeforeSet(new HTTPParameters()),
                        new Field('age', new IntString(), new ToInt())
                    ),
                    'header' => new FieldSet('header'),
                    'cookie' => new FieldSet('cookie'),
                    'body' => new Field('requestBody', new Passes()),
                ]
            ),
            $result,
            new Request(
                self::DIR . 'noReferences.json',
                'http://www.test.com/requestpathone/{id}',
                Method::POST
            ),
            $serverRequest,
        ];

        yield 'Valid:Optional int query param not specified' => $optionalIntQueryParam(
            Result::valid([
                'request' => [
                    'method' => 'post',
                    'operationId' => 'requestpathone-post'
                ],
                'path' => ['id' => 5],
                'query' => [],
                'header' => ['Host' => ['www.test.com']],
                'cookie' => [],
                'body' => '',
            ]),
            new ServerRequest(
                'post',
                'http://www.test.com/requestpathone/5'
            )
        );

        yield 'Valid:Optional int query param specified' => $optionalIntQueryParam(
            Result::valid([
                'request' => [
                    'method' => 'post',
                    'operationId' => 'requestpathone-post'
                ],
                'path' => ['id' => 5],
                'query' => ['age' => 32],
                'header' => ['Host' => ['www.test.com']],
                'cookie' => [],
                'body' => '',
            ]),
            new ServerRequest(
                'post',
                'http://www.test.com/requestpathone/5?age=32'
            )
        );

        yield 'Invalid:Optional int query param specified as string' => $optionalIntQueryParam(
            Result::invalid(
                [
                    'request' => [
                        'method' => 'post',
                        'operationId' => 'requestpathone-post'
                    ],
                    'path' => ['id' => 5],
                    'query' => ['age' => 'thirty-two'],
                    'header' => ['Host' => ['www.test.com']],
                    'cookie' => [],
                    'body' => '',
                ],
                new MessageSet(
                    new FieldName('age', '', 'query'),
                    new Message('String value must be an integer.', [])
                )
            ),
            new ServerRequest(
                'post',
                'http://www.test.com/requestpathone/5?age=thirty-two'
            )
        );

        $requiredStringQueryParamWithSchema = fn($result, $serverRequest) => [
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
                        new BeforeSet(new HTTPParameters(), new RequiredFields('name')),
                        new Field('name', new IsString())
                    ),
                    'header' => new FieldSet('header'),
                    'cookie' => new FieldSet('cookie'),
                    'body' => new Field('requestBody', new Passes()),
                ]
            ),
            $result,
            new Request(
                self::DIR . 'noReferences.json',
                'http://www.test.com/requestpathone/{id}',
                Method::PUT
            ),
            $serverRequest,
        ];

        yield 'Valid:Required string query param specified' => $requiredStringQueryParamWithSchema(
            Result::valid([
                'request' => [
                    'method' => 'put',
                    'operationId' => 'requestpathone-put'
                ],
                'path' => ['id' => 5],
                'query' => ['name' => 'dave'],
                'header' => ['Host' => ['www.test.com']],
                'cookie' => [],
                'body' => '',
            ]),
            new ServerRequest(
                'put',
                'http://www.test.com/requestpathone/5?name=dave'
            )
        );

        yield 'Invalid:Required string query param not specified' => $requiredStringQueryParamWithSchema(
            Result::invalid(
                [
                    'request' => [
                        'method' => 'put',
                        'operationId' => 'requestpathone-put'
                    ],
                    'path' => ['id' => 5],
                    'query' => [],
                    'header' => ['Host' => ['www.test.com']],
                    'cookie' => [],
                    'body' => '',
                ],
                new MessageSet(
                    new FieldName('', '', 'query'),
                    new Message('%s is a required field', ['name'])
                )
            ),
            new ServerRequest(
                'put',
                'http://www.test.com/requestpathone/5',
            )
        );

        $requiredStringQueryParamWithContent = fn($result, $serverRequest) => [
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
                        new BeforeSet(new HTTPParameters(), new RequiredFields('name')),
                        new Field('name', new IsString())
                    ),
                    'header' => new FieldSet('header'),
                    'cookie' => new FieldSet('cookie'),
                    'body' => new Field('requestBody', new Passes()),
                ]
            ),
            $result,
            new Request(
                self::DIR . 'noReferences.json',
                'http://www.test.com/requestpathone/{id}',
                Method::DELETE
            ),
            $serverRequest,
        ];

        yield 'Valid:Required Query param with content specified' => $requiredStringQueryParamWithContent(
            Result::valid([
                'request' => [
                    'method' => 'delete',
                    'operationId' => 'requestpathone-delete'
                ],
                'path' => ['id' => 5],
                'query' => ['name' => 'dave'],
                'header' => ['Host' => ['www.test.com']],
                'cookie' => [],
                'body' => '',
            ]),
            new ServerRequest(
                'delete',
                'http://www.test.com/requestpathone/5?name=dave'
            )
        );

        yield 'Invalid:Required query param with content unspecified' => $requiredStringQueryParamWithContent(
            Result::invalid(
                [
                    'request' => [
                        'method' => 'delete',
                        'operationId' => 'requestpathone-delete'
                    ],
                    'path' => ['id' => 5],
                    'query' => [],
                    'header' => ['Host' => ['www.test.com']],
                    'cookie' => [],
                    'body' => '',
                ],
                new MessageSet(
                    new FieldName('', '', 'query'),
                    new Message('%s is a required field', ['name'])
                )
            ),
            new ServerRequest(
                'delete',
                'http://www.test.com/requestpathone/5',
            )
        );

        $optionalIntHeaderParam = fn($result, $serverRequest) => [
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
                    'query' => new FieldSet('query', new BeforeSet(new HTTPParameters())),
                    'header' => new FieldSet('header', new Field('id', new Implode(','), new IntString(), new ToInt())),
                    'cookie' => new FieldSet('cookie'),
                    'body' => new Field('requestBody', new Passes()),
                ]
            ),
            $result,
            new Request(
                self::DIR . 'noReferences.json',
                'http://www.test.com/requestpathtwo',
                Method::GET
            ),
            $serverRequest,
        ];

        yield 'Valid:Optional int header param unspecified' => $optionalIntHeaderParam(
            Result::valid([
                'request' => [
                    'method' => 'get',
                    'operationId' => 'requestpathtwo-get'
                ],
                'path' => [],
                'query' => [],
                'header' => ['Host' => ['www.test.com']],
                'cookie' => [],
                'body' => '',
            ]),
            new ServerRequest(
                'get',
                'http://www.test.com/requestpathtwo'
            )
        );

        yield 'Valid:Optional int header param specified' => $optionalIntHeaderParam(
            Result::valid([
                'request' => [
                    'method' => 'get',
                    'operationId' => 'requestpathtwo-get'
                ],
                'path' => [],
                'query' => [],
                'header' => [
                    'Host' => ['www.test.com'],
                    'id' => '5',
                ],
                'cookie' => [],
                'body' => '',
            ]),
            new ServerRequest(
                'get',
                'http://www.test.com/requestpathtwo',
                ['id' => '5']
            )
        );

        yield 'Invalid:Optional int header param specified as string' => $optionalIntHeaderParam(
            Result::invalid(
                [
                    'request' => [
                        'method' => 'get',
                        'operationId' => 'requestpathtwo-get'
                    ],
                    'path' => [],
                    'query' => [],
                    'header' => [
                        'Host' => ['www.test.com'],
                        'id' => 'dave',
                    ],
                    'cookie' => [],
                    'body' => '',
                ],
                new MessageSet(
                    new FieldName('id', '', 'header'),
                    new Message('String value must be an integer.', [])
                )
            ),
            new ServerRequest(
                'get',
                'http://www.test.com/requestpathtwo',
                ['id' => 'dave']
            )
        );

        $operationParamOverridesPathParam = fn($result, $serverRequest) => [
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
                        new BeforeSet(new HTTPParameters()),
                        new Field('id', new IntString(), new ToInt())
                    ),
                    'header' => new FieldSet('header'),
                    'cookie' => new FieldSet('cookie'),
                    'body' => new Field('requestBody', new Passes()),
                ]
            ),
            $result,
            new Request(
                self::DIR . 'noReferences.json',
                'http://www.test.com/requestpathtwo',
                Method::PUT
            ),
            $serverRequest
        ];

        yield 'Valid:operation param overrides path param' => $operationParamOverridesPathParam(
            Result::valid([
                'request' => [
                    'method' => 'put',
                    'operationId' => 'requestpathtwo-put'
                ],
                'path' => [],
                'query' => [],
                'header' => [
                    'Host' => ['www.test.com'],
                ],
                'cookie' => [],
                'body' => '',
            ]),
            new ServerRequest(
                'put',
                'http://www.test.com/requestpathtwo'
            )
        );

        yield 'Invalid:operation param overrides path param' => $operationParamOverridesPathParam(
            Result::invalid([
                'request' => [
                    'method' => 'put',
                    'operationId' => 'requestpathtwo-put'
                ],
                'path' => [],
                'query' => ['id' => 'five'],
                'header' => [
                    'Host' => ['www.test.com'],
                ],
                'cookie' => [],
                'body' => '',
            ],
                new MessageSet(
                    new FieldName('id', '', 'query'),
                    new Message('String value must be an integer.', [])
                )
            ),
            new ServerRequest(
                'put',
                'http://www.test.com/requestpathtwo?id=five'
            )
        );

        $intHeaderStringCookieParam = fn($result, $serverRequest) => [
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
                    'query' => new FieldSet('query', new BeforeSet(new HTTPParameters())),
                    'header' => new FieldSet('header', new Field('id', new Implode(','), new IntString(), new ToInt())),
                    'cookie' => new FieldSet('cookie', new Field('name', new IsString())),
                    'body' => new Field('requestBody', new Passes()),
                ]
            ),
            $result,
            new Request(self::DIR . 'noReferences.json', 'http://www.test.com/requestpathtwo', Method::POST),
            $serverRequest,
        ];

        yield 'Valid:Int header and string cookie param, unspecified' => $intHeaderStringCookieParam(
            Result::valid([
                'request' => [
                    'method' => 'post',
                    'operationId' => 'requestpathtwo-post'
                ],
                'path' => [],
                'query' => [],
                'header' => [
                    'Host' => ['www.test.com'],
                ],
                'cookie' => [],
                'body' => '',
            ]),
            new ServerRequest(
                'post',
                'http://www.test.com/requestpathtwo'
            )
        );

        yield 'Valid:Int header and string cookie param, specified' => $intHeaderStringCookieParam(
            Result::valid([
                'request' => [
                    'method' => 'post',
                    'operationId' => 'requestpathtwo-post'
                ],
                'path' => [],
                'query' => [],
                'header' => [
                    'Host' => ['www.test.com'],
                    'id' => '5'
                ],
                'cookie' => ['name' => 'dave'],
                'body' => '',
            ]),
            (new ServerRequest(
                'post',
                'http://www.test.com/requestpathtwo',
                ['id' => '5']
            ))->withCookieParams(['name' => 'dave'])
        );

        yield 'Invalid:string cookie param specified as int' => $intHeaderStringCookieParam(
            Result::invalid(
                [
                    'request' => [
                        'method' => 'post',
                        'operationId' => 'requestpathtwo-post'
                    ],
                    'path' => [],
                    'query' => [],
                    'header' => [
                        'Host' => ['www.test.com'],
                        'id' => '5'
                    ],
                    'cookie' => ['name' => 1],
                    'body' => '',
                ],
                new MessageSet(
                    new FieldName('name', '', 'cookie'),
                    new Message('IsString validator expects string value, %s passed instead', ['integer'])
                )
            ),
            (new ServerRequest(
                'post',
                'http://www.test.com/requestpathtwo',
                ['id' => '5']
            ))->withCookieParams(['name' => 1])
        );

        $intBodyParam = fn($result, $serverRequest) => [
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
                    'query' => new FieldSet('query', new BeforeSet(new HTTPParameters())),
                    'header' => new FieldSet('header'),
                    'cookie' => new FieldSet('cookie'),
                    'body' => new Field('requestBody', new IsInt()),
                ]
            ),
            $result,
            new Request(
                self::DIR . 'noReferences.json',
                'http://www.test.com/requestbodypath',
                Method::GET
            ),
            $serverRequest,
        ];

        yield 'Valid:Optional int body' => $intBodyParam(
            Result::valid([
                'request' => [
                    'method' => 'get',
                    'operationId' => 'requestbodypath-get'
                ],
                'path' => [],
                'query' => [],
                'header' => [
                    'Host' => ['www.test.com'],
                    'Content-Type' => ['application/json'],
                ],
                'cookie' => [],
                'body' => 5,
            ]),
            new ServerRequest(
                'get',
                'http://www.test.com/requestbodypath',
                [
                    'Content-Type' => 'application/json',
                ],
                '5'
            )
        );

        yield 'Invalid:No Content-Type specified' => $intBodyParam(
            Result::invalid([
                'request' => [
                    'method' => 'get',
                    'operationId' => 'requestbodypath-get'
                ],
                'path' => [],
                'query' => [],
                'header' => ['Host' => ['www.test.com']],
                'cookie' => [],
                'body' => '5',
            ],
                new MessageSet(
                    new FieldName('requestBody', ''),
                    new Message('IsInt validator expects integer value, %s passed instead', ['string']),
                )
            ),
            new ServerRequest(
                'get',
                'http://www.test.com/requestbodypath',
                [],
                '5'
            )
        );

        $scalarsInEverything = fn($result, $serverRequest) => [
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
                        new Field('id', new NumericString(), new ToFloat())
                    ),
                    'query' => new FieldSet(
                        'query',
                        new BeforeSet(new HTTPParameters()),
                        new Field('age', new IntString(), new ToInt())
                    ),
                    'header' => new FieldSet(
                        'header',
                        new Collection(
                            'species',
                            new BeforeSet(new IsList()),
                            new Field('', new BoolString(), new ToBool())
                        )
                    ),
                    'cookie' => new FieldSet(
                        'cookie',
                        new Field('subspecies', new IsNumber())
                    ),
                    'body' => new Field('requestBody', new IsFloat()),
                ]
            ),
            $result,
            new Request(
                self::DIR . 'noReferences.json',
                'http://www.test.com/requestbodypath/{id}',
                Method::GET
            ),
            $serverRequest,
        ];

        yield 'Valid:Scalar parameters everything specified correctly' => $scalarsInEverything(
            Result::valid([
                'request' => [
                    'method' => 'get',
                    'operationId' => 'requestbodypath-id-get'
                ],
                'path' => ['id' => 5.5],
                'query' => ['age' => 3],
                'header' => [
                    'Host' => ['www.test.com'],
                    'Content-Type' => ['application/json'],
                    'species' => [true, false, true],
                ],
                'cookie' => ['subspecies' => 1],
                'body' => 3.14,
            ]),
            (new ServerRequest(
                'get',
                'http://www.test.com/requestbodypath/5.5?age=3',
                [
                    'Content-Type' => 'application/json',
                    'species' => ['true', 'false', 'true'],
                ],
                '3.14'
            ))->withCookieParams(['subspecies' => 1])
        );

        yield 'Invalid:Scalar parameters everything specified incorrectly' => $scalarsInEverything(
            Result::invalid(
                [
                    'request' => [
                        'method' => 'get',
                        'operationId' => 'requestbodypath-id-get'
                    ],
                    'path' => ['id' => 'five'],
                    'query' => ['age' => 3.14],
                    'header' => [
                        'Host' => ['www.test.com'],
                        'Content-Type' => ['application/json'],
                        'species' => ['yes', 'no'],
                    ],
                    'cookie' => ['subspecies' => 'dave'],
                    'body' => 'pi',
                ],
                new MessageSet(
                    new FieldName('requestBody', ''),
                    new Message('IsFloat expects float value, %s passed instead', ['string'])
                ),
                new MessageSet(
                    new FieldName('subspecies', '', 'cookie'),
                    new Message('Value must be a number, %s passed', ['string'])
                ),
                new MessageSet(
                    new FieldName('', '', 'header', 'species', '0'),
                    new Message('String value must be a boolean.', [])
                ),
                new MessageSet(
                    new FieldName('', '', 'header', 'species', '1'),
                    new Message('String value must be a boolean.', [])
                ),
                new MessageSet(
                    new FieldName('age', '', 'query'),
                    new Message('String value must be an integer.', [])
                ),
                new MessageSet(
                    new FieldName('id', '', 'path'),
                    new Message('String value must be numeric', [])
                ),
            ),
            (new ServerRequest(
                'get',
                'http://www.test.com/requestbodypath/five?age=3.14',
                [
                    'Content-Type' => 'application/json',
                    'species' => ['yes', 'no'],
                ],
                '"pi"'
            ))->withCookieParams(['subspecies' => 'dave'])
        );
    }

    #[Test, DataProvider('provideRequestsToBuildForAndProcess')]
    public function itBuildsAppropriateProcessors(
        Processor $expectedProcessor,
        Result $expectedResult,
        Request $specification,
        ServerRequest $request,
    ): void {
        $sut = new RequestBuilder();

        $actualProcessor = $sut->build($specification);

        self::assertEquals($expectedProcessor, $actualProcessor);

        $actualResult = $actualProcessor->process(new FieldName(''), $request);

        self::assertEquals($expectedResult, $actualResult);
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
                        'header' => ['Host' => ['petstore.swagger.io'],],
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
                        'header' => ['Host' => ['petstore.swagger.io'],],
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
                    'http://petstore.swagger.io/api/pets',
                    Method::GET
                ),
                new ServerRequest('get', 'http://petstore.swagger.io/api/pets?limit=5&tags=cat,tabby'),
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

        self::assertEquals($expected, $actual);
        self::assertSame($expected->value, $actual->value);
    }
}
