# Getting Started

Membrane requirements:

```
"php": ^8.1.0,
"cebe/php-openapi": ^1.7.0
```

## Installation

To start; require Membrane in [Composer](https://getcomposer.org/)

```text
composer require membrane-php/membrane-core
```

## The Basics

### What Goes In

Membrane needs a Specification to validate against.   
These are the supported types of Specification:

* [Class With Attributes](builder-attributes.md#specification)
* [OpenAPI Request](builder-request.md#specification)
* [OpenAPI Response](builder-response.md#specification)

With your Specification(s) at hand, Membrane is ready to process your data:

```php
$membrane = new Membrane();

$membrane->process($data, ...$specifications);
```

### What Comes Out

All data processed by Membrane will return as a [Result](result.md) object. Your data will be contained in your
Result's `$value` property.

To check if the data is valid your Result has the following method:

```php
isValid(): bool
```

If your data is valid, that's great, your application can now make use of the data with confidence.

What if your data is invalid?  
Your Result object will create a new [MessageSet](result.md#message-set) for each validation failure, The MessageSet(s)
will contain information
aiming to clarify the reason the data is considered invalid.

To get a nested list of messages in an HTML format you can use the following code snippet:

```php
echo '<ul>';
foreach($result->messageSets as $messageSet) {
    echo '<li><ul> <b>' . $messageSet->fieldName->getStringRepresentation() . '</b>';
    foreach($messageSet->messages as $message) {
        echo '<li>' . $message->rendered() . '</li>';
    }
    echo '</ul></li>';
}
echo '</ul>';
```
