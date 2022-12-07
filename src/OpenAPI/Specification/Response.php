<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Exception;
use Membrane\OpenAPI\Method;

class Response extends APISpec
{
    public readonly ?Schema $schema;

    public function __construct(string $filePath, string $url, Method $method, string $httpStatus)
    {
        parent::__construct($filePath, $url);

        $response = $this->getResponse($method, $httpStatus);

        $this->schema = $response->content !== [] ? $this->getSchema($response->content) : null;
    }

    private function getResponse(Method $method, string $httpStatus): \cebe\openapi\spec\Response
    {
        $operation = $this->getOperation($method);

        return $operation->responses[$httpStatus]
            ??
            $operation->responses['default']
            ??
            throw new Exception('No applicable response found');
    }
}
