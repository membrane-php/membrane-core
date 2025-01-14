<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\Builder\Specification;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
use Psr\Http\Message\ServerRequestInterface;

class Request implements Specification
{
    public readonly string $absoluteFilePath;

    public function __construct(
        string $absoluteFilePath,
        public readonly string $url,
        public readonly Method $method
    ) {
        $this->absoluteFilePath = realpath($absoluteFilePath) ?: $absoluteFilePath;
    }

    public static function fromPsr7(string $apiPath, ServerRequestInterface $request): self
    {
        $method = Method::tryFrom(strtolower($request->getMethod()))
            ??
            throw CannotProcessSpecification::methodNotSupported($request->getMethod());

        return new self($apiPath, $request->getUri()->getPath(), $method);
    }
}
