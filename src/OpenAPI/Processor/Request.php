<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Processor;

use Membrane\Filter\String\JsonDecode;
use Membrane\OpenAPI\ContentType;
use Membrane\OpenAPIReader\Method;
use Membrane\Processor;
use Membrane\Processor\Field;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

class Request implements Processor
{
    /** @param Processor[] $processors */
    public function __construct(
        private readonly string $processes,
        private readonly string $operationId,
        private readonly Method $method,
        private readonly array $processors
    ) {
    }

    public function __toString()
    {
        if ($this->processors === []) {
            return 'Parse PSR-7 request';
        }

        return "Parse PSR-7 request:\n\t" .
            implode("\n\t", array_map(fn($p) => preg_replace("#\n#m", "\n\t", (string)$p), $this->processors));
    }

    public function __toPHP(): string
    {
        $processors = [];
        foreach ($this->processors as $key => $processor) {
            $processors[] = '"' . $key . '" => ' . $processor->__toPHP();
        }

        return sprintf(
            'new %s("%s", "%s", %s::%s, [%s])',
            self::class,
            $this->processes(),
            $this->operationId,
            Method::class,
            $this->method->name,
            implode(', ', $processors)
        );
    }

    public function processes(): string
    {
        return $this->processes;
    }

    public function process(FieldName $parentFieldName, mixed $value): Result
    {
        if ($value instanceof ServerRequestInterface) {
            $value = $this->formatPsr7($parentFieldName, $value);
            if (!$value->isValid()) {
                return $value;
            }

            $value = $value->value;
        }

        if (!is_array($value)) {
            return Result::invalid(
                $value,
                new MessageSet(
                    $parentFieldName,
                    new Message(
                        'Request processor expects array or PSR7 HTTP request, %s passed',
                        [gettype($value)]
                    )
                )
            );
        }

        $request = ['method' => $this->method->value, 'operationId' => $this->operationId];
        $value = array_merge(
            ['request' => $request, 'path' => '', 'query' => '', 'header' => [], 'cookie' => [], 'body' => ''],
            $value
        );

        $result = Result::valid($value);
        foreach ($this->processors as $in => $processor) {
            $itemResult = $processor->process($parentFieldName, $value[$in]);
            $value[$in] = $itemResult->value;
            $result = $itemResult->merge($result);
        }

        return $result->merge(Result::noResult($value));
    }

    private function formatPsr7(FieldName $parentFieldName, ServerRequestInterface $request): Result
    {
        $value = ['header' => [], 'cookie' => []];
        $value['path'] = $request->getUri()->getPath();
        $value['query'] = $request->getUri()->getQuery();
        // @TODO support header
        //$value['header'] = $this->getHeaderParams($request->getHeaders());
        // @TODO support cookie
        //$value['cookie'] = $request->getCookieParams();

        //There should only be one content type header; PHP ignores additional header values
        $contentType = ContentType::fromContentTypeHeader(current($request->getHeader('Content-Type')));

        // If content type is JSON, parse & return otherwise use the already parsed PSR7 body.
        if ($contentType === ContentType::Json) {
            $body = (string)$request->getBody();

            if ($body === '') {
                $value['body'] = '';
                return Result::noResult($value);
            }

            $jsonDecode = new Field('', new JsonDecode());
            $result = $jsonDecode->process($parentFieldName, $body);
            $value['body'] = $result->value;

            return new Result(
                $value,
                $result->result,
                ...$result->messageSets
            );
        }

        // If content type is unmatched, return raw body. This is /probably/ an error, but we can't do much better
        if ($contentType === ContentType::Unmatched) {
            $value['body'] = (string)$request->getBody();
            return Result::noResult($value);
        }

        $value['body'] = (array)$request->getParsedBody();
        if ($contentType === ContentType::Multipart) {
            $value['body'] = array_merge(
                $value['body'],
                $this->convertUploadedFilesToStrings($request->getUploadedFiles())
            );
        }

        return Result::noResult($value);
    }

    /**
     * @param array<string, mixed> $uploadedFiles
     * @return array<string, mixed>
     */
    public function convertUploadedFilesToStrings(array $uploadedFiles): array
    {
        $result = [];
        foreach ($uploadedFiles as $name => $file) {
            if (is_array($file)) {
                $result[$name] = $this->convertUploadedFilesToStrings($file);
            } elseif ($file instanceof UploadedFileInterface) {
                $result[$name] = (string)$file->getStream();
            }
        }

        return $result;
    }
}
