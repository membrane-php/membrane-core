<?php

declare(strict_types=1);

namespace Membrane\Tests\Console\Service;

use Membrane;
use Membrane\Console\Service\CacheOpenAPIProcessors;
use Membrane\Console\Template;
use Membrane\Filter\{String\AlphaNumeric, String\Explode, String\ToPascalCase, Type as TypeFilter};
use Membrane\OpenAPI\Builder as Builder;
use Membrane\OpenAPI\Builder\OpenAPIRequestBuilder;
use Membrane\OpenAPI\ContentType;
use Membrane\OpenAPI\Exception\CannotReadOpenAPI;
use Membrane\OpenAPI\ExtractPathParameters\PathParameterExtractor;
use Membrane\OpenAPI\Filter\PathMatcher;
use Membrane\OpenAPI\Processor\Request;
use Membrane\OpenAPI\Specification;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\Reader;
use Membrane\Processor;
use Membrane\Validator\{FieldSet as FieldSetValidator,
    String\IntString,
    Type as TypeValidator,
    Utility as UtilityValidator};
use org\bovigo\vfs\{vfsStream, vfsStreamDirectory};
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider, Test, TestDox, UsesClass};
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(CacheOpenAPIProcessors::class)]
#[UsesClass(Template\Processor::class)]
#[UsesClass(Template\ResponseBuilder::class)]
#[UsesClass(Template\RequestBuilder::class)]
#[UsesClass(Builder\APIBuilder::class)]
#[UsesClass(Builder\Arrays::class)]
#[UsesClass(Builder\Numeric::class)]
#[UsesClass(Builder\Strings::class)]
#[UsesClass(Builder\Objects::class)]
#[UsesClass(Builder\ParameterBuilder::class)]
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
#[UsesClass(IntString::class)]
#[UsesClass(AlphaNumeric::class)]
#[UsesClass(ToPascalCase::class)]
#[UsesClass(Explode::class)]
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
    private CacheOpenAPIProcessors $sut;

    public function setUp(): void
    {
        $this->root = vfsStream::setup();
        $this->sut = new CacheOpenAPIProcessors(self::createStub(LoggerInterface::class));
    }

    #[Test, TestDox('It will fail if it lacks write permission to the destination filepath')]
    public function failsOnReadonlyDestinations(): void
    {
        $correctApiPath = __DIR__ . '/../../fixtures/OpenAPI/docs/petstore-expanded.json';
        chmod($this->root->url(), 0444);
        $readonlyDestination = $this->root->url() . '/cache';

        $actual = $this->sut->cache($correctApiPath, $readonlyDestination, 'Membrane\\Cache');

        self::assertFalse($actual);
    }

    public static function provideCasesThatFailToRead(): array
    {
        return [
            'cannot read from relative filename' => [
                '/../../fixtures/docs/petstore-expanded.json',
                vfsStream::url('root') . 'cache/routes.php',
            ],
            'cannot route from an api with no routes' => [
                __DIR__ . '/../../fixtures/simple.json',
                vfsStream::url('root') . 'cache/routes.php',
            ],
        ];
    }

    #[Test, TestDox('It will fail if it cannot read an OpenAPI from the given filepath')]
    #[DataProvider('provideCasesThatFailToRead')]
    public function failsOnUnreadableOpenAPI(string $openAPI, string $destination): void
    {
        $actual = $this->sut->cache($openAPI, $destination, 'Membrane\\Cache');

        self::assertFalse($actual);
    }

    #[Test, TestDox('Numbers will be appended to classnames where otherwise duplicates would exist')]
    public function cachesProcessorsWithSuitableNamesToAvoidDuplicates(): void
    {
        $hatstoreFilePath = __DIR__ . '/../../fixtures/OpenAPI/hatstore.json';
        $hatstoreApi = (new Reader([OpenAPIVersion::Version_3_0]))
            ->readFromAbsoluteFilePath($hatstoreFilePath);

        $requestBuilder = new Builder\OpenAPIRequestBuilder();
        $expectedFindHats = $requestBuilder->build(
            new Specification\OpenAPIRequest(
                OpenAPIVersion::Version_3_0,
                new PathParameterExtractor('/hats'),
                $hatstoreApi->paths->getPath('/hats'),
                Method::GET
            )
        );
        $expectedFindHats1 = $requestBuilder->build(
            new Specification\OpenAPIRequest(
                OpenAPIVersion::Version_3_0,
                new PathParameterExtractor('/hats/{id}'),
                $hatstoreApi->paths->getPath('/hats/{id}'),
                Method::GET
            )
        );

        $this->sut->cache($hatstoreFilePath, $this->root->url() . '/cache/', 'ServiceTest\\Hatstore');

        eval('//' . file_get_contents($this->root->getChild('root/cache/Request/FindHats.php')->url()));
        $actualFindHats = eval('return new \\ServiceTest\\Hatstore\\Request\\FindHats();');
        self::assertEquals($expectedFindHats, $actualFindHats->processor);

        eval('//' . file_get_contents($this->root->getChild('root/cache/Request/FindHats1.php')->url()));
        $actualFindHats1 = eval('return new \\ServiceTest\\Hatstore\\Request\\FindHats1();');
        self::assertEquals($expectedFindHats1, $actualFindHats1->processor);
    }

    public static function provideCasesOfCachedRequestsFromPetstoreExpanded(): array
    {
        $requestBuilder = new OpenAPIRequestBuilder();
        $petstoreExpandedFilePath = __DIR__ . '/../../fixtures/OpenAPI/docs/petstore-expanded.json';
        $petstoreExpandedOpenApi = (new Reader([OpenAPIVersion::Version_3_0]))
            ->readFromAbsoluteFilePath($petstoreExpandedFilePath);

        return [
            'findPets : Request' => [
                $petstoreExpandedFilePath,
                'cache/Request/FindPets.php',
                'ServiceTest\\PetstoreA',
                'Request\\FindPets',
                $requestBuilder->build(
                    new Specification\OpenAPIRequest(
                        OpenAPIVersion::Version_3_0,
                        new PathParameterExtractor('/pets'),
                        $petstoreExpandedOpenApi->paths->getPath('/pets'),
                        Method::GET
                    )
                ),
            ],
            'addPet : Request' => [
                $petstoreExpandedFilePath,
                'cache/Request/AddPet.php',
                'ServiceTest\\PetstoreD',
                'Request\\AddPet',
                $requestBuilder->build(
                    new Specification\OpenAPIRequest(
                        OpenAPIVersion::Version_3_0,
                        new PathParameterExtractor('/pets'),
                        $petstoreExpandedOpenApi->paths->getPath('/pets'),
                        Method::POST
                    )
                ),
            ],
            'find pet by id : Request' => [
                $petstoreExpandedFilePath,
                'cache/Request/FindPetById.php',
                'ServiceTest\\PetstoreE',
                'Request\\FindPetById',
                $requestBuilder->build(
                    new Specification\OpenAPIRequest(
                        OpenAPIVersion::Version_3_0,
                        new PathParameterExtractor('/pets/{id}'),
                        $petstoreExpandedOpenApi->paths->getPath('/pets/{id}'),
                        Method::GET
                    )
                ),
            ],
            'deletePet : Request' => [
                $petstoreExpandedFilePath,
                'cache/Request/DeletePet.php',
                'ServiceTest\\PetstoreH',
                'Request\\DeletePet',
                $requestBuilder->build(
                    new Specification\OpenAPIRequest(
                        OpenAPIVersion::Version_3_0,
                        new PathParameterExtractor('/pets/{id}'),
                        $petstoreExpandedOpenApi->paths->getPath('/pets/{id}'),
                        Method::DELETE
                    )
                ),
            ],
        ];
    }

    public static function provideCasesOfCachedResponsesFromPetstoreExpanded(): array
    {
        $responseBuilder = new Builder\OpenAPIResponseBuilder();
        $petstoreExpandedFilePath = __DIR__ . '/../../fixtures/OpenAPI/docs/petstore-expanded.json';
        $petstoreExpandedOpenApi = (new Reader([OpenAPIVersion::Version_3_0]))
            ->readFromAbsoluteFilePath($petstoreExpandedFilePath);

        return [
            'findPets : 200 Response' => [
                $petstoreExpandedFilePath,
                'cache/Response/Code200/FindPets.php',
                'ServiceTest\\PetstoreB',
                'Response\\Code200\\FindPets',
                $responseBuilder->build(
                    new Specification\OpenAPIResponse(
                        OpenAPIVersion::Version_3_0,
                        'findPets',
                        '200',
                        $petstoreExpandedOpenApi->paths->getPath('/pets')->get->responses->getResponse('200')
                    )
                ),
            ],
            'findPets : default Response' => [
                $petstoreExpandedFilePath,
                'cache/Response/CodeDefault/FindPets.php',
                'ServiceTest\\PetstoreC',
                'Response\\CodeDefault\\FindPets',
                $responseBuilder->build(
                    new Specification\OpenAPIResponse(
                        OpenAPIVersion::Version_3_0,
                        'findPets',
                        'default',
                        $petstoreExpandedOpenApi->paths->getPath('/pets')->get->responses->getResponse('default')
                    )
                ),
            ],
            'addPet : 200 Response' => [
                $petstoreExpandedFilePath,
                'cache/Response/Code200/AddPet.php',
                'ServiceTest\\PetstoreE',
                'Response\\Code200\\AddPet',
                $responseBuilder->build(
                    new Specification\OpenAPIResponse(
                        OpenAPIVersion::Version_3_0,
                        'addPet',
                        '200',
                        $petstoreExpandedOpenApi->paths->getPath('/pets')->post->responses->getResponse('200')
                    )
                ),
            ],
            'addPet : default Response' => [
                $petstoreExpandedFilePath,
                'cache/Response/CodeDefault/AddPet.php',
                'ServiceTest\\PetstoreF',
                'Response\\CodeDefault\\AddPet',
                $responseBuilder->build(
                    new Specification\OpenAPIResponse(
                        OpenAPIVersion::Version_3_0,
                        'addPet',
                        'default',
                        $petstoreExpandedOpenApi->paths->getPath('/pets')->post->responses->getResponse('default')
                    )
                ),
            ],
            'find pet by id : 200 Response' => [
                $petstoreExpandedFilePath,
                'cache/Response/Code200/FindPetById.php',
                'ServiceTest\\PetstoreF',
                'Response\\Code200\\FindPetById',
                $responseBuilder->build(
                    new Specification\OpenAPIResponse(
                        OpenAPIVersion::Version_3_0,
                        'find pet by id',
                        '200',
                        $petstoreExpandedOpenApi->paths->getPath('/pets/{id}')->get->responses->getResponse('200')
                    )
                ),
            ],
            'find pet by id : default Response' => [
                $petstoreExpandedFilePath,
                'cache/Response/CodeDefault/FindPetById.php',
                'ServiceTest\\PetstoreG',
                'Response\\CodeDefault\\FindPetById',
                $responseBuilder->build(
                    new Specification\OpenAPIResponse(
                        OpenAPIVersion::Version_3_0,
                        'find pet by id',
                        'default',
                        $petstoreExpandedOpenApi->paths->getPath('/pets/{id}')->get->responses->getResponse('default')
                    )
                ),
            ],
            'deletePet : 204 Response' => [
                $petstoreExpandedFilePath,
                'cache/Response/Code204/DeletePet.php',
                'ServiceTest\\PetstoreI',
                'Response\\Code204\\DeletePet',
                $responseBuilder->build(
                    new Specification\OpenAPIResponse(
                        OpenAPIVersion::Version_3_0,
                        'deletePet',
                        '204',
                        $petstoreExpandedOpenApi->paths->getPath('/pets/{id}')->delete->responses->getResponse('204')
                    )
                ),
            ],
        ];
    }

    #[Test, TestDox('It caches Requests and Responses for the OpenAPI example: petstore-expanded.json')]
    #[DataProvider('provideCasesOfCachedRequestsFromPetstoreExpanded')]
    #[DataProvider('provideCasesOfCachedResponsesFromPetstoreExpanded')]
    public function cachesProcessorsFromPetStoreExpanded(
        string $openAPIFilePath,
        string $relativeDestination,
        string $namespace,
        string $className,
        Processor $expectedProcessor
    ): void {
        $this->sut->cache($openAPIFilePath, $this->root->url() . '/cache', $namespace);

        $fullClassName = sprintf('\\%s\\%s', $namespace, $className);

        eval('//' . file_get_contents($this->root->getChild($relativeDestination)->url()));
        $cachedClass = eval(sprintf('return new %s();', $fullClassName));

        self::assertEquals($expectedProcessor, $cachedClass->processor);
    }

    #[Test, TestDox('It only caches Requests when build responses is false')]
    public function cacheOnlyRequestProcessors(): void
    {
        $this->sut->cache(
            __DIR__ . '/../../fixtures/OpenAPI/docs/petstore-expanded.json',
            $this->root->url() . '/cache',
            'ServiceTest\\Petstore\\RequestsOnly',
            true,
            false
        );

        self::assertDirectoryDoesNotExist($this->root->url() . '/cache/Response');
        self::assertFileDoesNotExist($this->root->url() . '/cache/CachedResponseBuilder.php');
        self::assertDirectoryExists($this->root->url() . '/cache/Request');
        self::assertFileExists($this->root->url() . '/cache/CachedRequestBuilder.php');
    }

    #[Test, TestDox('It only caches Requests when build responses is false')]
    public function cacheOnlyResponseProcessors(): void
    {
        $this->sut->cache(
            __DIR__ . '/../../fixtures/OpenAPI/docs/petstore-expanded.json',
            $this->root->url() . '/cache',
            'ServiceTest\\Petstore\\ResponsesOnly',
            false,
            true
        );

        self::assertDirectoryDoesNotExist($this->root->url() . '/cache/Request');
        self::assertFileDoesNotExist($this->root->url() . '/cache/CachedRequestBuilder.php');
        self::assertFileExists($this->root->url() . '/cache/CachedResponseBuilder.php');
        self::assertDirectoryExists($this->root->url() . '/cache/Response');
    }
}
