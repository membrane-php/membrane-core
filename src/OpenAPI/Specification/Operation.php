<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec as Cebe;
use Membrane\Builder\Specification;

class Operation implements Specification
{
    /** @var array<string, Cebe\Server> */
    public readonly array $servers;
    /** @var array<string, Cebe\Parameter|Cebe\Reference> */
    public readonly array $parameters;
    public readonly string $operationId;

    /**
     * @param array<string, Cebe\Parameter|Cebe\Reference> $pathParameters
     */
    public function __construct(
        Cebe\Operation $operation,
        array $pathParameters,
        Cebe\Server ...$pathServers
    ) {
        // OpenAPI Spec: Operation servers override PathItem and Root level servers
        $this->servers = $this->getServers(...$operation->servers !== [] ? $operation->servers : $pathServers);

        // OpenAPI Spec: If a parameter is already defined by Path Item, the new definition will override it
        $this->parameters = array_merge($pathParameters, $this->getParameters($operation->parameters));

        $this->operationId = $operation->operationId;
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

    
}
