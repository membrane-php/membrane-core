<?php

declare(strict_types=1);

namespace Console\Template;

use Membrane\Console\Template;
use Membrane\OpenAPI\Specification\Response;
use Membrane\OpenAPI\Specification\RouteMatch;
use Membrane\OpenAPIReader\MembraneReader;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
use Membrane\OpenAPIRouter\RouteCollection;
use Membrane\OpenAPIRouter\RouteCollector;
use Membrane\OpenAPIRouter\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Template\RouteMatchBuilder::class)]
#[UsesClass(RouteMatch::class)]
class RouteMatchBuilderTest extends TestCase
{
    private string $petstoreAPIPath = __DIR__ . '/../../fixtures/OpenAPI/docs/petstore-expanded.json';

    #[Test, TestDox('createFromTemplate returns string PHP code that evaluates to a CachedRouteMatchBuilder')]
    public function createFromTemplateReturnsPHPString(): \TemplateTest\Petstore\CachedRouteMatchBuilder
    {
        $namespace = 'TemplateTest\\Petstore';
        $classname = 'CachedRouteMatchBuilder';

        $petstoreExpandedFilePath = $this->petstoreAPIPath;
        $map = [
            'findPets' => 'TemplateTest\\Petstore\\Request\\FindPets',
            'addPet' => 'TemplateTest\\Petstore\\Request\\AddPet',
            'find pet by id' => 'TemplateTest\\Petstore\\Request\\FindPetById',
            'deletePet' => 'TemplateTest\\Petstore\\Request\\DeletePet',
        ];

        $sut = new Template\RouteMatchBuilder(
            $namespace,
            $classname,
            $petstoreExpandedFilePath,
            $map,
        );
        eval('//' . $sut->getCode());

        $createdBuilder = eval(sprintf(
            'return new \\%s\\%s();',
            $namespace,
            $classname,
        ));

        self::assertInstanceOf('\TemplateTest\Petstore\CachedRouteMatchBuilder', $createdBuilder);

        return $createdBuilder;
    }

    #[Test, TestDox('It can support cached processors by searching for them in the map')]
    #[Depends('createFromTemplateReturnsPHPString')]
    public function templatedBuilderCanSupportCachedProcessors(
        \TemplateTest\Petstore\CachedRouteMatchBuilder $cachedRouteMatchBuilder
    ): RouteMatch {
        $routeMatch = new RouteMatch('findPets', $this->petstoreAPIPath);

        self::assertTrue($cachedRouteMatchBuilder->supports($routeMatch));

        return $routeMatch;
    }

    #[Test, TestDox('It can build cached processors by searching for them in the map')]
    #[Depends('createFromTemplateReturnsPHPString'), Depends('templatedBuilderCanSupportCachedProcessors')]
    public function templatedBuilderCanBuildCachedProcessors(
        \TemplateTest\Petstore\CachedRouteMatchBuilder $cachedRouteMatchBuilder,
        RouteMatch $routeMatch,
    ): void {
        eval(<<<'PHP'
namespace TemplateTest\Petstore\Request;

use Membrane;

class FindPets implements Membrane\Processor
{
    public readonly Membrane\Processor $processor;

    public function __construct()
    {
        $this->processor = new Membrane\Processor\Field('');
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
PHP);

        $processor = $cachedRouteMatchBuilder->build($routeMatch);

        self::assertInstanceOf('\\TemplateTest\\Petstore\\Request\\FindPets', $processor);
    }
}
