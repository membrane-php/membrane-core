<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Processor;

use Membrane\Processor;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Psr\Http\Message\ServerRequestInterface;

class Request implements Processor
{
    /** @param Processor[] $processors */
    public function __construct(
        private readonly string $processes,
        private readonly array $processors
    ) {
    }

    public function processes(): string
    {
        return $this->processes;
    }

    public function process(FieldName $parentFieldName, mixed $value): Result
    {
        if ($value instanceof ServerRequestInterface) {
            $value = $this->formatPsr7($value);
        } elseif (!is_array($value)) {
            return Result::invalid(
                $value,
                new MessageSet(
                    $parentFieldName,
                    new Message('Request processor expects array or PSR7 HTTP request, %s passed', [gettype($value)])
                )
            );
        }
        $value = array_merge(['path' => '', 'query' => '', 'header' => [], 'cookie' => [], 'body' => ''], $value);

        $result = Result::valid($value);
        foreach ($this->processors as $in => $processor) {
            $itemResult = $processor->process($parentFieldName, $value[$in]);
            $value[$in] = $itemResult->value;
            $result = $itemResult->merge($result);
        }

        return $result->merge(Result::noResult($value));
    }

    /** @return array<string, string|array<string, mixed>> */
    private function formatPsr7(ServerRequestInterface $request): array
    {
        $value = [];
        $value['path'] = $request->getUri()->getPath();
        $value['query'] = $request->getUri()->getQuery();
        // @TODO support header
        //$value['header'] = $this->getHeaderParams($request->getHeaders());
        // @TODO support cookie
        //$value['cookie'] = $request->getCookieParams();
        $value['body'] = (string)$request->getBody();

        return $value;
    }
}
