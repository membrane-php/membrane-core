<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Method;

class Response extends APISpec
{
    public readonly ?Schema $schema;
    public readonly string $operationId;

    public function __construct(string $absoluteFilePath, string $url, Method $method, string $httpStatus)
    {
        parent::__construct($absoluteFilePath, $url, $method);

        $operation = $this->getOperation($method);
        $this->operationId = $operation->operationId ?? '';
        $response = $this->getResponse($operation, $httpStatus);

        $this->schema = $response->content !== [] ? $this->getSchema($response->content) : null;
    }

    private function getResponse(Operation $operation, string $httpStatus): \cebe\openapi\spec\Response
    {
        return $operation->responses[$httpStatus]
            ??
            $operation->responses['default']
            ??
            throw CannotProcessOpenAPI::responseNotFound($httpStatus);
    }
}
