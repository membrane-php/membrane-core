<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Router\Collector;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\PathItem;
use Membrane\OpenAPI\Router\Collection\PathCollection;

class PathCollector
{
    public function collect(OpenApi $openApi): PathCollection
    {
        $operationIdMap = $pathMap = [];
        $i = 0;

        foreach ($openApi->paths as $path => $pathItem) {
            $pathMap[$i] = $this->getPathRegex($path, $i);
            $operationIdMap[$i++] = $this->getOperations($pathItem);
        }

        return new PathCollection($operationIdMap, $pathMap);
    }

    /** @return string[] */
    private function getOperations(PathItem $pathItem): array
    {
        $operations = [];
        foreach ($pathItem->getOperations() as $method => $operationObject) {
            $operations[$method] = $operationObject->operationId;
        }

        return $operations;
    }

    private function getPathRegex(string $pathURL, int $captureGroup): string
    {
        return sprintf('%s(*MARK:%d)', preg_replace('#{[^/]+}#', '([^/]+)', $pathURL), $captureGroup);
    }
}
