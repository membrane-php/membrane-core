<?php

declare(strict_types=1);

namespace Membrane\Console\Template;


use Atto\CodegenTools\ClassDefinition\PHPClassDefinition;

class ResponseBuilder implements PHPClassDefinition
{
    private const TEMPLATE_CODE = <<<'END'
<?php

declare(strict_types=1);

namespace %s;

use Membrane\Builder\Builder;
use Membrane\Processor;
use Membrane\Builder\Specification;
use Membrane\OpenAPIRouter\Router;
use \Membrane\OpenAPI\Specification\Response as ResponseSpecification;

class CachedResponseBuilder implements Builder
{
    private const OPEN_API_FILENAME = '%s';
    private const MAP = [%s];

    private array $operationIDs = [];

    public function __construct(
        private readonly Router $router
    ) {
    }

    /** @phpstan-assert-if-true ResponseSpecification $specification */
    public function supports(Specification $specification): bool
    {
        if (!$specification instanceof ResponseSpecification) {
            return false;
        }

        $operationId = $this->getOperationId($specification);

        return $specification->absoluteFilePath === self::OPEN_API_FILENAME && isset(self::MAP[$operationId]);
    }

    public function build(Specification $specification): Processor
    {
        assert($specification instanceof ResponseSpecification && $this->supports($specification));

        $operationId = $this->getOperationId($specification);
        $statusCode = 'Code' . ucfirst($specification->statusCode);

        return new (self::MAP[$operationId][$statusCode])();
    }

    private function getOperationId(ResponseSpecification $specification): string
    {
        $key = spl_object_hash($specification);

        if (!isset($this->operationIDs[$key])) {
            $this->operationIDs[$key] = $this->router->route($specification->url, $specification->method->value);
        }

        return $this->operationIDs[$key];
    }
}
END;

    /** @param array<string, array<string,string>> $map */
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
        return 'CachedResponseBuilder';
    }

    public function getCode(): string
    {
        $implodedMap = '';
        foreach ($this->map as $operationId => $responses) {
            $implodedResponses = implode(
                ', ',
                array_map(
                    fn(string $key, string $value) => "'$key' =>  '$value'",
                    array_keys($responses),
                    $responses
                )
            );
            $implodedMap .= sprintf('\'%s\' => [%s], ', $operationId, $implodedResponses);
        }

        return sprintf(self::TEMPLATE_CODE, $this->namespace, $this->openAPIFilePath, $implodedMap);
    }
}
