# Production Setup

## Caching Routes

```text
vendor/bin/membrane membrane:router:generate-routes <open-api-filepath> <cache-directory>
```

### Arguments

#### OpenAPI Filepath (Required)

This must be the **absolute** filepath to your OpenAPI filepath.  
This is required to resolve external references within your OpenAPI.

#### Cache Directory (Optional. Defaults to <membrane-package\>/cache/)

This is the directory that your routes will be cached to.  
The directory does not have to exist before running the command as long as the most deeply nested, existing directory
can be written to.

## Caching OpenAPI Processors

```text
vendor/bin/membrane membrane:membrane:generate-processors <open-api-filepath> <cache-directory>
```

This will cache a processor for each and every request and response specified in your OpenAPI.

This will also cache a CachedRequestBuilder and a CachedResponseBuilder capable of returning the relevant processor if
it exists within the cache.

### Arguments

#### OpenAPI Filepath (Required)

This must be the **absolute** filepath to your OpenAPI filepath.  
This is required to resolve external references within your OpenAPI.

#### Cache Directory (Optional. Defaults to <membrane-package\>/cache/)

This is the directory that your routes will be cached to.  
The directory does not have to exist before running the command as long as the most deeply nested, existing directory
can be written to.

### Options

#### Namespace (Defaults to Membrane\Cache)

This is the namespace all Processors within the cache will be prefixed with.

## Using The Cached Classes

### Setup Membrane to use the Cached Builders

Pass the CachedRequestBuilder and CachedResponseBuilder as constructor arguments to the `Membrane\Membrane` class.

```php
$membrane = new Membrane\Membrane(
    new <namespace>\CachedRequestBuilder(),
    new <namespace>\CachedResponseBuilder()
 );
```

Now when Membrane is asked to `process` data, it will check if the cached builders `support` it first.

### Format Incoming Data to Appropriate Specifications

To process the data it must first be in the appropriate Specification format.

#### Request

For Requests, we need to format it as a [Request Specification](builder-request.md#specification).

If the incoming request implements the Psr\Http\Message\ServerRequestInterface then we can use the static constructor
[fromPsr](builder-request.md#construct-from-psr7-requests).

If the CachedRequestBuilder does not support it, Membrane will create the processor dynamically.

#### Response

For Responses, we need to format it as a [Response Specification](builder-response.md#specification).

If the CachedResponseBuilder does not support it, Membrane will create the processor dynamically.

### Process the Specification

```php
$result = $membrane->process($data);
```

This will provide you with a [Result](result.md) object.

You can use this object to check whether the data [is valid](result.md#result) according to your OpenAPI.

## Example Docker Pipeline

```Dockerfile
FROM php:8.2-cli-alpine

COPY . /app
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

RUN composer install --no-scripts

RUN /app/vendor/bin/membrane membrane:membrane:generate-processors '/app/api/openapi.yaml' '/app/src/Membrane/Cache/' --namespace='App\\Membrane\\Cache'
RUN /app/vendor/bin/membrane membrane:router:generate-routes '/app/api/openapi.yaml' '/app/cache/routes.php'

RUN composer dump-autoload -o
```
