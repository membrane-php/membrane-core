<?php

declare(strict_types=1);

namespace Membrane\Console\Template;


use Atto\CodegenTools\ClassDefinition\PHPClassDefinition;

class RequestBuilder implements PHPClassDefinition
{
    private const TEMPLATE_CODE = <<<'END'
<?php

declare(strict_types=1);

namespace %s;

use Membrane\Builder\Builder;
use Membrane\Processor;
use Membrane\Builder\Specification;
use Membrane\OpenAPIRouter\Router;
use Membrane\OpenAPI\Specification\Request as RequestSpecification;

class CachedRequestBuilder implements Builder
{
    private const OPEN_API_FILENAME = '%s';
    private const MAP = [%s];

    /** @var array<string,string> */
    private array $operationIDs = [];

    public function __construct(
        private readonly Router $router
    ) {
    }

    /** @phpstan-assert-if-true RequestSpecification $specification */
    public function supports(Specification $specification): bool
    {
        if (!$specification instanceof RequestSpecification) {
            return false;
        }

        $operationId = $this->getOperationId($specification);

        return $specification->absoluteFilePath === self::OPEN_API_FILENAME && isset(self::MAP[$operationId]);
    }

    public function build(Specification $specification): Processor
    {
        assert($specification instanceof RequestSpecification && $this->supports($specification));

        $operationId = $this->getOperationId($specification);

        return new (self::MAP[$operationId])();
    }

    private function getOperationId(RequestSpecification $specification): string
    {
        $key = spl_object_hash($specification);

        if (!isset($this->operationIDs[$key])) {
            $this->operationIDs[$key] = $this->router->route($specification->url, $specification->method->value);
        }

        return $this->operationIDs[$key];
    }
}
END;

    /** @param array<string, string> $map */
    public function __construct(
        private readonly string $namespace,
        private readonly string $openAPIFilePath,
        private readonly array $map
    ) {
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getName(): string
    {
        return 'CachedRequestBuilder';
    }

    public function getCode(): string
    {
        $implodedMap = '';
        foreach ($this->map as $operationId => $processor) {
            $implodedMap .= sprintf('\'%s\' => \'%s\', ', $operationId, $processor);
        }

        return sprintf(self::TEMPLATE_CODE, $this->namespace, $this->openAPIFilePath, $implodedMap);
    }
}
