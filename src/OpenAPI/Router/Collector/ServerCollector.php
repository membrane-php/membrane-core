<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Router\Collector;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Router\Collection\ServerCollection;

class ServerCollector
{
    public function collect(OpenApi $openApi): ServerCollection
    {
        $collection = [];

        $rootServers = $this->getServers($openApi);
        foreach ($openApi->paths as $path => $pathObject) {
            $pathServers = $this->getServers($pathObject);
            foreach ($pathObject->getOperations() as $operation => $operationObject) {
                $operationServers = $this->getServers($operationObject);

                if ($operationObject->operationId === null) {
                    throw CannotProcessOpenAPI::missingOperationId($path, $operation);
                }

                if ($operationServers !== []) {
                    $collection[$operationObject->operationId] = $operationServers;
                } elseif ($pathServers !== []) {
                    $collection[$operationObject->operationId] = $pathServers;
                } else {
                    $collection[$operationObject->operationId] = $rootServers;
                }
            }
        }

        return $this->mapServers($collection);
    }

    /** @param string[][] $collection */
    private function mapServers(array $collection): ServerCollection
    {
        $mapOperationIds = $mapServers = [];

        foreach ($collection as $operationId => $servers) {
            foreach ($servers as $server) {
                if (!in_array($server, $mapServers)) {
                    $mapServers[] = $server;
                }
                $mapOperationIds[array_search($server, $mapServers)][] = $operationId;
            }
        }

        return new ServerCollection($mapOperationIds, $this->getCaptureGroup($mapServers));
    }

    /**
     * @param string[] $servers
     * @return string[]
     */
    private function getCaptureGroup(array $servers): array
    {
        return array_map(fn($p) => sprintf('%s(*MARK:%d)', $p, array_search($p, $servers)), $servers);
    }

    /** @return string[] */
    private function getServers(OpenApi|PathItem|Operation $object): array
    {
        return array_map(fn($p) => $this->getServerRegex($p->url), $object->servers);
    }

    private function getServerRegex(string $serverURL): string
    {
        return sprintf('%s', preg_replace('#{[^/]+}#', '([^/]+)', $serverURL));
    }
}
