<?php

declare(strict_types=1);

namespace Membrane\Tests\Fixtures\OpenAPI;

use Membrane\OpenAPIReader\FileFormat;
use Membrane\OpenAPIReader\ValueObject\Valid\{Enum\Method, V30, V31};

final class ProvidesUnsupportedContent extends Provider
{
    public static function pathItemMediaType(
        string $path = '/path',
        Method $method = Method::GET,
        string $parameter = 'pdf',
        string $mediaType = 'application/pdf',
    ): V30\PathItem {
        $api = <<<YAML
            openapi: "3.0.0"
            info:
              title: OpenAPI with Unsupported Content
              version: 1.0.0
            paths:
              $path:
                $method->value:
                  operationId: testcase
                  parameters:
                    - name: $parameter
                      in: header
                      content:
                        $mediaType:
                          schema:
                            type: integer
                  responses:
                    200:
                      description: Success
            YAML;

        return self::reader()
            ->readFromString($api, FileFormat::Yaml)
            ->paths[$path];
    }
}
