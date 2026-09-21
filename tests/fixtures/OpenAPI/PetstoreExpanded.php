<?php

declare(strict_types=1);

namespace Membrane\Tests\Fixtures\OpenAPI;

use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid;

final readonly class PetstoreExpanded
{
    public const string FILENAME = __DIR__ . '/docs/petstore-expanded.json';

    public const string JSON = <<<'JSON'
        {
          "openapi": "3.0.0",
          "info": {
            "version": "1.0.0",
            "title": "Swagger Petstore",
            "description": "A sample API that uses a petstore as an example to demonstrate features in the OpenAPI 3.0 specification",
            "termsOfService": "http://swagger.io/terms/",
            "contact": {
              "name": "Swagger API Team",
              "email": "apiteam@swagger.io",
              "url": "http://swagger.io"
            },
            "license": {
              "name": "Apache 2.0",
              "url": "https://www.apache.org/licenses/LICENSE-2.0.html"
            }
          },
          "servers": [
            {
              "url": "https://petstore.swagger.io/v2"
            }
          ],
          "paths": {
            "/pets": {
              "get": {
                "description": "Returns all pets from the system that the user has access to\nNam sed condimentum est. Maecenas tempor sagittis sapien, nec rhoncus sem sagittis sit amet. Aenean at gravida augue, ac iaculis sem. Curabitur odio lorem, ornare eget elementum nec, cursus id lectus. Duis mi turpis, pulvinar ac eros ac, tincidunt varius justo. In hac habitasse platea dictumst. Integer at adipiscing ante, a sagittis ligula. Aenean pharetra tempor ante molestie imperdiet. Vivamus id aliquam diam. Cras quis velit non tortor eleifend sagittis. Praesent at enim pharetra urna volutpat venenatis eget eget mauris. In eleifend fermentum facilisis. Praesent enim enim, gravida ac sodales sed, placerat id erat. Suspendisse lacus dolor, consectetur non augue vel, vehicula interdum libero. Morbi euismod sagittis libero sed lacinia.\n\nSed tempus felis lobortis leo pulvinar rutrum. Nam mattis velit nisl, eu condimentum ligula luctus nec. Phasellus semper velit eget aliquet faucibus. In a mattis elit. Phasellus vel urna viverra, condimentum lorem id, rhoncus nibh. Ut pellentesque posuere elementum. Sed a varius odio. Morbi rhoncus ligula libero, vel eleifend nunc tristique vitae. Fusce et sem dui. Aenean nec scelerisque tortor. Fusce malesuada accumsan magna vel tempus. Quisque mollis felis eu dolor tristique, sit amet auctor felis gravida. Sed libero lorem, molestie sed nisl in, accumsan tempor nisi. Fusce sollicitudin massa ut lacinia mattis. Sed vel eleifend lorem. Pellentesque vitae felis pretium, pulvinar elit eu, euismod sapien.\n",
                "operationId": "findPets",
                "parameters": [
                  {
                    "name": "tags",
                    "in": "query",
                    "description": "tags to filter by",
                    "required": false,
                    "style": "form",
                    "schema": {
                      "type": "array",
                      "items": {
                        "type": "string"
                      }
                    }
                  },
                  {
                    "name": "limit",
                    "in": "query",
                    "description": "maximum number of results to return",
                    "required": false,
                    "schema": {
                      "type": "integer",
                      "format": "int32"
                    }
                  }
                ],
                "responses": {
                  "200": {
                    "description": "pet response",
                    "content": {
                      "application/json": {
                        "schema": {
                          "type": "array",
                          "items": {
                            "$ref": "#/components/schemas/Pet"
                          }
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
                "description": "Creates a new pet in the store. Duplicates are allowed",
                "operationId": "addPet",
                "requestBody": {
                  "description": "Pet to add to the store",
                  "required": true,
                  "content": {
                    "application/json": {
                      "schema": {
                        "$ref": "#/components/schemas/NewPet"
                      }
                    }
                  }
                },
                "responses": {
                  "200": {
                    "description": "pet response",
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
            },
            "/pets/{id}": {
              "get": {
                "description": "Returns a user based on a single ID, if the user does not have access to the pet",
                "operationId": "find pet by id",
                "parameters": [
                  {
                    "name": "id",
                    "in": "path",
                    "description": "ID of pet to fetch",
                    "required": true,
                    "schema": {
                      "type": "integer",
                      "format": "int64"
                    }
                  }
                ],
                "responses": {
                  "200": {
                    "description": "pet response",
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
              },
              "delete": {
                "description": "deletes a single pet based on the ID supplied",
                "operationId": "deletePet",
                "parameters": [
                  {
                    "name": "id",
                    "in": "path",
                    "description": "ID of pet to delete",
                    "required": true,
                    "schema": {
                      "type": "integer",
                      "format": "int64"
                    }
                  }
                ],
                "responses": {
                  "204": {
                    "description": "pet deleted"
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
                "allOf": [
                  {
                    "$ref": "#/components/schemas/NewPet"
                  },
                  {
                    "type": "object",
                    "required": [
                      "id"
                    ],
                    "properties": {
                      "id": {
                        "type": "integer",
                        "format": "int64"
                      }
                    }
                  }
                ]
              },
              "NewPet": {
                "type": "object",
                "required": [
                  "name"
                ],
                "properties": {
                  "name": {
                    "type": "string"
                  },
                  "tag": {
                    "type": "string"
                  }
                }
              },
              "Error": {
                "type": "object",
                "required": [
                  "code",
                  "message"
                ],
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
        openapi: 3.0.0
        info:
          version: 1.0.0
          title: Swagger Petstore
          description: A sample API that uses a petstore as an example to demonstrate features in the OpenAPI 3.0 specification
          termsOfService: http://swagger.io/terms/
          contact:
            name: Swagger API Team
            email: apiteam@swagger.io
            url: http://swagger.io
          license:
            name: Apache 2.0
            url: https://www.apache.org/licenses/LICENSE-2.0.html
        servers:
          - url: https://petstore.swagger.io/v2
        paths:
          /pets:
            get:
              description: |
                Returns all pets from the system that the user has access to
                Nam sed condimentum est. Maecenas tempor sagittis sapien, nec rhoncus sem sagittis sit amet. Aenean at gravida augue, ac iaculis sem. Curabitur odio lorem, ornare eget elementum nec, cursus id lectus. Duis mi turpis, pulvinar ac eros ac, tincidunt varius justo. In hac habitasse platea dictumst. Integer at adipiscing ante, a sagittis ligula. Aenean pharetra tempor ante molestie imperdiet. Vivamus id aliquam diam. Cras quis velit non tortor eleifend sagittis. Praesent at enim pharetra urna volutpat venenatis eget eget mauris. In eleifend fermentum facilisis. Praesent enim enim, gravida ac sodales sed, placerat id erat. Suspendisse lacus dolor, consectetur non augue vel, vehicula interdum libero. Morbi euismod sagittis libero sed lacinia.

                Sed tempus felis lobortis leo pulvinar rutrum. Nam mattis velit nisl, eu condimentum ligula luctus nec. Phasellus semper velit eget aliquet faucibus. In a mattis elit. Phasellus vel urna viverra, condimentum lorem id, rhoncus nibh. Ut pellentesque posuere elementum. Sed a varius odio. Morbi rhoncus ligula libero, vel eleifend nunc tristique vitae. Fusce et sem dui. Aenean nec scelerisque tortor. Fusce malesuada accumsan magna vel tempus. Quisque mollis felis eu dolor tristique, sit amet auctor felis gravida. Sed libero lorem, molestie sed nisl in, accumsan tempor nisi. Fusce sollicitudin massa ut lacinia mattis. Sed vel eleifend lorem. Pellentesque vitae felis pretium, pulvinar elit eu, euismod sapien.
              operationId: findPets
              parameters:
                - name: tags
                  in: query
                  description: tags to filter by
                  required: false
                  style: form
                  schema:
                    type: array
                    items:
                      type: string
                - name: limit
                  in: query
                  description: maximum number of results to return
                  required: false
                  schema:
                    type: integer
                    format: int32
              responses:
                '200':
                  description: pet response
                  content:
                    application/json:
                      schema:
                        type: array
                        items:
                          $ref: '#/components/schemas/Pet'
                default:
                  description: unexpected error
                  content:
                    application/json:
                      schema:
                        $ref: '#/components/schemas/Error'
            post:
              description: Creates a new pet in the store. Duplicates are allowed
              operationId: addPet
              requestBody:
                description: Pet to add to the store
                required: true
                content:
                  application/json:
                    schema:
                      $ref: '#/components/schemas/NewPet'
              responses:
                '200':
                  description: pet response
                  content:
                    application/json:
                      schema:
                        $ref: '#/components/schemas/Pet'
                default:
                  description: unexpected error
                  content:
                    application/json:
                      schema:
                        $ref: '#/components/schemas/Error'
          /pets/{id}:
            get:
              description: Returns a user based on a single ID, if the user does not have access to the pet
              operationId: find pet by id
              parameters:
                - name: id
                  in: path
                  description: ID of pet to fetch
                  required: true
                  schema:
                    type: integer
                    format: int64
              responses:
                '200':
                  description: pet response
                  content:
                    application/json:
                      schema:
                        $ref: '#/components/schemas/Pet'
                default:
                  description: unexpected error
                  content:
                    application/json:
                      schema:
                        $ref: '#/components/schemas/Error'
            delete:
              description: deletes a single pet based on the ID supplied
              operationId: deletePet
              parameters:
                - name: id
                  in: path
                  description: ID of pet to delete
                  required: true
                  schema:
                    type: integer
                    format: int64
              responses:
                '204':
                  description: pet deleted
                default:
                  description: unexpected error
                  content:
                    application/json:
                      schema:
                        $ref: '#/components/schemas/Error'
        components:
          schemas:
            Pet:
              allOf:
                - $ref: '#/components/schemas/NewPet'
                - type: object
                  required:
                    - id
                  properties:
                    id:
                      type: integer
                      format: int64
            NewPet:
              type: object
              required:
                - name
              properties:
                name:
                  type: string
                tag:
                  type: string
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
        return Valid\V30\OpenAPI::fromPartial(new Partial\OpenAPI(
            openAPI: '3.0.0',
            title: 'Swagger Petstore',
            version: '1.0.0',
            servers: self::servers(),
            paths: [self::pathPets(), self::pathPetsId()],
        ));
    }

    /** @return non-empty-list<Partial\Server> */
    private static function servers(): array
    {
        return [new Partial\Server(url: 'https://petstore.swagger.io/v2')];
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
                operationId: 'findPets',
                servers: [],
                parameters: [
                    new Partial\Parameter(
                        name: 'tags',
                        in: 'query',
                        schema: new Partial\Schema(
                            type: 'array',
                            items: new Partial\Schema(
                                type: 'string',
                            ),
                        ),
                    ),
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
                        content: [
                            new Partial\MediaType(
                                contentType: 'application/json',
                                schema: new Partial\Schema(
                                    type: 'array',
                                    items: self::schemaPet(),
                                ),
                            ),
                        ],
                    ),
                    'default' => self::responseError(),
                ]
            ),
            post: new Partial\Operation(
                operationId: 'addPet',
                requestBody: new Partial\RequestBody(
                    description: 'Pet to add to the store',
                    content: [
                        new Partial\MediaType(
                            contentType: 'application/json',
                            schema: self::schemaNewPet(),
                        )
                    ],
                    required: true,
                ),
                responses: [
                    '200' => new Partial\Response(
                        description: 'pet response',
                        content: [
                            new Partial\MediaType(
                                contentType: 'application/json',
                                schema: self::schemaPet(),
                            ),
                        ],
                    ),
                    'default' => self::responseError(),
                ],
            )
        );
    }

    /**
     * To use this as a standalone, you MUST fill the $servers,
     * since it will not have the root servers to fall back to.
     * You can use PetstoreExpanded::servers() to achieve this.
     */
    private static function pathPetsId(): Partial\PathItem
    {
        return new Partial\PathItem(
            path: '/pets/{id}',
            servers: [],
            parameters: [
                new Partial\Parameter(
                    name: 'petId',
                    in: 'path',
                    required: true,
                    schema: new Partial\Schema(
                        type: 'integer',
                        format: 'int64',
                    ),
                )
            ],
            get: new Partial\Operation(
                operationId: 'find pet by id',
                responses: [
                    '200' => new Partial\Response(
                        description: 'Expected response to a valid request',
                        content: [new Partial\MediaType(
                            contentType: 'application/json',
                            schema: self::schemaPet(),
                        )]
                    ),
                    'default' => self::responseError(),
                ],
            ),
            delete: new Partial\Operation(
                operationId: 'deletePet',
                responses: [
                    '204' => new Partial\Response(
                        description: 'pet deleted',
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

    private static function schemaNewPet(): Partial\Schema
    {
        return new Partial\Schema(
            type: 'object',
            required: ['name'],
            properties: [
                'name' => new Partial\Schema(
                    type: 'string',
                ),
            ],
        );
    }

    private static function schemaPet(): Partial\Schema
    {
        return new Partial\Schema(
            allOf: [
                new Partial\Schema(
                    type: 'object',
                    required: ['id'],
                    properties: [
                        'id' => new Partial\Schema(
                            type: 'integer',
                            format: 'int64',
                        ),
                    ],
                ),
                self::schemaNewPet(),
            ],
        );
    }
}
