<?php

declare(strict_types=1);

namespace Membrane\Tests\Console\Command;

use Membrane;
use Membrane\Console\Command\CacheOpenAPIProcessors;
use Membrane\Console\Template;
use Membrane\Filter\String\AlphaNumeric;
use Membrane\Filter\String\Explode;
use Membrane\Filter\String\ToPascalCase;
use Membrane\Filter\Type as TypeFilter;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPI\Builder as Builder;
use Membrane\OpenAPI\Builder\OpenAPIRequestBuilder;
use Membrane\OpenAPI\ContentType;
use Membrane\OpenAPI\ExtractPathParameters\PathParameterExtractor;
use Membrane\OpenAPI\Filter\PathMatcher;
use Membrane\OpenAPI\Processor\Request;
use Membrane\OpenAPI\Specification as Specification;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
use Membrane\OpenAPIReader\Reader;
use Membrane\Processor;
use Membrane\Validator\{FieldSet as FieldSetValidator,
    String\IntString,
    Type as TypeValidator,
    Utility as UtilityValidator};
use org\bovigo\vfs\{vfsStream, vfsStreamDirectory};
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider, Test, TestDox, UsesClass};
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(CacheOpenAPIProcessors::class)]
#[UsesClass(Membrane\Console\Service\CacheOpenAPIProcessors::class)]
#[UsesClass(Template\Processor::class)]
#[UsesClass(Template\ResponseBuilder::class)]
#[UsesClass(Template\RequestBuilder::class)]
#[UsesClass(Builder\APIBuilder::class)]
#[UsesClass(Builder\ParameterBuilder::class)]
#[UsesClass(Builder\Arrays::class)]
#[UsesClass(Builder\Numeric::class)]
#[UsesClass(Builder\Strings::class)]
#[UsesClass(Builder\Objects::class)]
#[UsesClass(Builder\OpenAPIRequestBuilder::class)]
#[UsesClass(Builder\OpenAPIResponseBuilder::class)]
#[UsesClass(PathMatcher::class)]
#[UsesClass(PathParameterExtractor::class)]
#[UsesClass(Membrane\OpenAPI\Processor\AllOf::class)]
#[UsesClass(Membrane\OpenAPI\Filter\QueryStringToArray::class)]
#[UsesClass(Membrane\OpenAPI\Filter\FormatStyle\Form::class)]
#[UsesClass(Request::class)]
#[UsesClass(Specification\Objects::class)]
#[UsesClass(Specification\OpenAPIRequest::class)]
#[UsesClass(Specification\OpenAPIResponse::class)]
#[UsesClass(Membrane\Result\Result::class)]
#[UsesClass(AlphaNumeric::class)]
#[UsesClass(ToPascalCase::class)]
#[UsesClass(Explode::class)]
#[UsesClass(IntString::class)]
#[UsesClass(TypeFilter\ToInt::class)]
#[UsesClass(Specification\APISchema::class)]
#[UsesClass(Specification\Parameter::class)]
#[UsesClass(Specification\Arrays::class)]
#[UsesClass(Specification\Numeric::class)]
#[UsesClass(Specification\Strings::class)]
#[UsesClass(Processor\BeforeSet::class)]
#[UsesClass(Processor\Collection::class)]
#[UsesClass(Processor\Field::class)]
#[UsesClass(Processor\FieldSet::class)]
#[UsesClass(FieldSetValidator\RequiredFields::class)]
#[UsesClass(TypeValidator\IsArray::class)]
#[UsesClass(TypeValidator\IsInt::class)]
#[UsesClass(TypeValidator\IsList::class)]
#[UsesClass(TypeValidator\IsString::class)]
#[UsesClass(UtilityValidator\Passes::class)]
#[UsesClass(ContentType::class)]
class CacheOpenAPIProcessorsTest extends TestCase
{
    private vfsStreamDirectory $root;
    private CommandTester $sut;

    public function setUp(): void
    {
        $this->root = vfsStream::setup();
        $this->sut = new CommandTester(new CacheOpenAPIProcessors());
    }

    #[Test, TestDox('It will fail if it lacks write permission to the destination filepath')]
    public function failsOnReadonlyDestinations(): void
    {
        $correctApiPath = __DIR__ . '/../../fixtures/OpenAPI/docs/petstore-expanded.json';
        chmod($this->root->url(), 0444);
        $readonlyDestination = $this->root->url() . '/cache';

        $this->sut->execute(['openAPI' => $correctApiPath, 'destination' => $readonlyDestination]);

        self::assertSame(Command::FAILURE, $this->sut->getStatusCode());
    }

    public static function provideCasesThatFailToRead(): array
    {
        return [
            'cannot read from relative filename' => [
                '/../../fixtures/docs/petstore-expanded.json',
                vfsStream::url('root') . 'cache/routes.php',
                Command::FAILURE,
            ],
            'cannot route from an api with no routes' => [
                __DIR__ . '/../../fixtures/simple.json',
                vfsStream::url('root') . 'cache/routes.php',
                Command::FAILURE,
            ],
        ];
    }

    #[Test, TestDox('It will fail if it cannot read an OpenAPI from the given filepath')]
    #[DataProvider('provideCasesThatFailToRead')]
    public function failsOnUnreadableOpenAPI(string $openAPI, string $destination, int $expectedStatusCode): void
    {
        $this->sut->execute(['openAPI' => $openAPI, 'destination' => $destination]);

        self::assertSame($expectedStatusCode, $this->sut->getStatusCode());
    }


    public static function provideCasesToCache(): array
    {
        $requestBuilder = new OpenAPIRequestBuilder();
        $responseBuilder = new Builder\OpenAPIResponseBuilder();
        $petstoreExpandedFilePath = __DIR__ . '/../../fixtures/OpenAPI/docs/petstore-expanded.json';
        $petstoreExpandedOpenApi = (new Reader([Membrane\OpenAPIReader\OpenAPIVersion::Version_3_0]))
            ->readFromAbsoluteFilePath($petstoreExpandedFilePath);

        return [
            'findPets : Request' => [
                $petstoreExpandedFilePath,
                'cache/Request/FindPets.php',
                'CommandTest\\PetstoreA',
                'Request\\FindPets',
                $requestBuilder->build(
                    new Specification\OpenAPIRequest(
                        OpenAPIVersion::FromString($petstoreExpandedOpenApi->openapi),
                        new PathParameterExtractor('/pets'),
                        $petstoreExpandedOpenApi->paths->getPath('/pets'),
                        Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method::GET
                    )
                ),
            ],
            'findPets : 200 Response' => [
                $petstoreExpandedFilePath,
                'cache/Response/Code200/FindPets.php',
                'CommandTest\\PetstoreB',
                'Response\\Code200\\FindPets',
                $responseBuilder->build(
                    new Specification\OpenAPIResponse(
                        OpenAPIVersion::FromString($petstoreExpandedOpenApi->openapi),
                        'findPets',
                        '200',
                        $petstoreExpandedOpenApi->paths->getPath('/pets')->get->responses->getResponse('200')
                    )
                ),
            ],
            'findPets : default Response' => [
                $petstoreExpandedFilePath,
                'cache/Response/CodeDefault/FindPets.php',
                'CommandTest\\PetstoreC',
                'Response\\CodeDefault\\FindPets',
                $responseBuilder->build(
                    new Specification\OpenAPIResponse(
                        OpenAPIVersion::FromString($petstoreExpandedOpenApi->openapi),
                        'findPets',
                        'default',
                        $petstoreExpandedOpenApi->paths->getPath('/pets')->get->responses->getResponse('default')
                    )
                ),
            ],
            'addPet : Request' => [
                $petstoreExpandedFilePath,
                'cache/Request/AddPet.php',
                'CommandTest\\PetstoreD',
                'Request\\AddPet',
                $requestBuilder->build(
                    new Specification\OpenAPIRequest(
                        OpenAPIVersion::FromString($petstoreExpandedOpenApi->openapi),
                        new PathParameterExtractor('/pets'),
                        $petstoreExpandedOpenApi->paths->getPath('/pets'),
                        Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method::POST
                    )
                ),
            ],
            'addPet : 200 Response' => [
                $petstoreExpandedFilePath,
                'cache/Response/Code200/AddPet.php',
                'CommandTest\\PetstoreE',
                'Response\\Code200\\AddPet',
                $responseBuilder->build(
                    new Specification\OpenAPIResponse(
                        OpenAPIVersion::FromString($petstoreExpandedOpenApi->openapi),
                        'addPet',
                        '200',
                        $petstoreExpandedOpenApi->paths->getPath('/pets')->post->responses->getResponse('200')
                    )
                ),
            ],
            'addPet : default Response' => [
                $petstoreExpandedFilePath,
                'cache/Response/CodeDefault/AddPet.php',
                'CommandTest\\PetstoreF',
                'Response\\CodeDefault\\AddPet',
                $responseBuilder->build(
                    new Specification\OpenAPIResponse(
                        OpenAPIVersion::FromString($petstoreExpandedOpenApi->openapi),
                        'addPet',
                        'default',
                        $petstoreExpandedOpenApi->paths->getPath('/pets')->post->responses->getResponse('default')
                    )
                ),
            ],
            'find pet by id : Request' => [
                $petstoreExpandedFilePath,
                'cache/Request/FindPetById.php',
                'CommandTest\\PetstoreG',
                'Request\\FindPetById',
                $requestBuilder->build(
                    new Specification\OpenAPIRequest(
                        OpenAPIVersion::FromString($petstoreExpandedOpenApi->openapi),
                        new PathParameterExtractor('/pets/{id}'),
                        $petstoreExpandedOpenApi->paths->getPath('/pets/{id}'),
                        Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method::GET
                    )
                ),
            ],
            'find pet by id : 200 Response' => [
                $petstoreExpandedFilePath,
                'cache/Response/Code200/FindPetById.php',
                'CommandTest\\PetstoreH',
                'Response\\Code200\\FindPetById',
                $responseBuilder->build(
                    new Specification\OpenAPIResponse(
                        OpenAPIVersion::FromString($petstoreExpandedOpenApi->openapi),
                        'find pet by id',
                        '200',
                        $petstoreExpandedOpenApi->paths->getPath('/pets/{id}')->get->responses->getResponse('200')
                    )
                ),
            ],
            'find pet by id : default Response' => [
                $petstoreExpandedFilePath,
                'cache/Response/CodeDefault/FindPetById.php',
                'CommandTest\\PetstoreI',
                'Response\\CodeDefault\\FindPetById',
                $responseBuilder->build(
                    new Specification\OpenAPIResponse(
                        OpenAPIVersion::FromString($petstoreExpandedOpenApi->openapi),
                        'find pet by id',
                        'default',
                        $petstoreExpandedOpenApi->paths->getPath('/pets/{id}')->get->responses->getResponse('default')
                    )
                ),
            ],
            'deletePet : Request' => [
                $petstoreExpandedFilePath,
                'cache/Request/DeletePet.php',
                'CommandTest\\PetstoreJ',
                'Request\\DeletePet',
                $requestBuilder->build(
                    new Specification\OpenAPIRequest(
                        OpenAPIVersion::FromString($petstoreExpandedOpenApi->openapi),
                        new PathParameterExtractor('/pets/{id}'),
                        $petstoreExpandedOpenApi->paths->getPath('/pets/{id}'),
                        Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method::DELETE
                    )
                ),
            ],
            'deletePet : 204 Response' => [
                $petstoreExpandedFilePath,
                'cache/Response/Code204/DeletePet.php',
                'CommandTest\\PetstoreK',
                'Response\\Code204\\DeletePet',
                $responseBuilder->build(
                    new Specification\OpenAPIResponse(
                        OpenAPIVersion::FromString($petstoreExpandedOpenApi->openapi),

                        'deletePet',
                        '204',
                        $petstoreExpandedOpenApi->paths->getPath('/pets/{id}')->delete->responses->getResponse('204')
                    )
                ),
            ],
        ];
    }

    #[Test, TestDox('It caches Requests and Responses for the OpenAPI example: petstore-expanded.json')]
    #[DataProvider('provideCasesToCache')]
    public function cachesProcessorsFromPetStoreExpanded(
        string $openAPIFilePath,
        string $relativeDestination,
        string $namespace,
        string $className,
        Processor $expectedProcessor
    ): void {
        $this->sut->execute(
            [
                'openAPI' => $openAPIFilePath,
                'destination' => $this->root->url() . '/cache',
                '--namespace' => $namespace,
            ]
        );

        eval('//' . file_get_contents($this->root->getChild($relativeDestination)->url()));
        $cachedClass = eval(sprintf('return new \\%s\\%s();', $namespace, $className));

        self::assertEquals($expectedProcessor, $cachedClass->processor);
    }

    #[Test, TestDox('Numbers will be appended to classnames where otherwise duplicates would exist')]
    public function cachesProcessorsWithSuitableNamesToAvoidDuplicates(): void
    {
        $hatstoreFilePath = __DIR__ . '/../../fixtures/OpenAPI/hatstore.json';
        $hatstoreApi = (new Reader([Membrane\OpenAPIReader\OpenAPIVersion::Version_3_0]))
            ->readFromAbsoluteFilePath($hatstoreFilePath);

        $requestBuilder = new Builder\OpenAPIRequestBuilder();
        $expectedFindHats = $requestBuilder->build(
            new Specification\OpenAPIRequest(
                OpenAPIVersion::fromString($hatstoreApi->openapi),
                new PathParameterExtractor('/hats'),
                $hatstoreApi->paths->getPath('/hats'),
                Method::GET
            )
        );
        $expectedFindHats1 = $requestBuilder->build(
            new Specification\OpenAPIRequest(
                OpenAPIVersion::fromString($hatstoreApi->openapi),
                new PathParameterExtractor('/hats/{id}'),
                $hatstoreApi->paths->getPath('/hats/{id}'),
                Method::GET
            )
        );

        $this->sut->execute(
            [
                'openAPI' => $hatstoreFilePath,
                'destination' => $this->root->url() . '/cache/',
                '--namespace' => 'CommandTest\\Hatstore',
            ]
        );

        eval('//' . file_get_contents($this->root->getChild('root/cache/Request/FindHats.php')->url()));
        $actualFindHats = new \CommandTest\Hatstore\Request\FindHats();
        self::assertEquals($expectedFindHats, $actualFindHats->processor);

        eval('//' . file_get_contents($this->root->getChild('root/cache/Request/FindHats1.php')->url()));
        $actualFindHats1 = new \CommandTest\Hatstore\Request\FindHats1();
        self::assertEquals($expectedFindHats1, $actualFindHats1->processor);
    }

    #[Test, TestDox('It will skip operations for unsupported methods')]
    public function skipsUnsupportedMethods(): void
    {
        $this->sut->execute(
            [
                'openAPI' => __DIR__ . '/../../fixtures/OpenAPI/hatstore.json',
                'destination' => $this->root->url() . '/cache/',
                '--namespace' => 'CommandTest\\Hatstore',
            ]
        );

        self::assertNull($this->root->getChild('cache/Request/TraceHats'));
    }
}
