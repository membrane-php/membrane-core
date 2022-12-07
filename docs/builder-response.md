# Building From An OpenAPI Response

## Specification

```php
APIResponse($filePath, $url, $method, $httpStatus)
```

This Specification is an OpenAPI Response from an [OpenAPI document](https://github.com/OAI/OpenAPI-Specification).

| Parameter   | Type   | Notes                                                                    |
|-------------|--------|--------------------------------------------------------------------------|
| $filePath   | string | The **absolute** file path of the OpenAPI schema the response belongs to |
| $url        | string | The url of the path the response belongs to                              |
| $method     | Method | The method the response belongs to                                       |
| $httpStatus | string | The http status code of the response                                     |

Both Json and Yaml schemas are supported.  
Only application/json content is supported.

**Please Note:** Membrane requires Schemas to have an explicitly stated `'type'`.

## Example

Referencing
the [OpenAPI PetStore.yaml](https://github.com/OAI/OpenAPI-Specification/blob/main/examples/v3.0/petstore.yaml):  
If our user is visiting the `/pets` path with the `get` method, if they get a successful response this is the schema it
should follow.

```yaml
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
                type: array
                items:
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
```

For the sake of this example, the references have already been resolved.

Our Specification might look like this.

```php
$specification = new APIResponse(__DIR__ . './api/OpenAPI.yaml', '/pets', Method::GET, '200');
```

Notice an absolute file path was used, relative paths will not work in Response Specifications.

We're now ready to validate any data coming in against our response schema like so:

```php
$dataSets = [
    'dataSet A' => [
        ['name' => 'Blink', 'id' => 1],
        ['name' => 'Harley', 'id' => 2]
    ],
    'dataSet B' => [
        ['name' => 'Blink'],
        ['id' => 2]
    ],
    'dataSet C' => [
        'Blink',
        5
    ],
];

$membrane = new Membrane();
foreach($dataSets as $key => $dataSet) {
    $result = $membrane->process($dataSet, $specification);
    echo $key;
    if ($result->isValid()) {
        echo ' is valid';
    } else {
        echo ' is invalid';
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
dataSet A is valid
dataSet B is invalid
id is a required field
name is a required field
dataSet C is invalid
IsArray validator expects array value, string passed instead
IsArray validator expects array value, integer passed instead
```

In this example DataSet B failed because:

- The first item in the array `['name' => 'Blink']` did not contain an id.
- The second item in the array `['id' => 2]` did not contain a name.

DataSet C failed because the items were meant to be arrays but:

- The first item was a string
- The second item was an integer

Notice in both cases that Membrane returned multiple messages.  
Membrane will provide messages for all reasons for invalidation.
