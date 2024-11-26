<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Builder;

use Generator;
use Membrane\Builder\Specification;
use Membrane\Filter\String\ToUpperCase;
use Membrane\OpenAPI\Builder\APIBuilder;
use Membrane\OpenAPI\Builder\OpenAPIResponseBuilder;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\ExtractPathParameters\PathMatcher;
use Membrane\OpenAPI\Processor\AllOf;
use Membrane\OpenAPI\Processor\AnyOf;
use Membrane\OpenAPI\Processor\OneOf;
use Membrane\OpenAPI\Specification\APISchema;
use Membrane\OpenAPI\Specification\Arrays;
use Membrane\OpenAPI\Specification\Numeric;
use Membrane\OpenAPI\Specification\Objects;
use Membrane\OpenAPI\Specification\OpenAPIResponse;
use Membrane\OpenAPI\Specification\Response;
use Membrane\OpenAPI\Specification\Strings;
use Membrane\OpenAPI\Specification\TrueFalse;
use Membrane\OpenAPIReader\MembraneReader;
use Membrane\OpenAPIReader\OpenAPIVersion;
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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpenAPIResponseBuilder::class)]
#[CoversClass(CannotProcessSpecification::class)]
#[CoversClass(CannotProcessOpenAPI::class)]
#[CoversClass(APIBuilder::class)]
#[UsesClass(OpenAPIResponse::class)]
#[UsesClass(\Membrane\OpenAPI\Builder\Arrays::class)]
#[UsesClass(\Membrane\OpenAPI\Builder\TrueFalse::class)]
#[UsesClass(\Membrane\OpenAPI\Builder\Numeric::class)]
#[UsesClass(\Membrane\OpenAPI\Builder\Objects::class)]
#[UsesClass(\Membrane\OpenAPI\Builder\Strings::class)]
#[UsesClass(PathMatcher::class)]
#[UsesClass(AllOf::class)]
#[UsesClass(AnyOf::class)]
#[UsesClass(OneOf::class)]
#[UsesClass(APISchema::class)]
#[UsesClass(Arrays::class)]
#[UsesClass(TrueFalse::class)]
#[UsesClass(Numeric::class)]
#[UsesClass(Objects::class)]
#[UsesClass(Strings::class)]
#[UsesClass(Response::class)]
#[UsesClass(BeforeSet::class)]
#[UsesClass(Collection::class)]
#[UsesClass(Field::class)]
#[UsesClass(FieldSet::class)]
#[UsesClass(FieldName::class)]
#[UsesClass(Message::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Result::class)]
#[UsesClass(Contained::class)]
#[UsesClass(Count::class)]
#[UsesClass(Unique::class)]
#[UsesClass(ToUpperCase::class)]
#[UsesClass(RequiredFields::class)]
#[UsesClass(Maximum::class)]
#[UsesClass(Minimum::class)]
#[UsesClass(MultipleOf::class)]
#[UsesClass(DateString::class)]
#[UsesClass(Length::class)]
#[UsesClass(Regex::class)]
#[UsesClass(IsArray::class)]
#[UsesClass(IsInt::class)]
#[UsesClass(IsList::class)]
#[UsesClass(IsString::class)]
#[UsesClass(\Membrane\Validator\Utility\AnyOf::class)]
class OpenAPIResponseBuilderTest extends TestCase
{
    public const DIR = __DIR__ . '/../../fixtures/OpenAPI/';

    #[Test, TestDox('It throws an exception if you try to use the keyword "not"')]
    public function throwsExceptionIfNotIsFound(): void
    {
        $openApi = (new MembraneReader([
            OpenAPIVersion::Version_3_0,
        ]))->readFromAbsoluteFilePath(self::DIR . 'noReferences.json');

        $operation = $openApi->paths['/responsepath']->get;

        $sut = new OpenAPIResponseBuilder();
        $response = new OpenAPIResponse(
            OpenAPIVersion::Version_3_0,
            $operation->operationId,
            '360',
            $operation->responses['360'],
        );

        self::expectExceptionObject(CannotProcessOpenAPI::unsupportedKeyword('not'));

        $sut->build($response);
    }

    #[Test, TestDox('It supports the Response Specification')]
    public function supportsResponseSpecification(): void
    {
        $specification = self::createStub(OpenAPIResponse::class);
        $sut = new OpenAPIResponseBuilder();

        self::assertTrue($sut->supports($specification));
    }

    #[Test, TestDox('It does not support any Specifications that are not Response')]
    public function doesNotSupportSpecificationsThatAreNotResponse(): void
    {
        $specification = self::createStub(\Membrane\Builder\Specification::class);
        $sut = new OpenAPIResponseBuilder();

        self::assertFalse($sut->supports($specification));
    }

    public static function dataSetsforBuilds(): array
    {
        $reader = new MembraneReader([
            OpenAPIVersion::Version_3_0,
        ]);

        $noReferences = $reader->readFromAbsoluteFilePath(self::DIR . 'noReferences.json');
        $petstore = $reader->readFromAbsoluteFilePath(self::DIR . 'docs/petstore.yaml');

        return [
            'no properties' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/path']->get->operationId,
                    '200',
                    $noReferences->paths['/path']->get->responses['200']
                ),
                new Field('', new Passes()),
            ],
            'int' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '200',
                    $noReferences->paths['/responsepath']->get->responses['200']
                ),
                new Field('', new IsInt()),
            ],
            'nullable int' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '201',
                    $noReferences->paths['/responsepath']->get->responses['201']
                ),
                new AnyOf('', new Field('', new IsNull()), new Field('', new IsInt())),
            ],
            'int, inclusive min' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '202',
                    $noReferences->paths['/responsepath']->get->responses['202']
                ),
                new Field('', new IsInt(), new Minimum(0)),
            ],
            'int, exclusive min' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '203',
                    $noReferences->paths['/responsepath']->get->responses['203']
                ),
                new Field('', new IsInt(), new Minimum(0, true)),
            ],
            'int, inclusive max' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '204',
                    $noReferences->paths['/responsepath']->get->responses['204']
                ),
                new Field('', new IsInt(), new Maximum(100)),
            ],
            'int, exclusive max' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '205',
                    $noReferences->paths['/responsepath']->get->responses['205']
                ),
                new Field('', new IsInt(), new Maximum(100, true)),
            ],
            'int, multipleOf' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '206',
                    $noReferences->paths['/responsepath']->get->responses['206']
                ),
                new Field('', new IsInt(), new MultipleOf(3)),
            ],
            'int, enum' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '207',
                    $noReferences->paths['/responsepath']->get->responses['207']
                ),
                new Field('', new IsInt(), new Contained([1, 2, 3])),
            ],
            'nullable int, enum, exclusive min, inclusive max, multipleOf' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '209',
                    $noReferences->paths['/responsepath']->get->responses['209']
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
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '210',
                    $noReferences->paths['/responsepath']->get->responses['210']
                ),
                new Field('', new IsNumber()),
            ],
            'nullable number' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '211',
                    $noReferences->paths['/responsepath']->get->responses['211']
                ),
                new AnyOf('', new Field('', new IsNull()), new Field('', new IsNumber())),
            ],
            'number, enum' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '212',
                    $noReferences->paths['/responsepath']->get->responses['212']
                ),
                new Field('', new IsNumber(), new Contained([1, 2.3, 4])),
            ],
            'number, float format' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '213',
                    $noReferences->paths['/responsepath']->get->responses['213']
                ),
                new Field('', new IsFloat()),
            ],
            'nullable number, float format' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '214',
                    $noReferences->paths['/responsepath']->get->responses['214']
                ),
                new AnyOf('', new Field('', new IsNull()), new Field('', new IsFloat())),
            ],
            'number, double format' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '215',
                    $noReferences->paths['/responsepath']->get->responses['215']
                ),
                new Field('', new IsFloat()),
            ],
            'nullable number, enum, inclusive min, exclusive max, multipleOf' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '219',
                    $noReferences->paths['/responsepath']->get->responses['219']
                ),
                new AnyOf(
                    '',
                    new Field('', new IsNull()),
                    new Field(
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
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '220',
                    $noReferences->paths['/responsepath']->get->responses['220']
                ),
                new Field('', new IsString()),
            ],
            'nullable string' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '221',
                    $noReferences->paths['/responsepath']->get->responses['221']
                ),
                new AnyOf('', new Field('', new IsNull()), new Field('', new IsString())),
            ],
            'string, enum' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '222',
                    $noReferences->paths['/responsepath']->get->responses['222']
                ),
                new Field('', new IsString(), new Contained(['a', 'b', 'c'])),
            ],
            'string, date format' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '223',
                    $noReferences->paths['/responsepath']->get->responses['223']
                ),
                new Field('', new IsString(), new DateString('Y-m-d', true)),
            ],
            'string, date-time format' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '224',
                    $noReferences->paths['/responsepath']->get->responses['224']
                ),
                new Field(
                    '',
                    new IsString(),
                    new ToUpperCase(),
                    new \Membrane\Validator\Utility\AnyOf(
                        new DateString('Y-m-d\TH:i:sP', true),
                        new DateString('Y-m-d\TH:i:sp', true),
                    ),
                ),
            ],
            'string, minLength' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '225',
                    $noReferences->paths['/responsepath']->get->responses['225']
                ),
                new Field('', new IsString(), new Length(5)),
            ],
            'string, maxLength' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '226',
                    $noReferences->paths['/responsepath']->get->responses['226']
                ),
                new Field('', new IsString(), new Length(0, 10)),
            ],
            'string, pattern' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '227',
                    $noReferences->paths['/responsepath']->get->responses['227']
                ),
                new Field('', new IsString(), new Regex('#[A-Za-z]+#u')),
            ],
            'nullable string, enum, minLength, maxLength, pattern' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '229',
                    $noReferences->paths['/responsepath']->get->responses['229']
                ),
                new AnyOf(
                    '',
                    new Field('', new IsNull()),
                    new Field(
                        '',
                        new IsString(),
                        new Contained(['a', 'b', 'c']),
                        new Length(5, 10),
                        new Regex('#[A-Za-z]+#u')
                    )
                ),
            ],
            'bool' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '230',
                    $noReferences->paths['/responsepath']->get->responses['230']
                ),
                new Field('', new IsBool()),
            ],
            'nullable bool' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '231',
                    $noReferences->paths['/responsepath']->get->responses['231']
                ),
                new AnyOf('', new Field('', new IsNull()), new Field('', new IsBool())),
            ],
            'bool, enum' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '232',
                    $noReferences->paths['/responsepath']->get->responses['232']
                ),
                new Field('', new IsBool(), new Contained([true])),
            ],
            'nullable bool, enum' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '239',
                    $noReferences->paths['/responsepath']->get->responses['239']
                ),
                new AnyOf(
                    '',
                    new Field('', new IsNull()),
                    new Field('', new IsBool(), new Contained([true, null]))
                ),
            ],
            'array of ints' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '240',
                    $noReferences->paths['/responsepath']->get->responses['240']
                ),
                new Collection('', new BeforeSet(new IsList()), new Field('', new IsInt())),
            ],
            'array of strings, enum' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '241',
                    $noReferences->paths['/responsepath']->get->responses['241']
                ),
                new Collection(
                    '',
                    new BeforeSet(new IsList(), new Contained([['a', 'b', 'c'], ['d', 'e', 'f']])),
                    new Field('', new IsString())
                ),
            ],
            'nullable array of strings' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '242',
                    $noReferences->paths['/responsepath']->get->responses['242']
                ),
                new AnyOf(
                    '',
                    new Field('', new IsNull()),
                    new Collection('', new BeforeSet(new IsList()), new Field('', new IsString()))
                ),
            ],
            'array of booleans, minItems' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '243',
                    $noReferences->paths['/responsepath']->get->responses['243']
                ),
                new Collection('', new BeforeSet(new IsList(), new Count(5)), new Field('', new IsBool())),
            ],
            'array of floats, maxItems' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '244',
                    $noReferences->paths['/responsepath']->get->responses['244']
                ),
                new Collection('', new BeforeSet(new IsList(), new Count(0, 5)), new Field('', new IsFloat())),
            ],
            'array of numbers, uniqueItems' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '245',
                    $noReferences->paths['/responsepath']->get->responses['245']
                ),
                new Collection('', new BeforeSet(new IsList(), new Unique()), new Field('', new IsNumber())),
            ],
            'nullable array of nullable numbers, enum, minItems, maxItems, uniqueItems' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '269',
                    $noReferences->paths['/responsepath']->get->responses['269']
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
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '270',
                    $noReferences->paths['/responsepath']->get->responses['270']
                ),
                new FieldSet(
                    '',
                    new BeforeSet(new IsArray()),
                    new Field('name', new IsString())
                ),
            ],
            'object with (int) id, enum' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '271',
                    $noReferences->paths['/responsepath']->get->responses['271']
                ),
                new FieldSet(
                    '',
                    new BeforeSet(new IsArray(), new Contained([['id' => 5], ['id' => 10]])),
                    new Field('id', new IsInt())
                ),
            ],
            'nullable object with (float) price' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '272',
                    $noReferences->paths['/responsepath']->get->responses['272']
                ),
                new AnyOf(
                    '',
                    new Field('', new IsNull()),
                    new FieldSet('', new BeforeSet(new IsArray()), new Field('price', new IsFloat()))
                ),
            ],
            'object with (string) name, (int) id, (bool) status' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '273',
                    $noReferences->paths['/responsepath']->get->responses['273']
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
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '274',
                    $noReferences->paths['/responsepath']->get->responses['274']
                ),
                new FieldSet(
                    '',
                    new BeforeSet(new IsArray(), new RequiredFields('name', 'id')),
                    new Field('name', new IsString()),
                    new Field('id', new IsInt()),
                    new Field('status', new IsBool())
                ),
            ],
            'nullable object with (string) name, (int) id, (bool) status, enum, required' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '299',
                    $noReferences->paths['/responsepath']->get->responses['299']
                ),
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
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '300',
                    $noReferences->paths['/responsepath']->get->responses['300']
                ),
                new FieldSet('', new Field('name', new IsString()), new BeforeSet(new IsArray())),
            ],
            'allOf, two objects, one identical parameter' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '301',
                    $noReferences->paths['/responsepath']->get->responses['301']
                ),
                new AllOf(
                    '',
                    new FieldSet('Branch-1', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('Branch-2', new Field('id', new IsInt()), new BeforeSet(new IsArray()))
                ),
            ],
            'allOf, two objects, one unique parameters' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '302',
                    $noReferences->paths['/responsepath']->get->responses['302']
                ),
                new AllOf(
                    '',
                    new FieldSet('Branch-1', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('Branch-2', new Field('name', new IsString()), new BeforeSet(new IsArray()))
                ),
            ],
            'allOf, two objects, conflicting parameter' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '303',
                    $noReferences->paths['/responsepath']->get->responses['303']
                ),
                new AllOf(
                    '',
                    new FieldSet('Branch-1', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('Branch-2', new Field('id', new IsString()), new BeforeSet(new IsArray()))
                ),
            ],
            'allOf, two objects, unique parameters, one requiredField' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '304',
                    $noReferences->paths['/responsepath']->get->responses['304']
                ),
                new AllOf(
                    '',
                    new FieldSet('Branch-1', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet(
                        'Branch-2',
                        new BeforeSet(new IsArray(), new RequiredFields('name')),
                        new Field('name', new IsString())
                    )
                ),
            ],
            'allOf, two objects, unique parameters, two requiredField' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '305',
                    $noReferences->paths['/responsepath']->get->responses['305']
                ),
                new AllOf(
                    '',
                    new FieldSet(
                        'Branch-1',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray(), new RequiredFields('id'))
                    ),
                    new FieldSet(
                        'Branch-2',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    )
                ),
            ],
            'allOf, two objects, unique parameters, two requiredFields requiring the other schemas property' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '306',
                    $noReferences->paths['/responsepath']->get->responses['306']
                ),
                new AllOf(
                    '',
                    new FieldSet(
                        'Branch-1',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    ),
                    new FieldSet(
                        'Branch-2',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('id'))
                    )
                ),
            ],
            'anyOf, one object (should act like normal object)' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '320',
                    $noReferences->paths['/responsepath']->get->responses['320']
                ),
                new FieldSet('', new Field('name', new IsString()), new BeforeSet(new IsArray())),
            ],
            'anyOf, two objects, one identical parameter' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '321',
                    $noReferences->paths['/responsepath']->get->responses['321']
                ),
                new AnyOf(
                    '',
                    new FieldSet('Branch-1', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('Branch-2', new Field('id', new IsInt()), new BeforeSet(new IsArray()))
                ),
            ],
            'anyOf, two objects, one unique parameters' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '322',
                    $noReferences->paths['/responsepath']->get->responses['322']
                ),
                new AnyOf(
                    '',
                    new FieldSet('Branch-1', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('Branch-2', new Field('name', new IsString()), new BeforeSet(new IsArray()))
                ),
            ],
            'anyOf, two objects, conflicting parameter' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '323',
                    $noReferences->paths['/responsepath']->get->responses['323']
                ),
                new AnyOf(
                    '',
                    new FieldSet('Branch-1', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('Branch-2', new Field('id', new IsString()), new BeforeSet(new IsArray()))
                ),
            ],
            'anyOf, two objects, unique parameters, one requiredField' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '324',
                    $noReferences->paths['/responsepath']->get->responses['324']
                ),
                new AnyOf(
                    '',
                    new FieldSet('Branch-1', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet(
                        'Branch-2',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    )
                ),
            ],
            'anyOf, two objects, unique parameters, two requiredField' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '325',
                    $noReferences->paths['/responsepath']->get->responses['325']
                ),
                new AnyOf(
                    '',
                    new FieldSet(
                        'Branch-1',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray(), new RequiredFields('id'))
                    ),
                    new FieldSet(
                        'Branch-2',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    )
                ),
            ],
            'anyOf, two objects, unique parameters, two requiredFields requiring the other schemas property' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '326',
                    $noReferences->paths['/responsepath']->get->responses['326']
                ),
                new AnyOf(
                    '',
                    new FieldSet(
                        'Branch-1',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    ),
                    new FieldSet(
                        'Branch-2',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('id'))
                    )
                ),
            ],
            'oneOf, one object (should act like normal object)' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '340',
                    $noReferences->paths['/responsepath']->get->responses['340']
                ),
                new FieldSet('', new Field('name', new IsString()), new BeforeSet(new IsArray())),
            ],
            'oneOf, two objects, one identical parameter' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '341',
                    $noReferences->paths['/responsepath']->get->responses['341']
                ),
                new OneOf(
                    '',
                    new FieldSet('Branch-1', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('Branch-2', new Field('id', new IsInt()), new BeforeSet(new IsArray()))
                ),
            ],
            'oneOf, two objects, one unique parameters' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '342',
                    $noReferences->paths['/responsepath']->get->responses['342']
                ),
                new OneOf(
                    '',
                    new FieldSet('Branch-1', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('Branch-2', new Field('name', new IsString()), new BeforeSet(new IsArray()))
                ),
            ],
            'oneOf, two objects, conflicting parameter' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '343',
                    $noReferences->paths['/responsepath']->get->responses['343']
                ),
                new OneOf(
                    '',
                    new FieldSet('Branch-1', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('Branch-2', new Field('id', new IsString()), new BeforeSet(new IsArray()))
                ),
            ],
            'oneOf, two objects, unique parameters, one requiredField' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '344',
                    $noReferences->paths['/responsepath']->get->responses['344']
                ),
                new OneOf(
                    '',
                    new FieldSet('Branch-1', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet(
                        'Branch-2',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    )
                ),
            ],
            'oneOf, two objects, unique parameters, two requiredField' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '345',
                    $noReferences->paths['/responsepath']->get->responses['345']
                ),
                new OneOf(
                    '',
                    new FieldSet(
                        'Branch-1',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray(), new RequiredFields('id'))
                    ),
                    new FieldSet(
                        'Branch-2',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    )
                ),
            ],
            'oneOf, two objects, unique parameters, two requiredFields requiring the other schemas property' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '346',
                    $noReferences->paths['/responsepath']->get->responses['346']
                ),
                new OneOf(
                    '',
                    new FieldSet(
                        'Branch-1',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    ),
                    new FieldSet(
                        'Branch-2',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('id'))
                    )
                ),
            ],
            'schema with no specified type' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '404',
                    $noReferences->paths['/responsepath']->get->responses['404']
                ),
                new Field('', new Passes()),
            ],
            'schema with empty content' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '405',
                    $noReferences->paths['/responsepath']->get->responses['405']
                ),
                new Field('', new Passes()),
            ],
            'schema with no content' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $noReferences->paths['/responsepath']->get->operationId,
                    '406',
                    $noReferences->paths['/responsepath']->get->responses['406']
                ),
                new Field('', new Passes()),
            ],
            'petstore.yaml: /pets path -> get operation -> 200 response' => [
                new OpenAPIResponse(
                    OpenAPIVersion::Version_3_0,
                    $petstore->paths['/pets']->get->operationId,
                    '200',
                    $petstore->paths['/pets']->get->responses['200']
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

    #[Test, TestDox('It builds processors that can validate data matches response content')]
    #[DataProvider('dataSetsforBuilds')]
    public function buildsTest(Specification $spec, Processor $expected): void
    {
        $sut = new OpenAPIResponseBuilder();

        $processor = $sut->build($spec);

        self::assertEquals($expected, $processor);
    }

    public static function dataSetsForDocExamples(): array
    {
        $petstore = (new MembraneReader([OpenAPIVersion::Version_3_0]))
            ->readFromAbsoluteFilePath(self::DIR . 'docs/petstore.yaml');

        $petsGet200Response = new OpenAPIResponse(
            OpenAPIVersion::Version_3_0,
            $petstore->paths['/pets']->get->operationId,
            '200',
            $petstore->paths['/pets']->get->responses['200']
        );

        return [
            'dataSet A' => [
                $petsGet200Response,
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
                $petsGet200Response,
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
                $petsGet200Response,
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

    #[DataProvider('dataSetsForDocExamples')]
    #[Test]
    public function docsTest(Specification $spec, array $data, Result $expected): void
    {
        $sut = new OpenAPIResponseBuilder();

        $processor = $sut->build($spec);

        self::assertEquals($expected, $processor->process(new FieldName(''), $data));
    }

    #[Test]
    #[DataProvider('provideDateStrings')]
    public function itValidatesDateTime(Result $expected, string $dateTime): void
    {
        $noReferences = (new MembraneReader([
            OpenAPIVersion::Version_3_0,
        ]))->readFromAbsoluteFilePath(self::DIR . 'noReferences.json');

        $specification = new OpenAPIResponse(
            OpenAPIVersion::Version_3_0,
            $noReferences->paths['/responsepath']->get->operationId,
            '224',
            $noReferences->paths['/responsepath']->get->responses['224']
        );

        $sut = new OpenAPIResponseBuilder();

        $processor = $sut->build($specification);

        self::assertEquals($expected, $processor->process(new FieldName(''), $dateTime));
    }

    public static function provideDateStrings(): Generator
    {
        yield 'valid, upper case T and Z' => [
            Result::valid('1970-01-01T00:00:00Z'),
            '1970-01-01T00:00:00Z',
        ];

        yield 'valid, upper case T, lower case z' => [
            Result::valid('1970-01-01T00:00:00Z'),
            '1970-01-01T00:00:00z',
        ];

        yield 'valid, lower case t, upper case z' => [
            Result::valid('1970-01-01T00:00:00Z'),
            '1970-01-01t00:00:00Z',
        ];

        yield 'valid, lower case t and z' => [
            Result::valid('1970-01-01T00:00:00Z'),
            '1970-01-01t00:00:00z',
        ];

        yield 'valid, lower case t and +00:00' => [
            Result::valid('1970-01-01T00:00:00+00:00'),
            '1970-01-01T00:00:00+00:00',
        ];
    }
}
