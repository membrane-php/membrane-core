# Building From An OpenAPI Request

## Specification

```php
\Membrane\OpenAPI\Specification\Request($filePath, $url, $method)
```

This Specification is an OpenAPI Request from an [OpenAPI document](https://github.com/OAI/OpenAPI-Specification).

| Parameter   | Type   | Notes                                                                   |
|-------------|--------|-------------------------------------------------------------------------|
| $filePath   | string | The **absolute** file path of the OpenAPI schema the request belongs to |
| $url        | string | The url of the path the request belongs to                              |
| $method     | Method | The method of the request                                               |

Both Json and Yaml schemas are supported.  
Only application/json content is supported.

**Please Note:** Membrane requires all Schema objects to have an explicitly stated `'type'`.

## Example

Referencing
the [OpenAPI petstore-expanded.json](https://github.com/OAI/OpenAPI-Specification/blob/main/examples/v3.0/petstore-expanded.json):   
To validate HTTP requests to visit `'http://petstore.swagger.io/api/pets'` using the `'get'` operation then this is the
schema it should follow:

```json
{
  "/pets": {
    "get": {
      "description": "Returns all pets from the system that the user has access to. \n",
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
      ]
    }
  }
}
```

Our Specification might look like this:

```php
use Membrane\OpenAPI\Method;use Membrane\OpenAPI\Specification\Request;

$specification = new Request(__DIR__ . './api/OpenAPI.json', 'http://petstore.swagger.io/api/pets', Method::GET);
```

Notice an absolute file path was used, relative paths will not work in Request Specifications.

With our specification we can build a processor to validate incoming server requests,
the server requests must implement the Psr/Http/Message/ServerRequestInterface.

For these examples the server requests will be mocked using the "guzzle/http-message" library:

```php
use GuzzleHttp\Psr7\ServerRequest; 

$dataSets = [
    'dataSet A' => new ServerRequest('get', 'http://petstore.swagger.io/v1/pets')
    'dataSet B' => new ServerRequest('get', 'http://petstore.swagger.io/api/pets?limit=5&tags[]=cat&tags[]=tabby'),
    'dataSet C' => new ServerRequest('get', 'http://petstore.swagger.io/api/pets?limit=five'),
];

$membrane = new Membrane();
foreach($dataSets as $key => $dataSet) {
    $result = $membrane->process($dataSet, $specification);
    
    if ($result->isValid()) {
        echo $key, " is valid. \n", var_export($value, true), "\n";
    } else {
        echo $key, " is invalid. \n";
        foreach($result->messageSets as $messageSet) {
            foreach ($messageSet->messages as $message) {
                echo $message->rendered();
            }
        }
    }
    echo "\n";
}
```

Outputs

```text
DataSet A is valid.
[
    'path' => [],
    'query' => [],
    'header' => [],
    'cookie' => [],
    'body' => '',
]

DataSet B is valid.
[
    'path' => [],
    'query' => ['limit' => 5, 'tags' => ['cat', 'tabby']],
    'header' => [],
    'cookie' => [],
    'body' => '',
]

DataSet C is invalid.
ToInt filter only accepts numeric strings
```

In this example DataSet C has been invalidated based on the query parameter 'limit'.  
The OpenAPI specification stated that 'limit' should be an integer, so Membrane will check this for you.
