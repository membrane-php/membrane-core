<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec as Cebe;
use Membrane\Builder\Specification;
use Membrane\OpenAPI\ExtractPathParameters\ExtractsPathParameters;
use Membrane\OpenAPI\Method;

interface RequestSpec extends Specification
{
    public function getOperationId(): string;

    public function getMethod(): Method;

    /** @return array<string, Cebe\Parameter> */
    public function getParameters(): array;

    public function getPathItem(): Cebe\PathItem;

    public function getPathParameterExtractor(): ExtractsPathParameters;

    public function getRequestBody(): ?Cebe\Schema;
}
