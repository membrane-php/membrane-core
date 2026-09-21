<?php

declare(strict_types=1);

namespace Membrane\Tests\Fixtures\OpenAPI;

use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid;

final readonly class Petstore
{
    public const string FILENAME = __DIR__ . '/docs/petstore.yaml';

    public const string JSON = <<<'JSON'
        {
          "openapi": "3.0.0",
          "info": {
            "version": "1.0.0",
            "title": "Swagger Petstore",
            "license": {
              "name": "MIT"
            }
          },
          "servers": [
            {
              "url": "http://petstore.swagger.io/v1"
            }
          ],
          "paths": {
            "/pets": {
              "get": {
                "summary": "List all pets",
                "operationId": "listPets",
                "tags": ["pets"],
                "parameters": [
                  {
                    "name": "limit",
                    "in": "query",
                    "description": "How many items to return at one time (max 100)",
                    "required": false,
                    "schema": {
                      "type": "integer",
                      "maximum": 100,
                      "format": "int32"
                    }
                  }
                ],
                "responses": {
                  "200": {
                    "description": "A paged array of pets",
                    "headers": {
                      "x-next": {
                        "description": "A link to the next page of responses",
                        "schema": {
                          "type": "string"
                        }
                      }
                    },
                    "content": {
                      "application/json": {
                        "schema": {
                          "$ref": "#/components/schemas/Pets"
                        }
                      }
                    }
                  },
                  "default": {
                    "description": "unexpected error",
                    "content": {
                      "application/json": {
                        "schema": {
                          "$ref": "#/components/schemas/Error"
                        }
                      }
                    }
                  }
                }
              },
              "post": {
                "summary": "Create a pet",
                "operationId": "createPets",
                "tags": ["pets"],
                "requestBody": {
                  "content": {
                    "application/json": {
                      "schema": {
                        "$ref": "#/components/schemas/Pet"
                      }
                    }
                  },
                  "required": true
                },
                "responses": {
                  "201": {
                    "description": "Null response"
                  },
                  "default": {
                    "description": "unexpected error",
                    "content": {
                      "application/json": {
                        "schema": {
                          "$ref": "#/components/schemas/Error"
                        }
                      }
                    }
                  }
                }
              }
            },
            "/pets/{petId}": {
              "get": {
                "summary": "Info for a specific pet",
                "operationId": "showPetById",
                "tags": ["pets"],
                "parameters": [
                  {
                    "name": "petId",
                    "in": "path",
                    "required": true,
                    "description": "The id of the pet to retrieve",
                    "schema": {
                      "type": "string"
                    }
                  }
                ],
                "responses": {
                  "200": {
                    "description": "Expected response to a valid request",
                    "content": {
                      "application/json": {
                        "schema": {
                          "$ref": "#/components/schemas/Pet"
                        }
                      }
                    }
                  },
                  "default": {
                    "description": "unexpected error",
                    "content": {
                      "application/json": {
                        "schema": {
                          "$ref": "#/components/schemas/Error"
                        }
                      }
                    }
                  }
                }
              }
            }
          },
          "components": {
            "schemas": {
              "Pet": {
                "type": "object",
                "required": ["id", "name"],
                "properties": {
                  "id": {
                    "type": "integer",
                    "format": "int64"
                  },
                  "name": {
                    "type": "string"
                  },
                  "tag": {
                    "type": "string"
                  }
                }
              },
              "Pets": {
                "type": "array",
                "maxItems": 100,
                "items": {
                  "$ref": "#/components/schemas/Pet"
                }
              },
              "Error": {
                "type": "object",
                "required": ["code", "message"],
                "properties": {
                  "code": {
                    "type": "integer",
                    "format": "int32"
                  },
                  "message": {
                    "type": "string"
                  }
                }
              }
            }
          }
        }
        JSON;

    public const string YAML = <<<'YAML'
        openapi: "3.0.0"
        info:
          version: 1.0.0
          title: Swagger Petstore
          license:
            name: MIT
        servers:
          - url: http://petstore.swagger.io/v1
        paths:
          /pets:
            get:
              summary: List all pets
              operationId: listPets
              tags:
                - pets
              parameters:
                - name: limit
                  in: query
                  description: How many items to return at one time (max 100)
                  required: false
                  schema:
                    type: integer
                    maximum: 100
                    format: int32
              responses:
                '200':
                  description: A paged array of pets
                  headers:
                    x-next:
                      description: A link to the next page of responses
                      schema:
                        type: string
                  content:
                    application/json:
                      schema:
                        $ref: "#/components/schemas/Pets"
                default:
                  description: unexpected error
                  content:
                    application/json:
                      schema:
                        $ref: "#/components/schemas/Error"
            post:
              summary: Create a pet
              operationId: createPets
              tags:
                - pets
              requestBody:
                content:
                  application/json:
                    schema:
                      $ref: '#/components/schemas/Pet'
                required: true
              responses:
                '201':
                  description: Null response
                default:
                  description: unexpected error
                  content:
                    application/json:
                      schema:
                        $ref: "#/components/schemas/Error"
          /pets/{petId}:
            get:
              summary: Info for a specific pet
              operationId: showPetById
              tags:
                - pets
              parameters:
                - name: petId
                  in: path
                  required: true
                  description: The id of the pet to retrieve
                  schema:
                    type: string
              responses:
                '200':
                  description: Expected response to a valid request
                  content:
                    application/json:
                      schema:
                        $ref: "#/components/schemas/Pet"
                default:
                  description: unexpected error
                  content:
                    application/json:
                      schema:
                        $ref: "#/components/schemas/Error"
        components:
          schemas:
            Pet:
              type: object
              required:
                - id
                - name
              properties:
                id:
                  type: integer
                  format: int64
                name:
                  type: string
                tag:
                  type: string
            Pets:
              type: array
              maxItems: 100
              items:
                $ref: "#/components/schemas/Pet"
            Error:
              type: object
              required:
                - code
                - message
              properties:
                code:
                  type: integer
                  format: int32
                message:
                  type: string
        YAML;

    public static function validated(): Valid\V30\OpenAPI
    {
        return Valid\V30\OpenAPI::fromPartial(
            new Partial\OpenAPI(
                openAPI: '3.0.0',
                title: 'Swagger Petstore',
                version: '1.0.0',
                servers: self::servers(),
                paths: [self::pathPets(), self::pathPetsPetId()],
            )
        );
    }

    /**
     * @return non-empty-list<Partial\Server>
     */
    private static function servers(): array
    {
        return [new Partial\Server(url: 'http://petstore.swagger.io/v1')];
    }

    /**
     * To use this as a standalone, you MUST fill the $servers,
     * since it will not have the root servers to fall back to.
     * You can use PetstoreExpanded::servers() to achieve this.
     */
    private static function pathPets(): Partial\PathItem
    {
        return new Partial\PathItem(
            path: '/pets',
            servers: [],
            parameters: [],
            get: new Partial\Operation(
                operationId: 'listPets',
                servers: [],
                parameters: [
                    new Partial\Parameter(
                        name: 'limit',
                        in: 'query',
                        schema: new Partial\Schema(
                            type: 'integer',
                            maximum: 100,
                            format: 'int32',
                        ),
                    )
                ],
                responses: [
                    '200' => new Partial\Response(
                        description: 'A paged array of pets',
                        headers: [
                            'x-next' => new Partial\Header(
                                schema: new Partial\Schema('string'),
                            )
                        ],
                        content: [
                            new Partial\MediaType(
                                contentType: 'application/json',
                                schema: new Partial\Schema(
                                    type: 'array',
                                    maxItems: 100,
                                    items: new Partial\Schema(
                                        type: 'object',
                                        required: ['id', 'name'],
                                        properties: [
                                            'id' => new Partial\Schema(
                                                type: 'integer',
                                                format: 'int64',
                                            ),
                                            'name' => new Partial\Schema(
                                                type: 'string',
                                            ),
                                            'tag' => new Partial\Schema(
                                                type: 'string',
                                            ),
                                        ],
                                    ),
                                )
                            )
                        ],
                    ),
                    'default' => self::responseError(),
                ]
            )
        );
    }

    /**
     * To use this as a standalone, you MUST fill the $servers,
     * since it will not have the root servers to fall back to.
     * You can use PetstoreExpanded::servers() to achieve this.
     */
    private static function pathPetsPetId(): Partial\PathItem
    {
        return new Partial\PathItem(
            path: '/pets/{petId}',
            servers: [],
            parameters: [
                new Partial\Parameter(
                    name: 'petId',
                    in: 'path',
                    required: true,
                    schema: new Partial\Schema(type: 'string'),
                )
            ],
            get: new Partial\Operation(
                operationId: 'showPetById',
                responses: [
                    '200' => new Partial\Response(
                        description: 'Expected response to a valid request',
                        content: [
                            new Partial\MediaType(
                                contentType: 'application/json',
                                schema: new Partial\Schema(
                                    type: 'object',
                                    required: ['id', 'name'],
                                    properties: [
                                        'id' => new Partial\Schema(
                                            type: 'integer',
                                            format: 'int64',
                                        ),
                                        'name' => new Partial\Schema(
                                            type: 'string',
                                        ),
                                        'tag' => new Partial\Schema(
                                            type: 'string',
                                        ),
                                    ],
                                ),
                            )
                        ]
                    ),
                    'default' => self::responseError(),
                ],
            )
        );
    }

    private static function responseError(): Partial\Response
    {
        return new Partial\Response(
            description: 'unexpected error',
            content: [
                new Partial\MediaType(
                    contentType: 'application/json',
                    schema: self::schemaError(),
                ),
            ]
        );
    }

    private static function schemaError(): Partial\Schema
    {
        return new Partial\Schema(
            type: 'object',
            required: ['code', 'message'],
            properties: [
                'code' => new Partial\Schema(
                    type: 'integer',
                    format: 'int32',
                ),
                'message' => new Partial\Schema(
                    type: 'string',
                ),
            ]
        );
    }
}
