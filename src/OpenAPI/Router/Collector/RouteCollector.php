<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Router\Collector;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Router\ValueObject;

class RouteCollector
{
    /**
     * @return  array{
     *              'hosted' : array{
     *                  'static': array{
     *                      'static': string[][],
     *                      'dynamic': array{
     *                          'regex': string,
     *                          'paths': string[][]
     *                      }
     *                  },
     *                  'dynamic': array{
     *                      'regex': string,
     *                      'servers': array{
     *                          'static': string[][],
     *                          'dynamic': array{
     *                              'regex': string,
     *                              'paths': string[][]
     *                          }
     *                      }
     *                  }
     *              },
     *              'hostless' : array{
     *                  'static': array{
     *                      'static': string[][],
     *                      'dynamic': array{
     *                          'regex': string,
     *                          'paths': string[][]
     *                      }
     *                  },
     *                  'dynamic': array{
     *                      'regex': string,
     *                      'servers': array{
     *                          'static': string[][],
     *                          'dynamic': array{
     *                              'regex': string,
     *                              'paths': string[][]
     *                          }
     *                      }
     *                  }
     *              }
     *          }
     */
    public function collect(OpenApi $openApi): array
    {
        $routes = $this->collectRoutes($openApi);

        if ($routes === []) {
            throw new \Exception();
        }

        $mergedRoutes = $this->mergeRoutes(...$routes);

        $sortedRoutes = $this->sortRoutes($mergedRoutes);

        return $sortedRoutes;
    }

    /** @return ValueObject\Route[] */
    private function collectRoutes(OpenApi $openApi): array
    {
        $rootServers = $this->getServers($openApi);
        foreach ($openApi->paths as $path => $pathObject) {
            $pathServers = $this->getServers($pathObject);
            foreach ($pathObject->getOperations() as $operation => $operationObject) {
                $operationServers = $this->getServers($operationObject);

                if ($operationObject->operationId === null) {
                    throw CannotProcessOpenAPI::missingOperationId($path, $operation);
                }

                if ($operationServers !== []) {
                    $servers = $operationServers;
                } elseif ($pathServers !== []) {
                    $servers = $pathServers;
                } else {
                    $servers = $rootServers;
                }

                $collection[] = new ValueObject\Route($servers, $path, $operation, $operationObject->operationId);
            }
        }
        return $collection ?? [];
    }

    /** @return string[] */
    private function getServers(OpenApi|PathItem|Operation $object): array
    {
        return array_map(fn($p) => $p->url, $object->servers);
    }

    /** @return string[][][] */
    private function mergeRoutes(ValueObject\Route ...$routes): array
    {
        foreach ($routes as $route) {
            foreach ($route->servers as $server) {
                $routesArray[$server][$route->path][$route->method] = $route->operationId;
            }
        }

        return $routesArray ?? [];
    }

    /**
     * @param string[][][] $routes
     * @return  array{
     *              'hosted' : array{
     *                  'static': array{
     *                      'static': string[][],
     *                      'dynamic': array{
     *                          'regex': string,
     *                          'paths': string[][]
     *                      }
     *                  },
     *                  'dynamic': array{
     *                      'regex': string,
     *                      'servers': array{
     *                          'static': string[][],
     *                          'dynamic': array{
     *                              'regex': string,
     *                              'paths': string[][]
     *                          }
     *                      }
     *                  }
     *              },
     *              'hostless' : array{
     *                  'static': array{
     *                      'static': string[][],
     *                      'dynamic': array{
     *                          'regex': string,
     *                          'paths': string[][]
     *                      }
     *                  },
     *                  'dynamic': array{
     *                      'regex': string,
     *                      'servers': array{
     *                          'static': string[][],
     *                          'dynamic': array{
     *                              'regex': string,
     *                              'paths': string[][]
     *                          }
     *                      }
     *                  }
     *              }
     *          }
     */
    private function sortRoutes(array $routes): array
    {
        $routesWithSortedPaths = [];

        foreach ($routes as $server => $paths) {
            $routesWithSortedPaths[$server] = $this->sortPaths($paths);
        }

        $routesWithSortedServers = $this->sortServers($routesWithSortedPaths);

        return $routesWithSortedServers;
    }

    /**
     * @param string[][] $paths
     * @return  array{
     *              'static': string[][],
     *              'dynamic': array{
     *                  'regex': string,
     *                  'paths': string[][]
     *              }
     *          }
     */
    private function sortPaths(array $paths): array
    {
        $staticPaths = $dynamicPaths = $groupRegex = [];

        foreach ($paths as $path => $operations) {
            $pathRegex = $this->getRegex($path);
            if ($path === $pathRegex) {
                $staticPaths[$path] = $operations;
            } else {
                $dynamicPaths[$path] = $operations;
                $groupRegex[] = sprintf('%s(*MARK:%s)', $pathRegex, $path);
            }
        }

        return [
            'static' => $staticPaths,
            'dynamic' => [
                'regex' => sprintf('#^(?|%s)$#', implode('|', $groupRegex)),
                'paths' => $dynamicPaths,
            ],
        ];
    }

    /**
     * @param array<array{
     *              'static': string[][],
     *              'dynamic': array{
     *                  'regex': string,
     *                  'paths': string[][]
     *              }
     *          }> $servers
     * @return  array{
     *              'hosted' : array{
     *                  'static': array{
     *                      'static': string[][],
     *                      'dynamic': array{
     *                          'regex': string,
     *                          'paths': string[][]
     *                      }
     *                  },
     *                  'dynamic': array{
     *                      'regex': string,
     *                      'servers': array{
     *                          'static': string[][],
     *                          'dynamic': array{
     *                              'regex': string,
     *                              'paths': string[][]
     *                          }
     *                      }
     *                  }
     *              },
     *              'hostless' : array{
     *                  'static': array{
     *                      'static': string[][],
     *                      'dynamic': array{
     *                          'regex': string,
     *                          'paths': string[][]
     *                      }
     *                  },
     *                  'dynamic': array{
     *                      'regex': string,
     *                      'servers': array{
     *                          'static': string[][],
     *                          'dynamic': array{
     *                              'regex': string,
     *                              'paths': string[][]
     *                          }
     *                      }
     *                  }
     *              }
     *          }
     */
    private function sortServers(array $servers): array
    {
        $hostedServers = $hostlessServers = [];
        foreach ($servers as $server => $paths) {
            if (parse_url($server, PHP_URL_HOST) === null) {
                $hostlessServers[$server] = $paths;
            } else {
                $hostedServers[$server] = $paths;
            }
        }

        $hostedStaticServers = $hostedDynamicServers = $hostedGroupRegex = [];
        foreach ($hostedServers as $server => $paths) {
            $serverRegex = $this->getRegex($server);
            if ($server === $serverRegex) {
                $hostedStaticServers[$server] = $paths;
            } else {
                $hostedDynamicServers[$server] = $paths;
                $hostedGroupRegex[] = sprintf('%s(*MARK:%s)', $serverRegex, $server);
            }
        }

        $hostlessStaticServers = $hostlessDynamicServers = $hostlessGroupRegex = [];
        foreach ($hostlessServers as $server => $paths) {
            $serverRegex = $this->getRegex($server);
            if ($server === $serverRegex) {
                $hostlessStaticServers[$server] = $paths;
            } else {
                $hostlessDynamicServers[$server] = $paths;
                $hostlessGroupRegex[] = sprintf('%s(*MARK:%s)', $serverRegex, $server);
            }
        }

        return [
            'hosted' => [
                'static' => $hostedStaticServers,
                'dynamic' => [
                    'regex' => sprintf('#^(?|%s)#', implode('|', $hostedGroupRegex)),
                    'servers' => $hostedDynamicServers,
                ],
            ],
            'hostless' => [
                'static' => $hostlessStaticServers,
                'dynamic' => [
                    'regex' => sprintf('#^(?|%s)#', implode('|', $hostlessGroupRegex)),
                    'servers' => $hostlessDynamicServers,
                ],
            ],
        ];
    }

    private function getRegex(string $path): string
    {
        return preg_replace('#{[^/]+}#', '([^/])', $path) ?? throw throw new \Exception();
    }
}
