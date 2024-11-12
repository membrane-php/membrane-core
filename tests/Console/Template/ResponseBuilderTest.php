<?php

declare(strict_types=1);

namespace Membrane\Tests\Console\Template;

use Membrane\Console\Template;
use Membrane\OpenAPI\Specification\Response;
use Membrane\OpenAPIReader\MembraneReader;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
use Membrane\OpenAPIRouter\RouteCollection;
use Membrane\OpenAPIRouter\RouteCollector;
use Membrane\OpenAPIRouter\Router;
use Membrane\Processor\Field;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Template\ResponseBuilder::class)]
#[UsesClass(Response::class)]
#[UsesClass(Field::class)]
class ResponseBuilderTest extends TestCase
{
    private $petstoreAPIPath = __DIR__ . '/../../fixtures/OpenAPI/docs/petstore-expanded.json';

    #[Test, TestDox('createFromTemplate will return a string of PHP code that can evaluate to a CachedResponseBuilder')]
    public function createFromTemplateReturnsPHPString(): \ResponseBuilderTemplateTest\Petstore\CachedResponseBuilder
    {
        $namespace = 'ResponseBuilderTemplateTest\\Petstore';
        $petstoreExpandedFilePath = $this->petstoreAPIPath;
        $map = [
            'findPets' => [
                'Code200' => 'ResponseBuilderTemplateTest\\Petstore\\Response\\Code200\\FindPets',
                'CodeDefault' => 'ResponseBuilderTemplateTest\\Petstore\\Response\\CodeDefault\\FindPets',
            ],
            'addPet' => [
                'Code200' => 'ResponseBuilderTemplateTest\\Petstore\\Response\\Code200\\AddPet',
                'CodeDefault' => 'ResponseBuilderTemplateTest\\Petstore\\Response\\CodeDefault\\AddPet',
            ],
            'find pet by id' => [
                'Code200' => 'ResponseBuilderTemplateTest\\Petstore\\Response\\Code200\\FindPetById',
                'CodeDefault' => 'ResponseBuilderTemplateTest\\Petstore\\Response\\CodeDefault\\FindPetById',
            ],
            'deletePet' => [
                'Code204' => 'ResponseBuilderTemplateTest\\Petstore\\Response\\Code200\\DeletePet',
            ],
        ];

        $sut = new Template\ResponseBuilder($namespace, $petstoreExpandedFilePath, $map);
        $phpString = $sut->getCode();
        eval('//' . $phpString);

        $openAPI = (new MembraneReader([OpenAPIVersion::Version_3_0]))
            ->readFromAbsoluteFilePath($petstoreExpandedFilePath);

        $routeCollection = (new RouteCollector())->collect($openAPI);
        $createdBuilder = eval(
            sprintf(
                'return new \\%s\\CachedResponseBuilder(new %s(new %s(%s)));',
                $namespace,
                Router::class,
                RouteCollection::class,
                var_export($routeCollection->routes, true)
            )
        );

        self::assertInstanceOf('\ResponseBuilderTemplateTest\Petstore\CachedResponseBuilder', $createdBuilder);

        return $createdBuilder;
    }

    #[Test, TestDox('It can support cached processors by searching for them in the map')]
    #[Depends('createFromTemplateReturnsPHPString')]
    public function templatedBuilderCanSupportCachedProcessors(
        \ResponseBuilderTemplateTest\Petstore\CachedResponseBuilder $cachedResponseBuilder
    ): Response {
        $responseSpecification = new Response(
            $this->petstoreAPIPath,
            'https://petstore.swagger.io/v2/pets',
            Method::GET,
            '200'
        );

        self::assertTrue($cachedResponseBuilder->supports($responseSpecification));

        return $responseSpecification;
    }

    #[Test, TestDox('It can build cached processors by searching for them in the map')]
    #[Depends('createFromTemplateReturnsPHPString'), Depends('templatedBuilderCanSupportCachedProcessors')]
    public function templatedBuilderCanBuildCachedProcessors(
        \ResponseBuilderTemplateTest\Petstore\CachedResponseBuilder $cachedResponseBuilder,
        Response $responseSpecification
    ): void {
        eval(
            '

namespace ResponseBuilderTemplateTest\Petstore\Response\Code200;

use Membrane;
    
class FindPets implements Membrane\Processor
{
    public readonly Membrane\Processor $processor;
    
    public function __construct()
    {
        $this->processor = new Membrane\Processor\Field(\'\');
    }

    public function processes(): string
    {
        return $this->processor->processes();
    }

    public function process(Membrane\Result\FieldName $parentFieldName, mixed $value): Membrane\Result\Result
    {
        return $this->processor->process($parentFieldName, $value);
    }

    public function __toString()
    {
        return (string)$this->processor;
    }

    public function __toPHP(): string
    {
        return $this->processor->__toPHP();
    }
}
'
        );

        $processor = $cachedResponseBuilder->build($responseSpecification);

        self::assertInstanceOf('\\ResponseBuilderTemplateTest\\Petstore\\Response\\Code200\\FindPets', $processor);
    }
}
