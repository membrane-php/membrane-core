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
use Membrane\OpenAPIReader\MembraneReader;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
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
#[UsesClass(Processor\AllOf::class)]
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
        $hatstoreApi = (new MembraneReader([OpenAPIVersion::Version_3_0]))
            ->readFromAbsoluteFilePath($hatstoreFilePath);

        $requestBuilder = new Builder\OpenAPIRequestBuilder();
        $expectedFindHats = $requestBuilder->build(
            new Specification\OpenAPIRequest(
                new PathParameterExtractor('/hats'),
                $hatstoreApi->paths['/hats'],
                Method::GET
            )
        );
        $expectedFindHats1 = $requestBuilder->build(
            new Specification\OpenAPIRequest(
                new PathParameterExtractor('/hats/{id}'),
                $hatstoreApi->paths['/hats/{id}'],
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
        $cacheDir = $this->root->url() . '/cache';

        $this->sut->cache($openAPIFilePath, $cacheDir, $namespace);

        $fullClassName = sprintf('\\%s\\%s', $namespace, $className);

        eval('//' . file_get_contents("$cacheDir/$relativeDestination"));
        $cachedClass = eval(sprintf('return new %s();', $fullClassName));

        self::assertEquals($expectedProcessor, $cachedClass->processor);
    }

    #[Test, TestDox('It only caches Requests when build responses is false')]
    public function cacheOnlyRequestProcessors(): void
    {
        $cacheDir = $this->root->url() . '/cache';

        $this->sut->cache(
            __DIR__ . '/../../fixtures/OpenAPI/docs/petstore-expanded.json',
            $cacheDir,
            'ServiceTest\\Petstore\\RequestsOnly',
            true,
            false
        );

        self::assertDirectoryDoesNotExist("$cacheDir/Response");
        self::assertFileDoesNotExist("$cacheDir/CachedResponseBuilder.php");

        self::assertDirectoryExists("$cacheDir/Request");
        self::assertFileExists("$cacheDir/CachedRequestBuilder.php");
    }

    #[Test, TestDox('It only caches Requests when build responses is false')]
    public function cacheOnlyResponseProcessors(): void
    {
        $cacheDir = $this->root->url() . '/cache';

        $this->sut->cache(
            __DIR__ . '/../../fixtures/OpenAPI/docs/petstore-expanded.json',
            $cacheDir,
            'ServiceTest\\Petstore\\ResponsesOnly',
            false,
            true
        );

        self::assertDirectoryExists("$cacheDir/Response");
        self::assertFileExists("$cacheDir/CachedResponseBuilder.php");

        self::assertDirectoryDoesNotExist("$cacheDir/Request");
        self::assertFileDoesNotExist("$cacheDir/CachedRequestBuilder.php");
    }

    public static function provideCasesOfCachedRequestsFromPetstoreExpanded(): array
    {
        $requestBuilder = new OpenAPIRequestBuilder();
        $petstoreExpandedFilePath = __DIR__ . '/../../fixtures/OpenAPI/docs/petstore-expanded.json';
        $petstoreExpandedOpenApi = (new MembraneReader([OpenAPIVersion::Version_3_0]))
            ->readFromAbsoluteFilePath($petstoreExpandedFilePath);

        return [
            'findPets : Request' => [
                $petstoreExpandedFilePath,
                'Request/FindPets.php',
                'ServiceTest\\PetstoreA',
                'Request\\FindPets',
                $requestBuilder->build(
                    new Specification\OpenAPIRequest(
                        new PathParameterExtractor('/pets'),
                        $petstoreExpandedOpenApi->paths['/pets'],
                        Method::GET
                    )
                ),
            ],
            'addPet : Request' => [
                $petstoreExpandedFilePath,
                'Request/AddPet.php',
                'ServiceTest\\PetstoreD',
                'Request\\AddPet',
                $requestBuilder->build(
                    new Specification\OpenAPIRequest(
                        new PathParameterExtractor('/pets'),
                        $petstoreExpandedOpenApi->paths['/pets'],
                        Method::POST
                    )
                ),
            ],
            'find pet by id : Request' => [
                $petstoreExpandedFilePath,
                'Request/FindPetById.php',
                'ServiceTest\\PetstoreE',
                'Request\\FindPetById',
                $requestBuilder->build(
                    new Specification\OpenAPIRequest(
                        new PathParameterExtractor('/pets/{id}'),
                        $petstoreExpandedOpenApi->paths['/pets/{id}'],
                        Method::GET
                    )
                ),
            ],
            'deletePet : Request' => [
                $petstoreExpandedFilePath,
                'Request/DeletePet.php',
                'ServiceTest\\PetstoreH',
                'Request\\DeletePet',
                $requestBuilder->build(
                    new Specification\OpenAPIRequest(
                        new PathParameterExtractor('/pets/{id}'),
                        $petstoreExpandedOpenApi->paths['/pets/{id}'],
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
        $petstoreExpandedOpenApi = (new MembraneReader([OpenAPIVersion::Version_3_0]))
            ->readFromAbsoluteFilePath($petstoreExpandedFilePath);

        return [
            'findPets : 200 Response' => [
                $petstoreExpandedFilePath,
                'Response/Code200/FindPets.php',
                'ServiceTest\\PetstoreB',
                'Response\\Code200\\FindPets',
                $responseBuilder->build(
                    new Specification\OpenAPIResponse(
                        'findPets',
                        '200',
                        $petstoreExpandedOpenApi->paths['/pets']->get->responses['200']
                    )
                ),
            ],
            'findPets : default Response' => [
                $petstoreExpandedFilePath,
                'Response/CodeDefault/FindPets.php',
                'ServiceTest\\PetstoreC',
                'Response\\CodeDefault\\FindPets',
                $responseBuilder->build(
                    new Specification\OpenAPIResponse(
                        'findPets',
                        'default',
                        $petstoreExpandedOpenApi->paths['/pets']->get->responses['default']
                    )
                ),
            ],
            'addPet : 200 Response' => [
                $petstoreExpandedFilePath,
                'Response/Code200/AddPet.php',
                'ServiceTest\\PetstoreE',
                'Response\\Code200\\AddPet',
                $responseBuilder->build(
                    new Specification\OpenAPIResponse(
                        'addPet',
                        '200',
                        $petstoreExpandedOpenApi->paths['/pets']->post->responses['200']
                    )
                ),
            ],
            'addPet : default Response' => [
                $petstoreExpandedFilePath,
                'Response/CodeDefault/AddPet.php',
                'ServiceTest\\PetstoreF',
                'Response\\CodeDefault\\AddPet',
                $responseBuilder->build(
                    new Specification\OpenAPIResponse(
                        'addPet',
                        'default',
                        $petstoreExpandedOpenApi->paths['/pets']->post->responses['default']
                    )
                ),
            ],
            'find pet by id : 200 Response' => [
                $petstoreExpandedFilePath,
                'Response/Code200/FindPetById.php',
                'ServiceTest\\PetstoreF',
                'Response\\Code200\\FindPetById',
                $responseBuilder->build(
                    new Specification\OpenAPIResponse(
                        'find pet by id',
                        '200',
                        $petstoreExpandedOpenApi->paths['/pets/{id}']->get->responses['200']
                    )
                ),
            ],
            'find pet by id : default Response' => [
                $petstoreExpandedFilePath,
                'Response/CodeDefault/FindPetById.php',
                'ServiceTest\\PetstoreG',
                'Response\\CodeDefault\\FindPetById',
                $responseBuilder->build(
                    new Specification\OpenAPIResponse(
                        'find pet by id',
                        'default',
                        $petstoreExpandedOpenApi->paths['/pets/{id}']->get->responses['default']
                    )
                ),
            ],
            'deletePet : 204 Response' => [
                $petstoreExpandedFilePath,
                'Response/Code204/DeletePet.php',
                'ServiceTest\\PetstoreI',
                'Response\\Code204\\DeletePet',
                $responseBuilder->build(
                    new Specification\OpenAPIResponse(
                        'deletePet',
                        '204',
                        $petstoreExpandedOpenApi->paths['/pets/{id}']->delete->responses['204']
                    )
                ),
            ],
        ];
    }
}
