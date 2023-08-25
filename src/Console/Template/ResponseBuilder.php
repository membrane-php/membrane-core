<?php

declare(strict_types=1);

namespace Membrane\Console\Template;

class ResponseBuilder
{
    private const TEMPLATE_CODE =
        '<?php

declare(strict_types=1);

namespace %s;

use Membrane\Builder\Builder;
use Membrane\Processor;
use Membrane\Builder\Specification;
use Membrane\OpenAPIRouter\Router\Router;
use \Membrane\OpenAPI\Specification\Response as ResponseSpecification;

class CachedResponseBuilder implements Builder
{
    private const OPEN_API_FILENAME = \'%s\';
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
        $statusCode = \'Code\' . ucfirst($specification->statusCode);

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
        ';

    /** @param array<string, array<string,string>> $map */
    public function createFromTemplate(string $namespace, string $openAPIFilePath, array $map): string
    {
        $implodedMap = '';
        foreach ($map as $operationId => $responses) {
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

        return sprintf(self::TEMPLATE_CODE, $namespace, $openAPIFilePath, $implodedMap);
    }
}
