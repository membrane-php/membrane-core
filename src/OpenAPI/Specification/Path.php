<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec as Cebe;
use Membrane\Builder\Specification;

class Path implements Specification
{
    /** @var array<string, Cebe\Server> */
    public readonly array $servers;
    /** @var array<string, Cebe\Parameter|Cebe\Reference> */
    public readonly array $parameters;
    /** @var array<string, Operation> */
    public readonly array $operations;

    public function __construct(
        Cebe\PathItem $pathItem,
        Cebe\Server ...$rootServers,
    ) {
        // OpenAPI Spec: PathItem servers override Root level servers
        $this->servers = $this->getServers(...$pathItem->servers !== [] ? $pathItem->servers : $rootServers);

        $this->parameters = $this->getParameters($pathItem->parameters);

        $this->operations = $this->getOperations(...$pathItem->getOperations());
    }

    /** @return array<string, Cebe\Server> */
    private function getServers(Cebe\Server ...$servers): array
    {
        $serverArray = [];
        foreach ($servers as $server) {
            $serverArray[$server->url] = $server;
        }
        return $serverArray;
    }

    /**
     * @param Cebe\Parameter[]|Cebe\Reference[] $parameters
     * @return array<string, Cebe\Parameter|Cebe\Reference>
     */
    private function getParameters(array $parameters): array
    {
        $parameterArray = [];
        foreach ($parameters as $parameter) {
            $parameterArray[$parameter->name] = $parameter;
        }
        return $parameterArray;
    }

    /** @return array<string, Operation> */
    private function getOperations(Cebe\Operation ...$operations): array
    {
        $operationArray = [];
        foreach ($operations as $method => $operation) {
            assert(is_string($method));
            $operationArray[$method] = new Operation($operation, $this->parameters, ...$this->servers);
        }
        return $operationArray;
    }
}
