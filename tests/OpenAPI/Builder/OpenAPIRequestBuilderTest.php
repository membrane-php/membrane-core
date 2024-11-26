<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Builder;

use cebe\openapi\spec as Cebe;
use Generator;
use GuzzleHttp\Psr7\ServerRequest;
use Membrane\Builder\Specification;
use Membrane\Filter\Shape\KeyValueSplit;
use Membrane\Filter\String\Explode;
use Membrane\Filter\String\Implode;
use Membrane\Filter\String\LeftTrim;
use Membrane\Filter\String\Tokenize;
use Membrane\Filter\Type\ToBool;
use Membrane\Filter\Type\ToInt;
use Membrane\Filter\Type\ToNumber;
use Membrane\OpenAPI\Builder\APIBuilder;
use Membrane\OpenAPI\Builder\Arrays;
use Membrane\OpenAPI\Builder\Numeric;
use Membrane\OpenAPI\Builder\Objects;
use Membrane\OpenAPI\Builder\OpenAPIRequestBuilder;
use Membrane\OpenAPI\Builder\ParameterBuilder;
use Membrane\OpenAPI\Builder\RequestBuilder;
use Membrane\OpenAPI\Builder\Strings;
use Membrane\OpenAPI\ContentType;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\ExtractPathParameters\PathMatcher as PathMatcherClass;
use Membrane\OpenAPI\ExtractPathParameters\PathParameterExtractor;
use Membrane\OpenAPI\Filter\FormatStyle\DeepObject;
use Membrane\OpenAPI\Filter\FormatStyle\Form;
use Membrane\OpenAPI\Filter\FormatStyle\Matrix;
use Membrane\OpenAPI\Filter\FormatStyle\PipeDelimited;
use Membrane\OpenAPI\Filter\FormatStyle\SpaceDelimited;
use Membrane\OpenAPI\Filter\HTTPParameters;
use Membrane\OpenAPI\Filter\PathMatcher;
use Membrane\OpenAPI\Filter\QueryStringToArray;
use Membrane\OpenAPI\Processor\AllOf;
use Membrane\OpenAPI\Processor\AnyOf;
use Membrane\OpenAPI\Processor\OneOf;
use Membrane\OpenAPI\Processor\Request as RequestProcessor;
use Membrane\OpenAPI\Specification\APISchema;
use Membrane\OpenAPI\Specification\OpenAPIRequest;
use Membrane\OpenAPI\Specification\Parameter;
use Membrane\OpenAPI\Specification\Request;
use Membrane\OpenAPI\Specification\TrueFalse;
use Membrane\OpenAPIReader\FileFormat;
use Membrane\OpenAPIReader\MembraneReader;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\Reader;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Style;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\Processor;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Collection;
use Membrane\Processor\DefaultProcessor;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
use Membrane\Renderer\HumanReadable;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Tests\Fixtures\OpenAPI\MakesOperation;
use Membrane\Tests\Fixtures\OpenAPI\MakesPathItem;
use Membrane\Tests\MembraneTestCase;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\Numeric\Maximum;
use Membrane\Validator\String\BoolString;
use Membrane\Validator\String\IntString;
use Membrane\Validator\String\NumericString;
use Membrane\Validator\Type\IsArray;
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
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\V30;

#[CoversClass(OpenAPIRequestBuilder::class)]
#[CoversClass(ParameterBuilder::class)]
#[CoversClass(APIBuilder::class)]
#[CoversClass(CannotProcessSpecification::class)]
#[CoversClass(CannotProcessOpenAPI::class)]
#[UsesClass(HumanReadable::class)] // to render test failure messages
#[UsesClass(RequestBuilder::class)]
#[UsesClass(OpenAPIRequestBuilder::class)]
#[UsesClass(\Membrane\OpenAPI\Builder\TrueFalse::class)]
#[UsesClass(OpenAPIRequest::class)]
#[UsesClass(Request::class)]
#[UsesClass(Arrays::class)]
#[UsesClass(Numeric::class)]
#[UsesClass(Objects::class)]
#[UsesClass(Strings::class)]
#[UsesClass(QueryStringToArray::class)]
#[UsesClass(PathMatcher::class)]
#[UsesClass(PathParameterExtractor::class)]
#[UsesClass(PathMatcherClass::class)]
#[UsesClass(RequestProcessor::class)]
#[UsesClass(Parameter::class)]
#[UsesClass(APISchema::class)]
#[UsesClass(AllOf::class)]
#[UsesClass(AnyOf::class)]
#[UsesClass(OneOf::class)]
#[UsesClass(\Membrane\OpenAPI\Specification\Arrays::class)]
#[UsesClass(\Membrane\OpenAPI\Specification\Numeric::class)]
#[UsesClass(\Membrane\OpenAPI\Specification\Objects::class)]
#[UsesClass(\Membrane\OpenAPI\Specification\Strings::class)]
#[UsesClass(TrueFalse::class)]
#[UsesClass(Request::class)]
#[UsesClass(Explode::class)]
#[UsesClass(Implode::class)]
#[UsesClass(Tokenize::class)]
#[UsesClass(ToBool::class)]
#[UsesClass(ToInt::class)]
#[UsesClass(ToNumber::class)]
#[UsesClass(BeforeSet::class)]
#[UsesClass(Collection::class)]
#[UsesClass(DefaultProcessor::class)]
#[UsesClass(Field::class)]
#[UsesClass(FieldSet::class)]
#[UsesClass(FieldName::class)]
#[UsesClass(Message::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Result::class)]
#[UsesClass(RequiredFields::class)]
#[UsesClass(Maximum::class)]
#[UsesClass(BoolString::class)]
#[UsesClass(IsArray::class)]
#[UsesClass(IsInt::class)]
#[UsesClass(IntString::class)]
#[UsesClass(IsFloat::class)]
#[UsesClass(IsList::class)]
#[UsesClass(NumericString::class)]
#[UsesClass(IsString::class)]
#[UsesClass(Passes::class)]
#[UsesClass(Matrix::class)]
#[UsesClass(Form::class)]
#[UsesClass(SpaceDelimited::class)]
#[UsesClass(PipeDelimited::class)]
#[UsesClass(DeepObject::class)]
#[UsesClass(ContentType::class)]
#[UsesClass(LeftTrim::class)]
#[UsesClass(KeyValueSplit::class)]
class OpenAPIRequestBuilderTest extends MembraneTestCase
{
    public const FIXTURES = __DIR__ . '/../../fixtures/OpenAPI/';

    #[Test, TestDox('It will support the OpenAPIRequest Specification')]
    public function supportsRequestSpecification(): void
    {
        $specification = self::createStub(OpenAPIRequest::class);
        $sut = new OpenAPIRequestBuilder();

        self::assertTrue($sut->supports($specification));
    }

    #[Test, TestDox('It will not support any Specifications other than OpenAPIRequest')]
    public function doesNotSupportSpecificationsOtherThanRequest(): void
    {
        $specification = self::createStub(\Membrane\Builder\Specification::class);
        $sut = new OpenAPIRequestBuilder();

        self::assertFalse($sut->supports($specification));
    }

    #[Test, TestDox('It currently only supports application/json content')]
    public function throwsExceptionIfParameterHasContentThatIsNotJson(): void
    {
        $openAPI = (new MembraneReader([OpenAPIVersion::Version_3_0]))
            ->readFromAbsoluteFilePath((self::FIXTURES . 'noReferences.json'));

        $specification = new OpenAPIRequest(
            OpenAPIVersion::Version_3_0,
            new PathParameterExtractor('/requestpathexceptions'),
            $openAPI->paths['/requestpathexceptions'],
            Method::POST
        );

        $sut = new OpenAPIRequestBuilder();

        $mediaTypes = array_keys($openAPI->paths['/requestpathexceptions']->post->parameters[0]->content);

        self::expectExceptionObject(CannotProcessOpenAPI::unsupportedMediaTypes(...$mediaTypes));

        $sut->build($specification);
    }

    //#[Test]
    //#[TestDox('Builds a Processor for the Operation Object (specified by the PathItem and method provided')]
    //#[DataProvider('dataSetsForBuild')]
    //public function buildTest(Specification $spec, Processor $expected): void
    //{
    //    $sut = new OpenAPIRequestBuilder();
    //
    //    $actual = $sut->build($spec);
    //
    //    self::assertProcessorEquals($expected, $actual);
    //}

    #[Test]
    #[DataProvider('dataSetsForDocExamples')]
    #[DataProvider('provideAPIWithPathParameters')]
    #[DataProvider('provideAPIWithQueryParameters')]
    #[DataProvider('provideAPIWithHeaderParameters')]
    public function itBuildsProcessorsThatValidateRequests(
        OpenAPIRequest $specification,
        array | ServerRequestInterface $serverRequest,
        Result $expected
    ): void {
        $sut = new OpenAPIRequestBuilder();

        $processor = $sut->build($specification);

        $actual = $processor->process(new FieldName(''), $serverRequest);

        self::assertResultEquals($expected, $actual);
        self::assertSame($expected->value, $actual->value);
    }

    //public static function dataSetsForBuild(): Generator
    //{
    //    $noRefAPI = (new Reader([OpenAPIVersion::Version_3_0]))
    //        ->readFromAbsoluteFilePath(__DIR__ . '/../../fixtures/OpenAPI/noReferences.json');
    //
    //    yield 'Request: no path params, no operation params, no requestBody' => [
    //        new OpenAPIRequest(
    //            OpenAPIVersion::Version_3_0,
    //            new PathParameterExtractor('/path'),
    //            $noRefAPI->paths['/path'],
    //            Method::GET
    //        ),
    //        new RequestProcessor(
    //            '',
    //            'path-get',
    //            Method::GET,
    //            [
    //                'path' => new FieldSet(
    //                    'path',
    //                    new BeforeSet(
    //                        new PathMatcher(new PathParameterExtractor('/path'))
    //                    )
    //                ),
    //                'query' => new FieldSet('query', new BeforeSet(new QueryStringToArray([]))),
    //                'header' => new FieldSet('header'),
    //                'cookie' => new FieldSet('cookie'),
    //                'body' => new Field('requestBody', new Passes()),
    //            ]
    //        ),
    //    ];
    //    yield 'Patch Request: no path params, no operation params, no requestBody' => [
    //        new OpenAPIRequest(
    //            OpenAPIVersion::Version_3_0,
    //            new PathParameterExtractor('/path'),
    //            $noRefAPI->paths['/path'],
    //            Method::PATCH
    //        ),
    //        new RequestProcessor(
    //            '',
    //            'path-patch',
    //            Method::PATCH,
    //            [
    //                'path' => new FieldSet(
    //                    'path',
    //                    new BeforeSet(
    //                        new PathMatcher(new PathParameterExtractor('/path'))
    //                    )
    //                ),
    //                'query' => new FieldSet('query', new BeforeSet(new QueryStringToArray([]))),
    //                'header' => new FieldSet('header'),
    //                'cookie' => new FieldSet('cookie'),
    //                'body' => new Field('requestBody', new Passes()),
    //            ]
    //        ),
    //    ];
    //    yield 'Request: path param in path, no operation params, no requestBody' => [
    //        new OpenAPIRequest(
    //            OpenAPIVersion::Version_3_0,
    //            new PathParameterExtractor('/requestpathone/{id}'),
    //            $noRefAPI->paths['/requestpathone/{id}'],
    //            Method::GET
    //        ),
    //        new RequestProcessor(
    //            '',
    //            'requestpathone-get',
    //            Method::GET,
    //            [
    //                'path' => new FieldSet(
    //                    'path',
    //                    new BeforeSet(
    //                        new PathMatcher(new PathParameterExtractor('/requestpathone/{id}')),
    //                        new RequiredFields('id')
    //                    ),
    //                    new Field('id', new IntString(), new ToInt())
    //                ),
    //                'query' => new FieldSet('query', new BeforeSet(new QueryStringToArray([]))),
    //                'header' => new FieldSet('header'),
    //                'cookie' => new FieldSet('cookie'),
    //                'body' => new Field('requestBody', new Passes()),
    //            ]
    //        ),
    //    ];
    //    yield 'Request: path param in path, operation param in query not required, no requestBody' => [
    //        new OpenAPIRequest(
    //            OpenAPIVersion::Version_3_0,
    //            new PathParameterExtractor('/requestpathone/{id}'),
    //            $noRefAPI->paths['/requestpathone/{id}'],
    //            Method::POST
    //        ),
    //        new RequestProcessor(
    //            '',
    //            'requestpathone-post',
    //            Method::POST,
    //            [
    //                'path' => new FieldSet(
    //                    'path',
    //                    new BeforeSet(
    //                        new PathMatcher(new PathParameterExtractor('/requestpathone/{id}')),
    //                        new RequiredFields('id')
    //                    ),
    //                    new Field('id', new IntString(), new ToInt())
    //                ),
    //                'query' => new FieldSet(
    //                    'query',
    //                    new BeforeSet(new QueryStringToArray(['age' => ['style' => 'form', 'explode' => true]])),
    //                    new Field('age', new Form('integer', false), new IntString(), new ToInt())
    //                ),
    //                'header' => new FieldSet('header'),
    //                'cookie' => new FieldSet('cookie'),
    //                'body' => new Field('requestBody', new Passes()),
    //            ]
    //        ),
    //    ];
    //    yield 'Request: path param in path, operation param in query required, no requestBody' => [
    //        new OpenAPIRequest(
    //            OpenAPIVersion::Version_3_0,
    //            new PathParameterExtractor('/requestpathone/{id}'),
    //            new Cebe\PathItem([
    //                'parameters' => [
    //                    new Cebe\Parameter([
    //                        'name' => 'id',
    //                        'in' => 'path',
    //                        'required' => true,
    //                        'schema' => new Cebe\Schema(['type' => 'integer']),
    //                    ]),
    //                ],
    //                'put' => new Cebe\Operation([
    //                    'operationId' => 'requestpathone-post',
    //                    'parameters' => [
    //                        new Cebe\Parameter(
    //                            [
    //                                'name' => 'name',
    //                                'in' => 'query',
    //                                'required' => true,
    //                                'schema' => new Cebe\Schema(['type' => 'string']),
    //                            ]
    //                        ),
    //                    ],
    //                ]),
    //            ]),
    //            Method::PUT
    //        ),
    //        new RequestProcessor(
    //            '',
    //            'requestpathone-post',
    //            Method::PUT,
    //            [
    //                'path' => new FieldSet(
    //                    'path',
    //                    new BeforeSet(
    //                        new PathMatcher(new PathParameterExtractor('/requestpathone/{id}')),
    //                        new RequiredFields('id')
    //                    ),
    //                    new Field('id', new IntString(), new ToInt())
    //                ),
    //                'query' => new FieldSet(
    //                    'query',
    //                    new BeforeSet(
    //                        new QueryStringToArray(
    //                            ['name' => ['style' => 'form', 'explode' => true]]
    //                        ),
    //                        new RequiredFields('name')
    //                    ),
    //                    new Field('name', new Form('string', false), new IsString())
    //                ),
    //                'header' => new FieldSet('header'),
    //                'cookie' => new FieldSet('cookie'),
    //                'body' => new Field('requestBody', new Passes()),
    //            ]
    //        ),
    //    ];
    //    yield 'Request: path param in path, operation param in query with json content, required, no requestBody' => [
    //        new OpenAPIRequest(
    //            OpenAPIVersion::Version_3_0,
    //            new PathParameterExtractor('/requestpathone/{id}'),
    //            $noRefAPI->paths['/requestpathone/{id}'],
    //            Method::DELETE
    //        ),
    //        new RequestProcessor(
    //            '',
    //            'requestpathone-delete',
    //            Method::DELETE,
    //            [
    //                'path' => new FieldSet(
    //                    'path',
    //                    new BeforeSet(
    //                        new PathMatcher(new PathParameterExtractor('/requestpathone/{id}')),
    //                        new RequiredFields('id')
    //                    ),
    //                    new Field('id', new IntString(), new ToInt())
    //                ),
    //                'query' => new FieldSet(
    //                    'query',
    //                    new BeforeSet(
    //                        new QueryStringToArray(
    //                            ['name' => ['style' => 'form', 'explode' => true]]
    //                        ),
    //                        new RequiredFields('name')
    //                    ),
    //                    new Field('name', new Form('string', false), new IsString())
    //                ),
    //                'header' => new FieldSet('header'),
    //                'cookie' => new FieldSet('cookie'),
    //                'body' => new Field('requestBody', new Passes()),
    //            ]
    //        ),
    //    ];
    //    yield 'Request: path param in header, no requestBody' => [
    //        new OpenAPIRequest(
    //            OpenAPIVersion::Version_3_0,
    //            new PathParameterExtractor('/requestpathtwo'),
    //            $noRefAPI->paths['/requestpathtwo'],
    //            Method::GET
    //        ),
    //        new RequestProcessor(
    //            '',
    //            'requestpathtwo-get',
    //            Method::GET,
    //            [
    //                'path' => new FieldSet(
    //                    'path',
    //                    new BeforeSet(
    //                        new PathMatcher(new PathParameterExtractor('/requestpathtwo'))
    //                    )
    //                ),
    //                'query' => new FieldSet('query', new BeforeSet(new QueryStringToArray([]))),
    //                'header' => new FieldSet('header', new Field('id', new Implode(','), new IntString(), new ToInt())),
    //                'cookie' => new FieldSet('cookie'),
    //                'body' => new Field('requestBody', new Passes()),
    //            ]
    //        ),
    //    ];
    //    yield 'Request: path param in header, operation param in cookie, no requestBody' => [
    //        new OpenAPIRequest(
    //            OpenAPIVersion::Version_3_0,
    //            new PathParameterExtractor('/requestpathtwo'),
    //            $noRefAPI->paths['/requestpathtwo'],
    //            Method::POST
    //        ),
    //        new RequestProcessor(
    //            '',
    //            'requestpathtwo-post',
    //            Method::POST,
    //            [
    //                'path' => new FieldSet(
    //                    'path',
    //                    new BeforeSet(
    //                        new PathMatcher(new PathParameterExtractor('/requestpathtwo'))
    //                    )
    //                ),
    //                'query' => new FieldSet('query', new BeforeSet(new QueryStringToArray([]))),
    //                'header' => new FieldSet('header', new Field('id', new Implode(','), new IntString(), new ToInt())),
    //                'cookie' => new FieldSet('cookie', new Field('name', new Form('string', false), new IsString())),
    //                'body' => new Field('requestBody', new Passes()),
    //            ]
    //        ),
    //    ];
    //
    //    yield 'Request: identical param in header and query, no requestBody' => [
    //        new OpenAPIRequest(
    //            OpenAPIVersion::Version_3_0,
    //            new PathParameterExtractor('/requestpathtwo'),
    //            $noRefAPI->paths['/requestpathtwo'],
    //            Method::PUT
    //        ),
    //        new RequestProcessor(
    //            '',
    //            'requestpathtwo-put',
    //            Method::PUT,
    //            [
    //                'path' => new FieldSet(
    //                    'path',
    //                    new BeforeSet(
    //                        new PathMatcher(new PathParameterExtractor('/requestpathtwo'))
    //                    )
    //                ),
    //                'query' => new FieldSet(
    //                    'query',
    //                    new BeforeSet(new QueryStringToArray(['id' => ['style' => 'form', 'explode' => true]])),
    //                    new Field('id', new Form('integer', false), new IntString(), new ToInt())
    //                ),
    //                'header' => new FieldSet('header'),
    //                'cookie' => new FieldSet('cookie'),
    //                'body' => new Field('requestBody', new Passes()),
    //            ]
    //        ),
    //    ];
    //
    //    yield 'Request: same param in path and operation with different types, no requestBody' => [
    //        new OpenAPIRequest(
    //            OpenAPIVersion::Version_3_0,
    //            new PathParameterExtractor('/requestpathtwo'),
    //            $noRefAPI->paths['/requestpathtwo'],
    //            Method::DELETE
    //        ),
    //        new RequestProcessor(
    //            '',
    //            'requestpathtwo-delete',
    //            Method::DELETE,
    //            [
    //                'path' => new FieldSet(
    //                    'path',
    //                    new BeforeSet(
    //                        new PathMatcher(new PathParameterExtractor('/requestpathtwo'))
    //                    )
    //                ),
    //                'query' => new FieldSet('query', new BeforeSet(new QueryStringToArray([]))),
    //                'header' => new FieldSet('header', new Field('id', new Implode(','), new IsString())),
    //                'cookie' => new FieldSet('cookie'),
    //                'body' => new Field('requestBody', new Passes()),
    //            ]
    //        ),
    //    ];
    //
    //    yield 'Request: requestBody param' => [
    //        new OpenAPIRequest(
    //            OpenAPIVersion::Version_3_0,
    //            new PathParameterExtractor('/requestbodypath'),
    //            $noRefAPI->paths['/requestbodypath'],
    //            Method::GET
    //        ),
    //        new RequestProcessor(
    //            '',
    //            'requestbodypath-get',
    //            Method::GET,
    //            [
    //                'path' => new FieldSet(
    //                    'path',
    //                    new BeforeSet(
    //                        new PathMatcher(new PathParameterExtractor('/requestbodypath'))
    //                    )
    //                ),
    //                'query' => new FieldSet('query', new BeforeSet(new QueryStringToArray([]))),
    //                'header' => new FieldSet('header'),
    //                'cookie' => new FieldSet('cookie'),
    //                'body' => new Field('requestBody', new IsInt()),
    //            ]
    //        ),
    //    ];
    //
    //    yield 'Request: operation param in query, requestBody param' => [
    //        new OpenAPIRequest(
    //            OpenAPIVersion::Version_3_0,
    //            new PathParameterExtractor('/requestbodypath'),
    //            $noRefAPI->paths['/requestbodypath'],
    //            Method::POST
    //        ),
    //        new RequestProcessor(
    //            '',
    //            'requestbodypath-post',
    //            Method::POST,
    //            [
    //                'path' => new FieldSet(
    //                    'path',
    //                    new BeforeSet(
    //                        new PathMatcher(new PathParameterExtractor('/requestbodypath'))
    //                    )
    //                ),
    //                'query' => new FieldSet(
    //                    'query',
    //                    new BeforeSet(new QueryStringToArray(['id' => ['style' => 'form', 'explode' => true]])),
    //                    new Field('id', new Form('string', false), new IsString())
    //                ),
    //                'header' => new FieldSet('header'),
    //                'cookie' => new FieldSet('cookie'),
    //                'body' => new Field('requestBody', new IsInt()),
    //            ]
    //        ),
    //    ];
    //
    //    yield 'Request: path param in path, operation param in query, header, cookie, requestBody param' => [
    //        new OpenAPIRequest(
    //            OpenAPIVersion::Version_3_0,
    //            new PathParameterExtractor('/requestbodypath/{id}'),
    //            $noRefAPI->paths['/requestbodypath/{id}'],
    //            Method::GET
    //        ),
    //        new RequestProcessor(
    //            '',
    //            'requestbodypath-id-get',
    //            Method::GET,
    //            [
    //                'path' => new FieldSet(
    //                    'path',
    //                    new BeforeSet(
    //                        new PathMatcher(new PathParameterExtractor('/requestbodypath/{id}')),
    //                        new RequiredFields('id')
    //                    ),
    //                    new Field('id', new IntString(), new ToInt())
    //                ),
    //                'query' => new FieldSet(
    //                    'query',
    //                    new BeforeSet(new QueryStringToArray(['name' => ['style' => 'form', 'explode' => true]])),
    //                    new Field('name', new Form('string', false), new IsString())
    //                ),
    //                'header' => new FieldSet('header', new Field('species', new Implode(','), new IsString())),
    //                'cookie' => new FieldSet('cookie', new Field('subspecies', new Form('string', false), new IsString())),
    //                'body' => new Field('requestBody', new IsFloat()),
    //            ]
    //        ),
    //    ];
    //
    //    $complexQueryAPI = fn(string $xOf) => (new Reader([OpenAPIVersion::Version_3_0]))->readFromString(
    //        json_encode([
    //            'openapi' => '3.0.3',
    //            'info' => ['title' => 'Complex Query Parameter', 'version' => '1.0'],
    //            'paths' => [
    //                '/path' => [
    //                    'get' => [
    //                        'operationId' => 'getPath',
    //                        'parameters' => [
    //                            [
    //                                'name' => 'complexity',
    //                                'in' => 'query',
    //                                'schema' => [
    //                                    $xOf => [
    //                                        [
    //                                            'title' => 'Uno',
    //                                            'type' => 'boolean',
    //                                        ],
    //                                        [
    //                                            'type' => 'integer',
    //                                        ],
    //                                    ],
    //                                ],
    //                            ],
    //                        ],
    //                        'responses' => ['200' => ['description' => 'Successful Response']],
    //                    ],
    //                ],
    //            ],
    //        ]),
    //        FileFormat::Json
    //    );
    //
    //    $complexProcessor = fn($processor, $queryParameters = []) => new RequestProcessor(
    //        '',
    //        'getPath',
    //        Method::GET,
    //        [
    //            'path' => new FieldSet(
    //                'path',
    //                new BeforeSet(new PathMatcher(new PathParameterExtractor('/path')))
    //            ),
    //            'query' => new FieldSet(
    //                'query',
    //                new BeforeSet(new QueryStringToArray($queryParameters)),
    //                $processor
    //            ),
    //            'header' => new FieldSet('header'),
    //            'cookie' => new FieldSet('cookie'),
    //            'body' => new Field('requestBody', new Passes()),
    //        ]
    //    );
    //
    //    yield 'query parameter with oneOf' => [
    //        new OpenAPIRequest(
    //            OpenAPIVersion::Version_3_0,
    //            new PathParameterExtractor('/path'),
    //            $complexQueryAPI('oneOf')->paths['/path'],
    //            Method::GET
    //        ),
    //        $complexProcessor(
    //            new OneOf(
    //                'complexity',
    //                new Field('Uno', new Form('boolean', false), new BoolString(), new ToBool()),
    //                new Field('Branch-2', new Form('integer', false), new IntString(), new ToInt())
    //            ),
    //            ['complexity' => ['style' => 'form', 'explode' => true]]
    //        ),
    //    ];
    //
    //    yield 'query parameter with anyOf' => [
    //        new OpenAPIRequest(
    //            OpenAPIVersion::Version_3_0,
    //            new PathParameterExtractor('/path'),
    //            $complexQueryAPI('anyOf')->paths['/path'],
    //            Method::GET
    //        ),
    //        $complexProcessor(
    //            new AnyOf(
    //                'complexity',
    //                new Field('Uno', new Form('boolean', false), new BoolString(), new ToBool()),
    //                new Field('Branch-2', new Form('integer', false), new IntString(), new ToInt())
    //            ),
    //            ['complexity' => ['style' => 'form', 'explode' => true]]
    //        ),
    //    ];
    //
    //    yield 'query parameter with allOf' => [
    //        new OpenAPIRequest(
    //            OpenAPIVersion::Version_3_0,
    //            new PathParameterExtractor('/path'),
    //            $complexQueryAPI('allOf')->paths['/path'],
    //            Method::GET
    //        ),
    //        $complexProcessor(
    //            new AllOf(
    //                'complexity',
    //                new Field('Uno', new Form('boolean', false), new BoolString(), new ToBool()),
    //                new Field('Branch-2', new Form('integer', false), new IntString(), new ToInt())
    //            ),
    //            ['complexity' => ['style' => 'form', 'explode' => true]]
    //        ),
    //    ];
    //}

    public static function dataSetsForDocExamples(): array
    {
        $petstoreApi = (new MembraneReader([OpenAPIVersion::Version_3_0]))
            ->readFromAbsoluteFilePath(self::FIXTURES . '/docs/petstore.yaml');

        $petstoreExpandedApi = (new MembraneReader([OpenAPIVersion::Version_3_0]))
            ->readFromAbsoluteFilePath(self::FIXTURES . '/docs/petstore-expanded.json');

        return [
            'petstore /pets get, minimal (valid)' => [
                new OpenAPIRequest(
                    OpenAPIVersion::Version_3_0,
                    new PathParameterExtractor('/pets'),
                    $petstoreApi->paths['/pets'],
                    Method::GET
                ),
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
                new OpenAPIRequest(
                    OpenAPIVersion::Version_3_0,
                    new PathParameterExtractor('/pets/{petId}'),
                    $petstoreApi->paths['/pets/{petId}'],
                    Method::GET
                ),
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
                new OpenAPIRequest(
                    OpenAPIVersion::Version_3_0,
                    new PathParameterExtractor('/pets'),
                    $petstoreExpandedApi->paths['/pets'],
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
                new OpenAPIRequest(
                    OpenAPIVersion::Version_3_0,
                    new PathParameterExtractor('/pets'),
                    $petstoreExpandedApi->paths['/pets'],
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

    /** @return Generator<array{
     *     0: OpenAPIRequest,
     *     1: array | ServerRequestInterface,
     *     2: Result,
     *  }>
     */
    public static function provideAPIWithPathParameters(): Generator
    {
        $dataSet = fn(
            Partial\Operation $operation,
            string $path,
            string $input,
            array $output,
        ) => [
            new OpenAPIRequest(
                OpenAPIVersion::Version_3_0,
                new PathParameterExtractor($path),
                V30\PathItem::fromPartial(
                    new Identifier('test-path'),
                    [],
                    new Partial\PathItem(path: '/path', get: $operation),
                ),
                Method::GET
            ),
            new ServerRequest('get', $input),
            Result::valid([
                'request' => ['method' => 'get', 'operationId' => 'test-op'],
                'path' => $output,
                'query' => [],
                'header' => [],
                'cookie' => [],
                'body' => '',
            ]),
        ];

        yield 'bool path parameter (style:simple, explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'simple',
                    explode: false,
                    schema: new Partial\Schema(type: 'boolean'),
                )]
            ),
            '/path/{colour}',
            '/path/true',
            ['colour' => true]
        );

        yield 'boolean path parameter (style:simple, explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'simple',
                    explode: true,
                    schema: new Partial\Schema(type: 'boolean'),
                )]
            ),
            '/path/{colour}',
            '/path/false',
            ['colour' => false]
        );

        yield 'boolean path parameter (style:matrix, explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'matrix',
                    explode: false,
                    schema: new Partial\Schema(type: 'boolean'),
                )]
            ),
            '/path/{colour}',
            '/path/;colour=true',
            ['colour' => true]
        );

        yield 'boolean path parameter (style:matrix, explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'matrix',
                    explode: true,
                    schema: new Partial\Schema(type: 'boolean'),
                )]
            ),
            '/path/{colour}',
            '/path/;colour=false',
            ['colour' => false]
        );

        yield 'boolean path parameter (style:label, explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'label',
                    explode: false,
                    schema: new Partial\Schema(type: 'boolean'),
                )]
            ),
            '/path/{colour}',
            '/path/.true',
            ['colour' => true]
        );

        yield 'boolean path parameter (style:label, explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'label',
                    explode: true,
                    schema: new Partial\Schema(type: 'boolean'),
                )]
            ),
            '/path/{colour}',
            '/path/.false',
            ['colour' => false]
        );

        yield 'string path parameter (style:simple, explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'simple',
                    explode: false,
                    schema: new Partial\Schema(type: 'string'),
                )]
            ),
            '/path/{colour}',
            '/path/blue',
            ['colour' => 'blue']
        );

        yield 'string path parameter (style:simple, explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'simple',
                    explode: true,
                    schema: new Partial\Schema(type: 'string'),
                )]
            ),
            '/path/{colour}',
            '/path/blue',
            ['colour' => 'blue']
        );

        yield 'string path parameter (style:matrix, explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'matrix',
                    explode: false,
                    schema: new Partial\Schema(type: 'string'),
                )]
            ),
            '/path/{colour}',
            '/path/;colour=blue',
            ['colour' => 'blue']
        );

        yield 'string path parameter (style:matrix, explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'matrix',
                    explode: true,
                    schema: new Partial\Schema(type: 'string'),
                )]
            ),
            '/path/{colour}',
            '/path/;colour=blue',
            ['colour' => 'blue']
        );

        yield 'string path parameter (style:label, explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'label',
                    explode: false,
                    schema: new Partial\Schema(type: 'string'),
                )]
            ),
            '/path/{colour}',
            '/path/.blue',
            ['colour' => 'blue']
        );

        yield 'string path parameter (style:label, explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'label',
                    explode: true,
                    schema: new Partial\Schema(type: 'string'),
                )]
            ),
            '/path/{colour}',
            '/path/.blue',
            ['colour' => 'blue']
        );

        yield 'int path parameter (style:simple, explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'simple',
                    explode: false,
                    schema: new Partial\Schema(type: 'integer'),
                )]
            ),
            '/path/{colour}',
            '/path/255',
            ['colour' => 255]
        );

        yield 'int path parameter (style:simple, explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'simple',
                    explode: true,
                    schema: new Partial\Schema(type: 'integer'),
                )]
            ),
            '/path/{colour}',
            '/path/255',
            ['colour' => 255]
        );

        yield 'int path parameter (style:matrix, explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'matrix',
                    explode: false,
                    schema: new Partial\Schema(type: 'integer'),
                )]
            ),
            '/path/{colour}',
            '/path/;colour=255',
            ['colour' => 255]
        );

        yield 'int path parameter (style:matrix, explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'matrix',
                    explode: true,
                    schema: new Partial\Schema(type: 'integer'),
                )]
            ),
            '/path/{colour}',
            '/path/;colour=255',
            ['colour' => 255]
        );

        yield 'int path parameter (style:label, explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'label',
                    explode: false,
                    schema: new Partial\Schema(type: 'integer'),
                )]
            ),
            '/path/{colour}',
            '/path/.255',
            ['colour' => 255]
        );

        yield 'int path parameter (style:label, explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'label',
                    explode: true,
                    schema: new Partial\Schema(type: 'integer'),
                )]
            ),
            '/path/{colour}',
            '/path/.255',
            ['colour' => 255]
        );

        yield 'number path parameter (style:simple, explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'simple',
                    explode: false,
                    schema: new Partial\Schema(type: 'number'),
                )]
            ),
            '/path/{colour}',
            '/path/255',
            ['colour' => 255]
        );

        yield 'number path parameter (style:simple, explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'simple',
                    explode: true,
                    schema: new Partial\Schema(type: 'number'),
                )]
            ),
            '/path/{colour}',
            '/path/255',
            ['colour' => 255]
        );

        yield 'number path parameter (style:matrix, explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'matrix',
                    explode: false,
                    schema: new Partial\Schema(type: 'number'),
                )]
            ),
            '/path/{colour}',
            '/path/;colour=255',
            ['colour' => 255]
        );

        yield 'number path parameter (style:matrix, explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'matrix',
                    explode: true,
                    schema: new Partial\Schema(type: 'number'),
                )]
            ),
            '/path/{colour}',
            '/path/;colour=255',
            ['colour' => 255]
        );

        yield 'number path parameter (style:label, explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'label',
                    explode: false,
                    schema: new Partial\Schema(type: 'number'),
                )]
            ),
            '/path/{colour}',
            '/path/.255',
            ['colour' => 255]
        );

        yield 'number path parameter (style:label, explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'label',
                    explode: true,
                    schema: new Partial\Schema(type: 'number'),
                )]
            ),
            '/path/{colour}',
            '/path/.255',
            ['colour' => 255]
        );

        yield 'array path parameter with int items (style:simple, explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'simple',
                    explode: false,
                    schema: new Partial\Schema(type: 'array', items: new Partial\Schema(type: 'integer')),
                )]
            ),
            '/path/{colour}',
            '/path/100,200,150',
            ['colour' => [100, 200, 150]]
        );

        yield 'array path parameter with int items (style:simple, explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'simple',
                    explode: true,
                    schema: new Partial\Schema(type: 'array', items: new Partial\Schema(type: 'integer')),
                )]
            ),
            '/path/{colour}',
            '/path/100,200,150',
            ['colour' => [100, 200, 150]]
        );

        yield 'array path parameter with int items (style:matrix, explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'matrix',
                    explode: false,
                    schema: new Partial\Schema(type: 'array', items: new Partial\Schema(type: 'integer')),
                )]
            ),
            '/path/{colour}',
            '/path/;color=100,200,150',
            ['colour' => [100, 200, 150]]
        );

        yield 'array path parameter with int items (style:matrix, explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'matrix',
                    explode: true,
                    schema: new Partial\Schema(type: 'array', items: new Partial\Schema(type: 'integer')),
                )]
            ),
            '/path/{colour}',
            '/path/;color=100;color=200;color=150',
            ['colour' => [100, 200, 150]]
        );

        yield 'array path parameter with int items (style:label, explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'label',
                    explode: false,
                    schema: new Partial\Schema(type: 'array', items: new Partial\Schema(type: 'integer')),
                )]
            ),
            '/path/{colour}',
            '/path/.100,200,150',
            ['colour' => [100, 200, 150]]
        );

        yield 'array path parameter with int items (style:label, explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'label',
                    explode: true,
                    schema: new Partial\Schema(type: 'array', items: new Partial\Schema(type: 'integer')),
                )]
            ),
            '/path/{colour}',
            '/path/.100.200.150',
            ['colour' => [100, 200, 150]]
        );

        yield 'array path parameter with string items (style:matrix, explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'matrix',
                    explode: false,
                    schema: new Partial\Schema(type: 'array', items: new Partial\Schema(type: 'string')),
                )]
            ),
            '/path/{colour}',
            '/path/;color=blue,black,brown',
            ['colour' => ['blue', 'black', 'brown']]
        );

        yield 'array path parameter with string items (style:matrix, explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'matrix',
                    explode: true,
                    schema: new Partial\Schema(type: 'array', items: new Partial\Schema(type: 'string')),
                )]
            ),
            '/path/{colour}',
            '/path/;color=blue;color=black;color=brown',
            ['colour' => ['blue', 'black', 'brown']]
        );

        yield 'array path parameter with string items (style:label, explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'label',
                    explode: false,
                    schema: new Partial\Schema(type: 'array', items: new Partial\Schema(type: 'string')),
                )]
            ),
            '/path/{colour}',
            '/path/.blue,black,brown',
            ['colour' => ['blue', 'black', 'brown']]
        );

        yield 'array path parameter with string items (style:label, explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'label',
                    explode: true,
                    schema: new Partial\Schema(type: 'array', items: new Partial\Schema(type: 'string')),
                )]
            ),
            '/path/{colour}',
            '/path/.blue.black.brown',
            ['colour' => ['blue', 'black', 'brown']]
        );

        yield 'object path parameter with int additionalProperties (style:simple, explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'simple',
                    explode: false,
                    schema: new Partial\Schema(type: 'object', additionalProperties: new Partial\Schema(type: 'integer')),
                )]
            ),
            '/path/{colour}',
            '/path/R,100,G,200,B,150',
            ['colour' => ['R' => 100, 'G' => 200, 'B' => 150]]
        );

        yield 'object path parameter with int additionalProperties (style:simple, explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'simple',
                    explode: true,
                    schema: new Partial\Schema(type: 'object', additionalProperties: new Partial\Schema(type: 'integer')),
                )]
            ),
            '/path/{colour}',
            '/path/R=100,G=200,B=150',
            ['colour' => ['R' => 100, 'G' => 200, 'B' => 150]]
        );

        yield 'object path parameter with int additionalProperties (style:matrix, explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'matrix',
                    explode: false,
                    schema: new Partial\Schema(type: 'object', additionalProperties: new Partial\Schema(type: 'integer')),
                )]
            ),
            '/path/{colour}',
            '/path/;color=R,100,G,200,B,150',
            ['colour' => ['R' => 100, 'G' => 200, 'B' => 150]]
        );

        yield 'object path parameter with int additionalProperties (style:matrix, explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'matrix',
                    explode: true,
                    schema: new Partial\Schema(type: 'object', additionalProperties: new Partial\Schema(type: 'integer')),
                )]
            ),
            '/path/{colour}',
            '/path/;R=100;G=200;B=150',
            ['colour' => ['R' => 100, 'G' => 200, 'B' => 150]]
        );

        yield 'object path parameter with int additionalProperties (style:label, explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'label',
                    explode: false,
                    schema: new Partial\Schema(type: 'object', additionalProperties: new Partial\Schema(type: 'integer')),
                )]
            ),
            '/path/{colour}',
            '/path/.R,100,G,200,B,150',
            ['colour' => ['R' => 100, 'G' => 200, 'B' => 150]]
        );

        yield 'object path parameter with int additionalProperties (style:label, explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'path',
                    required: true,
                    style: 'label',
                    explode: true,
                    schema: new Partial\Schema(type: 'object', additionalProperties: new Partial\Schema(type: 'integer')),
                )]
            ),
            '/path/{colour}',
            '/path/.R=100.G=200.B=150',
            ['colour' => ['R' => 100, 'G' => 200, 'B' => 150]]
        );
    }

    /** @return Generator<array{
     *     0: OpenAPIRequest,
     *     1: array | ServerRequestInterface,
     *     2: Result,
     *  }>
     */
    public static function provideAPIWithQueryParameters(): Generator
    {
        $dataSet = fn(
            Partial\Operation $operation,
            string $input,
            array $output,
        ) => [
            new OpenAPIRequest(
                OpenAPIVersion::Version_3_0,
                new PathParameterExtractor('/pets'),
                V30\PathItem::fromPartial(
                    new Identifier('test-path'),
                    [],
                    new Partial\PathItem(path: '/path', get: $operation),
                ),
                Method::GET
            ),
            new ServerRequest('get', '/pets?' . $input),
            Result::valid([
                'request' => ['method' => 'get', 'operationId' => 'test-op'],
                'path' => [],
                'query' => $output,
                'header' => [],
                'cookie' => [],
                'body' => '',
            ]),
        ];

        yield 'type:string, style:form, explode:false' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'query',
                    required: true,
                    style: 'form',
                    explode: false,
                    schema: new Partial\Schema(type: 'string'),
                )]
            ),
            'colour=blue',
            ['colour' => 'blue'],
        );

        yield 'type:string, style:form, explode:true' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'query',
                    required: true,
                    style: 'form',
                    explode: true,
                    schema: new Partial\Schema(type: 'string'),
                )]
            ),
            'colour=blue',
            ['colour' => 'blue'],
        );

        yield 'type:boolean, style:form, explode:false' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'query',
                    required: true,
                    style: 'form',
                    explode: false,
                    schema: new Partial\Schema(type: 'boolean'),
                )]
            ),
            'colour=true',
            ['colour' => true],
        );

        yield 'type:boolean, style:form, explode:true' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'query',
                    required: true,
                    style: 'form',
                    explode: true,
                    schema: new Partial\Schema(type: 'boolean'),
                )]
            ),
            'colour=true',
            ['colour' => true],
        );

        yield 'type:integer, style:form, explode:false' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'id',
                    in: 'query',
                    required: true,
                    style: 'form',
                    explode: false,
                    schema: new Partial\Schema(type: 'integer'),
                )]
            ),
            'id=5',
            ['id' => 5],
        );

        yield 'type:integer, style:form, explode:true' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'id',
                    in: 'query',
                    required: true,
                    style: 'form',
                    explode: true,
                    schema: new Partial\Schema(type: 'integer'),
                )]
            ),
            'id=5',
            ['id' => 5],
        );

        yield 'type:array, style:form, explode:false' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'query',
                    required: true,
                    style: 'form',
                    explode: false,
                    schema: new Partial\Schema(type: 'array', items: new Partial\Schema(type: 'string')),
                )]
            ),
            'colour=blue,black,brown',
            ['colour' => ['blue', 'black', 'brown']],
        );

        yield 'type:array, style:form, explode:true' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'query',
                    required: true,
                    style: 'form',
                    explode: true,
                    schema: new Partial\Schema(type: 'array', items: new Partial\Schema(type: 'string')),
                )]
            ),
            'colour=blue&colour=black&colour=brown',
            ['colour' => ['blue', 'black', 'brown']],
        );

        yield 'type:object, style:form, explode:false' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'query',
                    required: true,
                    style: 'form',
                    explode: false,
                    schema: new Partial\Schema(type: 'object', additionalProperties: new Partial\Schema(type: 'integer')),
                )]
            ),
            'colour=R,100,G,200,B,150',
            ['colour' => ['R' => 100, 'G' => 200, 'B' => 150]],
        );

        yield 'type:object, style:form, explode:true' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'query',
                    required: true,
                    style: 'form',
                    explode: true,
                    schema: new Partial\Schema(
                        type: 'object',
                        additionalProperties:  new Partial\Schema(type: 'integer'),
                    ),
                )]
            ),
            'R=100&G=200&B=150',
            ['colour' => ['R' => 100, 'G' => 200, 'B' => 150]],
        );

        yield 'type:array, style:spaceDelimited, explode:false' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'query',
                    required: true,
                    style: 'spaceDelimited',
                    explode: false,
                    schema: new Partial\Schema(type: 'array', items: new Partial\Schema(type: 'string')),
                )]
            ),
            'colour=blue%20black%20brown',
            ['colour' => ['blue', 'black', 'brown']],
        );

        yield 'type:object, style:spaceDelimited, explode:false' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'query',
                    required: true,
                    style: 'spaceDelimited',
                    explode: false,
                    schema: new Partial\Schema(type: 'object', additionalProperties: new Partial\Schema(type: 'integer')),
                )]
            ),
            'colour=R%20100%20G%20200%20B%20150',
            ['colour' => ['R' => 100, 'G' => 200, 'B' => 150]],
        );

        yield 'type:array, style:pipeDelimited, explode:false' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'query',
                    required: true,
                    style: 'pipeDelimited',
                    explode: false,
                    schema: new Partial\Schema(type: 'array', items: new Partial\Schema(type: 'string')),
                )]
            ),
            'colour=blue|black|brown',
            ['colour' => ['blue', 'black', 'brown']],
        );

        yield 'type:object, style:pipeDelimited, explode:false' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'query',
                    required: true,
                    style: 'pipeDelimited',
                    explode: false,
                    schema: new Partial\Schema(type: 'object', additionalProperties: new Partial\Schema(type: 'integer')),
                )]
            ),
            'colour=R|100|G|200|B|150',
            ['colour' => ['R' => 100, 'G' => 200, 'B' => 150]],
        );

        yield 'type:object, style:deepObject, explode:true' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'query',
                    required: true,
                    style: 'deepObject',
                    explode: true,
                    schema: new Partial\Schema(type: 'object', additionalProperties: new Partial\Schema(type: 'integer')),
                )]
            ),
            'colour[R]=100&colour[G]=200&colour[B]=150',
            ['colour' => ['R' => 100, 'G' => 200, 'B' => 150]],
        );
    }

    /** @return Generator<array{
     *     0: OpenAPIRequest,
     *     1: array | ServerRequestInterface,
     *     2: Result,
     *  }>
     */
    public static function provideAPIWithHeaderParameters(): Generator
    {
        $dataSet = fn(
            Partial\Operation $operation,
            array $requestHeaders,
            array $resultHeaders
        ) => [
            new OpenAPIRequest(
                OpenAPIVersion::Version_3_0,
                new PathParameterExtractor('/path'),
                V30\PathItem::fromPartial(
                    new Identifier('test-path'),
                    [],
                    new Partial\PathItem(path: '/path', get: $operation)
                ),
                Method::GET
            ),
            new ServerRequest('get', '/path', $requestHeaders),
            Result::valid([
                'request' => ['method' => 'get', 'operationId' => 'test-op'],
                'path' => [],
                'query' => [],
                'header' => $resultHeaders,
                'cookie' => [],
                'body' => '',
            ]),
        ];

        yield 'string header (explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'header',
                    required: true,
                    explode: false,
                    schema: new Partial\Schema(type: 'string'),
                )]
            ),
            ['colour' => 'blue'],
            ['colour' => 'blue'],
        );

        yield 'string header (explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'header',
                    required: true,
                    explode: true,
                    schema: new Partial\Schema(type: 'string'),
                )]
            ),
            ['colour' => 'blue'],
            ['colour' => 'blue'],
        );

        yield 'integer header (explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'header',
                    required: true,
                    explode: false,
                    schema: new Partial\Schema(type: 'integer'),
                )]
            ),
            ['colour' => '255'],
            ['colour' => 255],
        );

        yield 'integer header (explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'header',
                    required: true,
                    explode: true,
                    schema: new Partial\Schema(type: 'integer'),
                )]
            ),
            ['colour' => '255'],
            ['colour' => 255],
        );

        yield 'boolean header (explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'header',
                    required: true,
                    explode: false,
                    schema: new Partial\Schema(type: 'boolean'),
                )]
            ),
            ['colour' => 'true'],
            ['colour' => true],
        );

        yield 'boolean header (explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'header',
                    required: true,
                    explode: true,
                    schema: new Partial\Schema(type: 'boolean'),
                )]
            ),
            ['colour' => 'true'],
            ['colour' => true],
        );

        yield 'string array header (explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'header',
                    required: true,
                    explode: false,
                    schema: new Partial\Schema(type: 'array', items: new Partial\Schema(type: 'string')),
                )]
            ),
            ['colour' => 'blue,black,brown'],
            ['colour' => ['blue', 'black', 'brown']],
        );

        yield 'string array header (explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'header',
                    required: true,
                    explode: true,
                    schema: new Partial\Schema(type: 'array', items: new Partial\Schema(type: 'string')),
                )]
            ),
            ['colour' => 'blue,black,brown'],
            ['colour' => ['blue', 'black', 'brown']],
        );

        yield 'int array header (explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'header',
                    required: true,
                    explode: false,
                    schema: new Partial\Schema(type: 'array', items: new Partial\Schema(type: 'integer')),
                )]
            ),
            ['colour' => '100,200,150'],
            ['colour' => [100, 200, 150]],
        );

        yield 'int array header (explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'header',
                    required: true,
                    explode: true,
                    schema: new Partial\Schema(type: 'array', items: new Partial\Schema(type: 'integer')),
                )]
            ),
            ['colour' => '100,200,150'],
            ['colour' => [100, 200, 150]],
        );

        yield 'object header with additional int properties (explode:false)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'header',
                    required: true,
                    explode: false,
                    schema: new Partial\Schema(type: 'object', additionalProperties: new Partial\Schema(type: 'integer')),
                )]
            ),
            ['colour' => 'R,100,G,200,B,150'],
            ['colour' => ['R' => 100, 'G' => 200, 'B' => 150]],
        );

        yield 'object header additional int properties (explode:true)' => $dataSet(
            new Partial\Operation(
                operationId: 'test-op',
                parameters: [new Partial\Parameter(
                    name: 'colour',
                    in: 'header',
                    required: true,
                    explode: true,
                    schema: new Partial\Schema(type: 'object', additionalProperties: new Partial\Schema(type: 'integer')),
                )]
            ),
            ['colour' => 'R=100,G=200,B=150'],
            ['colour' => ['R' => 100, 'G' => 200, 'B' => 150]],
        );
    }

}
