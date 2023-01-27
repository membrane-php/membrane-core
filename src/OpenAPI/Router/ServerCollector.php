<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Router;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;

class ServerCollector
{
    /** @return array{'operationIds': string[][], 'servers': string[]} */
    public function collect(OpenApi $openApi): array
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

    /**
     * @param string[][] $collection
     * @return array{'operationIds': string[][], 'servers': string[]}
     */
    private function mapServers(array $collection): array
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


        return ['operationIds' => $mapOperationIds, 'servers' => $this->getCaptureGroup($mapServers)];
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
