<?php

declare(strict_types=1);

namespace Membrane\Tests\Console\Template;

use Membrane\Console\Template;
use Membrane\OpenAPI\Specification\Request;
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

#[CoversClass(Template\RequestBuilder::class)]
#[UsesClass(Request::class)]
#[UsesClass(Field::class)]
class RequestBuilderTest extends TestCase
{
    private string $petstoreAPIPath = __DIR__ . '/../../fixtures/OpenAPI/docs/petstore-expanded.json';

    #[Test, TestDox('createFromTemplate will return a string of PHP code that can evaluate to a CachedRequestBuilder')]
    public function createFromTemplateReturnsPHPString(): \RequestBuilderTemplateTest\Petstore\CachedRequestBuilder
    {
        $namespace = 'RequestBuilderTemplateTest\\Petstore';
        $petstoreExpandedFilePath = $this->petstoreAPIPath;
        $map = [
            'findPets' => 'RequestBuilderTemplateTest\\Petstore\\Request\\FindPets',
            'addPet' => 'RequestBuilderTemplateTest\\Petstore\\Request\\AddPet',
            'find pet by id' => 'RequestBuilderTemplateTest\\Petstore\\Request\\FindPetById',
            'deletePet' => 'RequestBuilderTemplateTest\\Petstore\\Request\\DeletePet',
        ];

        $sut = new Template\RequestBuilder($namespace, $petstoreExpandedFilePath, $map);

        $phpString = $sut->getCode();

        eval('//' . $phpString);

        $openAPI = (new MembraneReader([OpenAPIVersion::Version_3_0]))
            ->readFromAbsoluteFilePath($petstoreExpandedFilePath);
        $routeCollection = (new RouteCollector())
            ->collect($openAPI);
        $createdBuilder = eval(
            sprintf(
                'return new \\%s\\CachedRequestBuilder(new %s(new %s(%s)));',
                $namespace,
                Router::class,
                RouteCollection::class,
                var_export($routeCollection->routes, true)
            )
        );

        self::assertInstanceOf('\RequestBuilderTemplateTest\Petstore\CachedRequestBuilder', $createdBuilder);

        return $createdBuilder;
    }

    #[Test, TestDox('It can support cached processors by searching for them in the map')]
    #[Depends('createFromTemplateReturnsPHPString')]
    public function templatedBuilderCanSupportCachedProcessors(
        \RequestBuilderTemplateTest\Petstore\CachedRequestBuilder $cachedRequestBuilder
    ): Request {
        $requestSpecification = new Request(
            $this->petstoreAPIPath,
            'https://petstore.swagger.io/v2/pets',
            Method::GET,
        );

        self::assertTrue($cachedRequestBuilder->supports($requestSpecification));

        return $requestSpecification;
    }

    #[Test, TestDox('It can build cached processors by searching for them in the map')]
    #[Depends('createFromTemplateReturnsPHPString'), Depends('templatedBuilderCanSupportCachedProcessors')]
    public function templatedBuilderCanBuildCachedProcessors(
        \RequestBuilderTemplateTest\Petstore\CachedRequestBuilder $cachedRequestBuilder,
        Request $requestSpecification
    ): void {
        eval(
            '
namespace RequestBuilderTemplateTest\Petstore\Request;

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

        $processor = $cachedRequestBuilder->build($requestSpecification);

        self::assertInstanceOf('\\RequestBuilderTemplateTest\\Petstore\\Request\\FindPets', $processor);
    }
}
