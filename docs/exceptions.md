# Exceptions

## General Use

### Invalid Processor Arguments

This exception occurs when a [Processor](processors.md) receives invalid arguments.  
The Builder classes know how each processor works and will never throw this exception,  
this exception is thrown through developer error when attempting to use processors manually.  
This may be due to one of the following reasons:

* Too many of one type of processor have been passed
* The processor being used serves no purpose

## Attribute Specific

These exceptions may occur when dealing with the [Attribute Builder](builder-attributes.md).

### Cannot Process Property

This exception occurs if Membrane cannot process attributes on the class provided.  
This may be due to one of the following reasons:

* Class is missing required attributes
* Class contains attributes/properties that are currently unsupported

## OpenAPI Specific

These exceptions may occur when dealing with the OpenAPI specific classes,  
including (but not limited to) [Request](builder-request.md) and [Response](builder-response.md) Builders.

### Cannot Read OpenAPI

This exception occurs when the file specified cannot be read as an OpenAPI document.  
This may be due to one of the following reasons:

* The file cannot be found on the path provided
* The file is not following the OpenAPI specification

### Cannot Process OpenAPI

This exception occurs when the OpenAPI has been read and parsed as OpenAPI
but Membrane cannot process it further due to developer error.  
This may be due to one of the following reasons:

* Your OpenAPI spec contains features currently unsupported by Membrane
* Your OpenAPI spec does not contain the data you're trying to process.

### Cannot Process Request

This exception occurs when the OpenAPI has been read and parsed as OpenAPI
but Membrane cannot process it further due to user error.  
This may be due to one of the following reasons:

* Your request contains features currently unsupported by Membrane
* Your request does not match anything found in your OpenAPI spec.
