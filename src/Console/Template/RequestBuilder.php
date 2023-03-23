<?php

declare(strict_types=1);

namespace Membrane\Console\Template;

class RequestBuilder
{
    private const TEMPLATE_CODE =
        '<?php

namespace %s;

use Membrane\Builder\Builder;
use Membrane\Processor;
use Membrane\Builder\Specification;
use Membrane\OpenAPIRouter\Router\Router;
use Membrane\OpenAPI\Specification\Request as RequestSpecification;

class CachedRequestBuilder implements Builder
{
    private const OPEN_API_FILENAME = \'%s\';
    private const MAP = [%s];

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
        ';

    /** @param array<string, string> $map */
    public function createFromTemplate(string $namespace, string $openAPIFilePath, array $map): string
    {
        $implodedMap = '';
        foreach ($map as $operationId => $processor) {
            $implodedMap .= sprintf('\'%s\' => \'%s\', ', $operationId, $processor);
        }

        return sprintf(self::TEMPLATE_CODE, $namespace, $openAPIFilePath, $implodedMap);
    }
}
