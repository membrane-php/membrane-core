<?php

declare(strict_types=1);

namespace OpenAPI\Builder;

use Exception;
use GuzzleHttp\Psr7\ServerRequest;
use Membrane\Builder\Specification;
use Membrane\Filter\Type\ToInt;
use Membrane\OpenAPI\Builder\RequestBuilder;
use Membrane\OpenAPI\Filter\HTTPParameters;
use Membrane\OpenAPI\Filter\PathMatcher;
use Membrane\OpenAPI\Method;
use Membrane\OpenAPI\PathMatcher as PathMatcherClass;
use Membrane\OpenAPI\Processor\Json;
use Membrane\OpenAPI\Processor\Request as RequestProcessor;
use Membrane\OpenAPI\Specification\Request;
use Membrane\OpenAPI\Specification\Response;
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
use Membrane\Validator\Type\IsFloat;
use Membrane\Validator\Type\IsInt;
use Membrane\Validator\Type\IsList;
use Membrane\Validator\Type\IsString;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers    \Membrane\OpenAPI\Builder\RequestBuilder
 * @covers    \Membrane\OpenAPI\Builder\APIBuilder
 * @uses      \Membrane\OpenAPI\Builder\Arrays
 * @uses      \Membrane\OpenAPI\Builder\Numeric
 * @uses      \Membrane\OpenAPI\Builder\Strings
 * @uses      \Membrane\OpenAPI\Filter\HTTPParameters
 * @uses      \Membrane\OpenAPI\Filter\PathMatcher
 * @uses      \Membrane\OpenAPI\PathMatcher
 * @uses      \Membrane\OpenAPI\Processor\Json
 * @uses      \Membrane\OpenAPI\Processor\Request
 * @uses      \Membrane\OpenAPI\Specification\APISchema
 * @uses      \Membrane\OpenAPI\Specification\APISpec
 * @uses      \Membrane\OpenAPI\Specification\Arrays
 * @uses      \Membrane\OpenAPI\Specification\Numeric
 * @uses      \Membrane\OpenAPI\Specification\Strings
 * @uses      \Membrane\OpenAPI\Specification\Request
 * @uses      \Membrane\Filter\Type\ToInt
 * @uses      \Membrane\Processor\BeforeSet
 * @uses      \Membrane\Processor\Collection
 * @uses      \Membrane\Processor\Field
 * @uses      \Membrane\Processor\FieldSet
 * @uses      \Membrane\Result\FieldName
 * @uses      \Membrane\Result\Message
 * @uses      \Membrane\Result\MessageSet
 * @uses      \Membrane\Result\Result
 * @uses      \Membrane\Validator\FieldSet\RequiredFields
 * @uses      \Membrane\Validator\Numeric\Maximum
 * @uses      \Membrane\Validator\Type\IsInt
 * @uses      \Membrane\Validator\Type\IsList
 * @uses      \Membrane\Validator\Type\IsString
 * @uses      \Membrane\Validator\Utility\Passes
 */
class RequestBuilderTest extends TestCase
{
    public const DIR = __DIR__ . '/../../fixtures/OpenAPI/';

    /** @test */
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

    /** @test */
    public function throwsExceptionIfParameterHasNoSchemaNorContent(): void
    {
        $specification = new Request(self::DIR . 'noReferences.json', '/requestpathexceptions', Method::GET);
        $sut = new RequestBuilder();

        self::expectExceptionObject(
            new Exception('A parameter MUST contain either a schema property, or a content property, but not both')
        );

        $sut->build($specification);
    }

    public function dataSetsforSupports(): array
    {
        return [
            [
                new class() implements Specification {
                },
                false,
            ],
            [
                self::createStub(Request::class),
                true,
            ],
            [
                self::createStub(Response::class),
                false,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsforSupports
     */
    public function supportsTest(Specification $spec, bool $expected): void
    {
        $sut = new RequestBuilder();

        $supported = $sut->supports($spec);

        self::assertSame($expected, $supported);
    }

    public function dataSetsForBuild(): array
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
                new Request(self::DIR . 'noReferences.json', '/requestpathone/{id}', Method::POST),
                new RequestProcessor(
                    '',
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
                    '/requestpathone/{id}',
                    Method::PUT
                ),
                new RequestProcessor(
                    '',
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
                    '/requestpathone/{id}',
                    Method::DELETE
                ),
                new RequestProcessor(
                    '',
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
                    '/requestpathtwo',
                    Method::GET
                ),
                new RequestProcessor(
                    '',
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
                new Request(self::DIR . 'noReferences.json', '/requestpathtwo', Method::POST),
                new RequestProcessor(
                    '',
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
                    '/requestpathtwo',
                    Method::PUT
                ),
                new RequestProcessor(
                    '',
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
                    '/requestpathtwo',
                    Method::DELETE
                ),
                new RequestProcessor(
                    '',
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
                    '/requestbodypath',
                    Method::GET
                ),
                new RequestProcessor(
                    '',
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
                    '/requestbodypath',
                    Method::POST
                ),
                new RequestProcessor(
                    '',
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
                new Request(self::DIR . 'noReferences.json', '/requestbodypath/{id}', Method::GET),
                new RequestProcessor(
                    '',
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

    /**
     * @test
     * @dataProvider dataSetsForBuild
     */
    public function buildTest(Specification $spec, Processor $expected): void
    {
        $sut = new RequestBuilder();

        $actual = $sut->build($spec);

        self::assertEquals($expected, $actual);
    }


    public function dataSetsForDocExamples(): array
    {
        $api = self::DIR . '/docs/petstore.yaml';
        $expanded = self::DIR . '/docs/petstore-expanded.json';

        return [
            'petstore /pets get, minimal (valid)' => [
                new Request($api, 'http://petstore.swagger.io/v1/pets', Method::GET),
                new ServerRequest('get', 'http://petstore.swagger.io/v1/pets'),
                Result::valid(
                    [
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

    /**
     * @test
     * @dataProvider dataSetsForDocExamples
     */
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
