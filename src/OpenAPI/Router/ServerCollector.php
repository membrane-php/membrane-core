<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Router;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;

class ServerCollector
{
    /** @return array{'operationIds': string[][], 'servers': string[]} */
    public function collect(OpenApi $openApi): array
    {
        $operationIdMap = $serverMap = [];
        $i = 0;

        $rootServers = $this->getServers($openApi);
        foreach ($openApi->paths as $path) {
            $pathServers = $this->getServers($path);
            foreach ($path->getOperations() as $operation) {
                $operationServers = $this->getServers($operation);

                if ($operationServers !== []) {
                    $operationServers = $this->appendOperationId($operationServers, $operation->operationId);
                    foreach ($operationServers as $operationServer => $operationIds) {
                        $serverMap[$i] = $this->getServerRegex($operationServer, $i);
                        $operationIdMap[$i++] = $operationIds;
                    }
                } elseif ($pathServers !== []) {
                    $pathServers = $this->appendOperationId($pathServers, $operation->operationId);
                } else {
                    $rootServers = $this->appendOperationId($rootServers, $operation->operationId);
                }
            }

            foreach ($pathServers as $pathServer => $operationIds) {
                if ($pathServer !== []) {
                    $serverMap[$i] = $this->getServerRegex($pathServer, $i);
                    $operationIdMap[$i++] = $operationIds;
                }
            }
        }
        foreach ($rootServers as $rootServer => $operationIds) {
            if ($rootServer !== []) {
                $serverMap[$i] = $this->getServerRegex($rootServer, $i);
                $operationIdMap[$i++] = $operationIds;
            }
        }

        return ['operationIds' => $operationIdMap, 'servers' => $serverMap];
    }

    /**
     * @param string[][] $servers
     * @return string[][]
     */
    private function appendOperationId(array $servers, string $operationId): array
    {
        $newServers = $servers;
        foreach ($servers as $server => $operationIds) {
            $newServers[$server][] = $operationId;
        }

        return $newServers;
    }

    /** @return string[][] */
    private function getServers(OpenApi|PathItem|Operation $object): array
    {
        $servers = [];
        foreach ($object->servers as $server) {
            $servers[$server->url] = [];
        }

        return $servers;
    }

    private function getServerRegex(string $serverURL, int $captureGroup): string
    {
        return sprintf('%s(*MARK:%d)', preg_replace('#{[^/]+}#', '([^/]+)', $serverURL), $captureGroup);
    }
}
