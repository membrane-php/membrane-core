<?php

declare(strict_types=1);

namespace Membrane\OpenAPI;

enum ContentType
{
    case Json;
    case FormData;
    case Multipart;
    case Unmatched;

    public static function fromContentTypeHeader(string|false $headerValue): self
    {
        if ($headerValue === false) {
            return self::Unmatched;
        }

        if ($headerValue === 'application/x-www-form-urlencoded') {
            return self::FormData;
        }

        // allows for application/problem+json and other similar options
        if (preg_match('#^application/.*json$#', $headerValue) === 1) {
            return self::Json;
        }

        // multipart is typically multipart/form-data but can also have other values
        if (preg_match('#^multipart/.*$#', $headerValue)) {
            return self::Multipart;
        }

        return self::Unmatched;
    }
}
