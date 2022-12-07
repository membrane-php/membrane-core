<?php

declare(strict_types=1);

namespace OpenAPI\Builder;

use Exception;
use Membrane\Builder\Specification;
use Membrane\OpenAPI\Builder\ResponseBuilder;
use Membrane\OpenAPI\Method;
use Membrane\OpenAPI\Processor\AllOf;
use Membrane\OpenAPI\Processor\AnyOf;
use Membrane\OpenAPI\Processor\OneOf;
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
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\Collection\Count;
use Membrane\Validator\Collection\Unique;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\Numeric\Maximum;
use Membrane\Validator\Numeric\Minimum;
use Membrane\Validator\Numeric\MultipleOf;
use Membrane\Validator\String\DateString;
use Membrane\Validator\String\Length;
use Membrane\Validator\String\Regex;
use Membrane\Validator\Type\IsArray;
use Membrane\Validator\Type\IsBool;
use Membrane\Validator\Type\IsFloat;
use Membrane\Validator\Type\IsInt;
use Membrane\Validator\Type\IsList;
use Membrane\Validator\Type\IsNull;
use Membrane\Validator\Type\IsNumber;
use Membrane\Validator\Type\IsString;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\TestCase;

/**
 * @covers   \Membrane\OpenAPI\Builder\ResponseBuilder
 * @covers   \Membrane\OpenAPI\Builder\APIBuilder
 * @uses     \Membrane\OpenAPI\Builder\Arrays
 * @uses     \Membrane\OpenAPI\Builder\TrueFalse
 * @uses     \Membrane\OpenAPI\Builder\Numeric
 * @uses     \Membrane\OpenAPI\Builder\Objects
 * @uses     \Membrane\OpenAPI\Builder\Strings
 * @uses     \Membrane\OpenAPI\PathMatcher
 * @uses     \Membrane\OpenAPI\Processor\AllOf
 * @uses     \Membrane\OpenAPI\Processor\AnyOf
 * @uses     \Membrane\OpenAPI\Processor\OneOf
 * @uses     \Membrane\OpenAPI\Specification\APISchema
 * @uses     \Membrane\OpenAPI\Specification\APISpec
 * @uses     \Membrane\OpenAPI\Specification\Arrays
 * @uses     \Membrane\OpenAPI\Specification\TrueFalse
 * @uses     \Membrane\OpenAPI\Specification\Numeric
 * @uses     \Membrane\OpenAPI\Specification\Objects
 * @uses     \Membrane\OpenAPI\Specification\Strings
 * @uses     \Membrane\OpenAPI\Specification\Response
 * @uses     \Membrane\Processor\BeforeSet
 * @uses     \Membrane\Processor\Collection
 * @uses     \Membrane\Processor\Field
 * @uses     \Membrane\Processor\FieldSet
 * @uses     \Membrane\Result\FieldName
 * @uses     \Membrane\Result\Message
 * @uses     \Membrane\Result\MessageSet
 * @uses     \Membrane\Result\Result
 * @uses     \Membrane\Validator\Collection\Contained
 * @uses     \Membrane\Validator\Collection\Count
 * @uses     \Membrane\Validator\Collection\Unique
 * @uses     \Membrane\Validator\FieldSet\RequiredFields
 * @uses     \Membrane\Validator\Numeric\Maximum
 * @uses     \Membrane\Validator\Numeric\Minimum
 * @uses     \Membrane\Validator\Numeric\MultipleOf
 * @uses     \Membrane\Validator\String\DateString
 * @uses     \Membrane\Validator\String\Length
 * @uses     \Membrane\Validator\String\Regex
 * @uses     \Membrane\Validator\Type\IsArray
 * @uses     \Membrane\Validator\Type\IsInt
 * @uses     \Membrane\Validator\Type\IsList
 * @uses     \Membrane\Validator\Type\IsString
 */
class ResponseBuilderTest extends TestCase
{
    public const DIR = __DIR__ . '/../../fixtures/OpenAPI/';

    /** @test */
    public function throwsExceptionIfNotIsFound(): void
    {
        $sut = new ResponseBuilder();
        $response = new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '360');

        self::expectExceptionObject(new Exception("Keyword 'not' is currently unsupported"));

        $sut->build($response);
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
                false,
            ],
            [
                self::createStub(Response::class),
                true,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsforSupports
     */
    public function supportsTest(Specification $spec, bool $expected): void
    {
        $sut = new ResponseBuilder();

        $supported = $sut->supports($spec);

        self::assertSame($expected, $supported);
    }

    public function dataSetsforBuilds(): array
    {
        return [
            'no properties' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/path',
                    Method::GET,
                    '200'
                ),
                new Field('', new Passes()),
            ],
            'int' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '200'
                ),
                new Field('', new IsInt()),
            ],
            'nullable int' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '201'
                ),
                new AnyOf('', new Field('', new IsNull()), new Field('', new IsInt())),
            ],
            'int, inclusive min' => [
                new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '202'),
                new Field('', new IsInt(), new Minimum(0)),
            ],
            'int, exclusive min' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '203',
                ),
                new Field('', new IsInt(), new Minimum(0, true)),
            ],
            'int, inclusive max' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '204',
                ),
                new Field('', new IsInt(), new Maximum(100)),
            ],
            'int, exclusive max' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '205',
                ),
                new Field('', new IsInt(), new Maximum(100, true)),
            ],
            'int, multipleOf' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '206',
                ),
                new Field('', new IsInt(), new MultipleOf(3)),
            ],
            'int, enum' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '207',
                ),
                new Field('', new IsInt(), new Contained([1, 2, 3])),
            ],
            'nullable int, enum, exclusive min, inclusive max, multipleOf' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '209',
                ),
                new AnyOf(
                    '',
                    new Field('', new IsNull()),
                    new Field(
                        '',
                        new IsInt(),
                        new Contained([1, 2, 3]),
                        new Maximum(100),
                        new Minimum(0, true),
                        new MultipleOf(3)

                    )
                ),
            ],
            'number' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '210',
                ),
                new Field('', new IsNumber()),
            ],
            'nullable number' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '211',
                ),
                new AnyOf('', new Field('', new IsNull()), new Field('', new IsNumber())),
            ],
            'number, enum' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '212',
                ),
                new Field('', new IsNumber(), new Contained([1, 2.3, 4])),
            ],
            'number, float format' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '213'
                ),
                new Field('', new IsFloat()),
            ],
            'nullable number, float format' => [
                new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '214'),
                new AnyOf('', new Field('', new IsNull()), new Field('', new IsFloat())),
            ],
            'number, double format' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '215'
                ),
                new Field('', new IsFloat()),
            ],
            'nullable number, enum, inclusive min, exclusive max, multipleOf' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '219'
                ),
                new AnyOf(
                    '', new Field('', new IsNull()), new Field(
                        '',
                        new IsNumber(),
                        new Contained([1, 2.3, 4]),
                        new Maximum(99.99, true),
                        new Minimum(6.66),
                        new MultipleOf(3.33)

                    )
                ),
            ],
            'string' => [
                new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '220'),
                new Field('', new IsString()),
            ],
            'nullable string' => [
                new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '221'),
                new AnyOf('', new Field('', new IsNull()), new Field('', new IsString())),
            ],
            'string, enum' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '222'
                ),
                new Field('', new IsString(), new Contained(['a', 'b', 'c'])),
            ],
            'string, date format' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '223'
                ),
                new Field('', new IsString(), new DateString('Y-m-d')),
            ],
            'string, date-time format' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '224'
                ),
                new Field('', new IsString(), new DateString(DATE_ATOM)),
            ],
            'string, minLength' => [
                new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '225'),
                new Field('', new IsString(), new Length(5)),
            ],
            'string, maxLength' => [
                new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '226'),
                new Field('', new IsString(), new Length(0, 10)),
            ],
            'string, pattern' => [
                new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '227'),
                new Field('', new IsString(), new Regex('#[A-Za-z]+#')),
            ],
            'nullable string, enum, minLength, maxLength, pattern' => [
                new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '229'),
                new AnyOf(
                    '',
                    new Field('', new IsNull()),
                    new Field(
                        '',
                        new IsString(),
                        new Contained(['a', 'b', 'c']),
                        new Length(5, 10),
                        new Regex('#[A-Za-z]+#')
                    )
                ),
            ],
            'bool' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '230'
                ),
                new Field('', new IsBool()),
            ],
            'nullable bool' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '231'
                ),
                new AnyOf('', new Field('', new IsNull()), new Field('', new IsBool())),
            ],
            'bool, enum' => [
                new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '232'),
                new Field('', new IsBool(), new Contained([true])),
            ],
            'nullable bool, enum' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '239'
                ),
                new AnyOf(
                    '',
                    new Field('', new IsNull()),
                    new Field('', new IsBool(), new Contained([true, null]))
                ),
            ],
            'array of ints' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '240'
                ),
                new Collection('', new BeforeSet(new IsList()), new Field('', new IsInt())),
            ],
            'array of strings, enum' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '241'
                ),
                new Collection(
                    '',
                    new BeforeSet(new IsList(), new Contained([['a', 'b', 'c'], ['d', 'e', 'f']])),
                    new Field('', new IsString())
                ),
            ],
            'nullable array of strings' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '242'
                ),
                new AnyOf(
                    '',
                    new Field('', new IsNull()),
                    new Collection('', new BeforeSet(new IsList()), new Field('', new IsString()))
                ),
            ],
            'array of booleans, minItems' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '243'
                ),
                new Collection('', new BeforeSet(new IsList(), new Count(5)), new Field('', new IsBool())),
            ],
            'array of floats, maxItems' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '244'
                ),
                new Collection('', new BeforeSet(new IsList(), new Count(0, 5)), new Field('', new IsFloat())),
            ],
            'array of numbers, uniqueItems' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '245'
                ),
                new Collection('', new BeforeSet(new IsList(), new Unique()), new Field('', new IsNumber())),
            ],
            'nullable array of nullable numbers, enum, minItems, maxItems, uniqueItems' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '269'
                ),
                new AnyOf(
                    '',
                    new Field('', new IsNull()),
                    new Collection(
                        '',
                        new BeforeSet(
                            new IsList(),
                            new Contained([[1, 2.0, null], [4.0, null, 6]]),
                            new Count(2, 5),
                            new Unique()

                        ),
                        new AnyOf('', new Field('', new IsNull()), new Field('', new IsNumber()))
                    )
                ),
            ],
            'object with (string) name' => [
                new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '270'),
                new FieldSet(
                    '',
                    new BeforeSet(new IsArray()),
                    new Field('name', new IsString())
                ),
            ],
            'object with (int) id, enum' => [
                new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '271'),
                new FieldSet(
                    '',
                    new BeforeSet(new IsArray(), new Contained([['id' => 5], ['id' => 10]])),
                    new Field('id', new IsInt())
                ),
            ],
            'nullable object with (float) price' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '272'
                ),
                new AnyOf(
                    '',
                    new Field('', new IsNull()),
                    new FieldSet('', new BeforeSet(new IsArray()), new Field('price', new IsFloat()))

                ),
            ],
            'object with (string) name, (int) id, (bool) status' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '273'
                ),
                new FieldSet(
                    '',
                    new BeforeSet(new IsArray()),
                    new Field('name', new IsString()),
                    new Field('id', new IsInt()),
                    new Field('status', new IsBool())
                ),
            ],
            'object with (string) name, (int) id, (bool) status, required' => [
                new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '274'),
                new FieldSet(
                    '',
                    new BeforeSet(new IsArray(), new RequiredFields('name', 'id')),
                    new Field('name', new IsString()),
                    new Field('id', new IsInt()),
                    new Field('status', new IsBool())
                ),
            ],
            'nullable object with (string) name, (int) id, (bool) status, enum, required' => [
                new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '299'),
                new FieldSet(
                    '',
                    new BeforeSet(
                        new IsArray(),
                        new Contained(
                            [
                                ['name' => 'Ben', 'id' => 5, 'status' => true],
                                ['name' => 'Blink', 'id' => 10, 'status' => true],
                            ]
                        ),
                        new RequiredFields('name', 'id')
                    ),
                    new Field('name', new IsString()),
                    new Field('id', new IsInt()),
                    new Field('status', new IsBool())
                ),
            ],
            'allOf, one object (should act like normal object)' => [
                new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '300'),
                new FieldSet('', new Field('name', new IsString()), new BeforeSet(new IsArray())),
            ],
            'allOf, two objects, one identical parameter' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '301'
                ),
                new AllOf(
                    '',
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray()))
                ),
            ],
            'allOf, two objects, one unique parameters' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '302'
                ),
                new AllOf(
                    '',
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('', new Field('name', new IsString()), new BeforeSet(new IsArray()))
                ),
            ],
            'allOf, two objects, conflicting parameter' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '303'
                ),
                new AllOf(
                    '',
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('', new Field('id', new IsString()), new BeforeSet(new IsArray()))
                ),
            ],
            'allOf, two objects, unique parameters, one requiredField' => [
                new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '304'),
                new AllOf(
                    '',
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet(
                        '',
                        new BeforeSet(new IsArray(), new RequiredFields('name')),
                        new Field('name', new IsString())
                    )
                ),
            ],
            'allOf, two objects, unique parameters, two requiredField' => [
                new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '305'),
                new AllOf(
                    '',
                    new FieldSet(
                        '',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray(), new RequiredFields('id'))
                    ),
                    new FieldSet(
                        '',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    )
                ),
            ],
            'allOf, two objects, unique parameters, two requiredFields requiring the other schemas property' => [
                new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '306'),
                new AllOf(
                    '',
                    new FieldSet(
                        '',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    ),
                    new FieldSet(
                        '',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('id'))
                    )
                ),
            ],
            'anyOf, one object (should act like normal object)' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '320'
                ),
                new FieldSet('', new Field('name', new IsString()), new BeforeSet(new IsArray())),
            ],
            'anyOf, two objects, one identical parameter' => [
                new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '321'),
                new AnyOf(
                    '',
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray()))
                ),
            ],
            'anyOf, two objects, one unique parameters' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '322'
                ),
                new AnyOf(
                    '',
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('', new Field('name', new IsString()), new BeforeSet(new IsArray()))
                ),
            ],
            'anyOf, two objects, conflicting parameter' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '323'
                ),
                new AnyOf(
                    '',
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('', new Field('id', new IsString()), new BeforeSet(new IsArray()))
                ),
            ],
            'anyOf, two objects, unique parameters, one requiredField' => [
                new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '324'),
                new AnyOf(
                    '',
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet(
                        '',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    )
                ),
            ],
            'anyOf, two objects, unique parameters, two requiredField' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '325'
                ),
                new AnyOf(
                    '',
                    new FieldSet(
                        '',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray(), new RequiredFields('id'))
                    ),
                    new FieldSet(
                        '',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    )
                ),
            ],
            'anyOf, two objects, unique parameters, two requiredFields requiring the other schemas property' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '326'
                ),
                new AnyOf(
                    '',
                    new FieldSet(
                        '',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    ),
                    new FieldSet(
                        '',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('id'))
                    )
                ),
            ],
            'oneOf, one object (should act like normal object)' => [
                new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '340'),
                new FieldSet('', new Field('name', new IsString()), new BeforeSet(new IsArray())),
            ],
            'oneOf, two objects, one identical parameter' => [
                new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '341'),
                new OneOf(
                    '',
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray()))
                ),
            ],
            'oneOf, two objects, one unique parameters' => [
                new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '342'),
                new OneOf(
                    '',
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('', new Field('name', new IsString()), new BeforeSet(new IsArray()))
                ),
            ],
            'oneOf, two objects, conflicting parameter' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '343'
                ),
                new OneOf(
                    '',
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('', new Field('id', new IsString()), new BeforeSet(new IsArray()))
                ),
            ],
            'oneOf, two objects, unique parameters, one requiredField' => [
                new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '344'),
                new OneOf(
                    '',
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet(
                        '',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    )
                ),
            ],
            'oneOf, two objects, unique parameters, two requiredField' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '345'
                ),
                new OneOf(
                    '',
                    new FieldSet(
                        '',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray(), new RequiredFields('id'))
                    ),
                    new FieldSet(
                        '',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    )
                ),
            ],
            'oneOf, two objects, unique parameters, two requiredFields requiring the other schemas property' => [
                new Response(
                    self::DIR . 'noReferences.json',
                    '/responsepath',
                    Method::GET,
                    '346'
                ),
                new OneOf(
                    '',
                    new FieldSet(
                        '',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    ),
                    new FieldSet(
                        '',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('id'))
                    )
                ),
            ],
            'schema with no specified type' => [
                new Response(self::DIR . 'noReferences.json', '/responsepath', Method::GET, '404'),
                new Field('', new Passes()),
            ],
            'petstore.yaml: /pets path -> get operation -> 200 response' => [
                new Response(
                    self::DIR . 'docs/petstore.yaml',
                    '/pets',
                    Method::GET,
                    '200'
                ),
                new Collection(
                    '',
                    new BeforeSet(new IsList(), new Count(0, 100)),
                    new FieldSet(
                        '',
                        new BeforeSet(new IsArray(), new RequiredFields('id', 'name')),
                        new Field('id', new IsInt()),
                        new Field('name', new IsString()),
                        new Field('tag', new IsString())
                    ),
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsforBuilds
     */
    public function buildsTest(Specification $spec, Processor $expected): void
    {
        $sut = new ResponseBuilder();

        $processor = $sut->build($spec);

        self::assertEquals($expected, $processor);
    }

    public function dataSetsForDocExamples(): array
    {
        $petStore1 = new Response(
            self::DIR . 'docs/petstore.yaml',
            '/pets',
            Method::GET,
            '200'
        );

        return [
            'dataSet A' => [
                $petStore1,
                [
                    ['name' => 'Blink', 'id' => 1],
                    ['name' => 'Harley', 'id' => 2],
                ],
                Result::valid(
                    [
                        ['name' => 'Blink', 'id' => 1],
                        ['name' => 'Harley', 'id' => 2],
                    ]
                ),
            ],
            'dataSet B' => [
                $petStore1,
                [
                    ['name' => 'Blink'],
                    ['id' => 2],
                ],
                Result::invalid(
                    [
                        ['name' => 'Blink'],
                        ['id' => 2],
                    ],
                    new MessageSet(new FieldName('', '', '', '0', ''), new Message('%s is a required field', ['id'])),
                    new MessageSet(new FieldName('', '', '', '1', ''), new Message('%s is a required field', ['name'])),
                ),
            ],
            'dataSet C' => [
                $petStore1,
                [
                    'Blink',
                    5,
                ],
                Result::invalid(
                    [
                        'Blink',
                        5,
                    ],
                    new MessageSet(
                        new FieldName('', '', '', '0', ''),
                        new Message('IsArray validator expects array value, %s passed instead', ['string'])
                    ),
                    new MessageSet(
                        new FieldName('', '', '', '1', ''),
                        new Message('IsArray validator expects array value, %s passed instead', ['integer'])
                    ),
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsForDocExamples
     */
    public function docsTest(Specification $spec, array $data, Result $expected): void
    {
        $sut = new ResponseBuilder();

        $processor = $sut->build($spec);

        self::assertEquals($expected, $processor->process(new FieldName(''), $data));
    }
}
