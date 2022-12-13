# Getting Started

## Installation

To start; require the `membrane/membrane` package in [Composer](https://getcomposer.org/):

```text
composer require membrane/membrane
```

## The Basics

### What Goes In

Membrane takes a Specification to validate against.   
The Specifications currently supported are as follows:

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

If your data is valid, that's great, your application can now use the data with confidence.

What if your data is invalid?  
Your Result object will contain a [MessageSet](result.md#message-set) for each validation failure,
The MessageSet(s) will contain information to clarify the reason the data is considered invalid.

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
