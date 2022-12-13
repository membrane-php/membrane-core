# The Result Object

The Result object represents your data after it has been processed by Membrane.  
It has three properties you can access:

## Properties

### Value

This is the external data after it has passed through Membrane. Unless you made use of [Filters](filters.md) then it
will remain unchanged.

For the Result object `$result` you can access it like so:

```php
$result->value
```

### Result

This lets you know whether the external data passed through Membrane successfully or unsuccessfully.

For most use-cases you only need to call the public method `isValid(): bool`.  
This will return true as long as the data does not fail validation.

If you find yourself needing to access the result property you will notice it has three states:

* `Result::VALID` means that the external data has been passed validation.
* `Result::NO_RESULT` means that the external data has neither passed nor failed validation.  
  isValid() will still return true for noResult.  
  A common reason may be that it has only passed through Filters and no Validators have been used.
* `Result::INVALID` means that the external data has failed validation.

### Message Set

In the case that the external data fails validation i.e. `Result::invalid` the Result object
will contain at least one Message Set.

Message Sets provide details on why the external data has failed validation.

They consist of two properties:

#### Field Name

This specifies what part of the data failed validation.

#### Message

This specifies how the data failed validation.  
Message Sets can contain multiple messages. A message can be read using `rendered()`

```php
$message = new Message('this message is an %s', ['example']);
echo $message->rendered(); // this message is an example
```

## The Result Renderer

Your Results can be rendered in several formats, depending on your need.  
All Renderers implement the following interface:

```php
interface Renderer extends \JsonSerializable
{
    public function toString(): string;

    public function toArray(): array;
}
```

### Human Readable

Easy to read and lends itself well to debugging.

**Example**

```text
pet
    - age is a required field
pet->id
    - expects integer value, string passed instead
pet->name
    - String is expected to be a minimum of 5 characters 
    - String does not match the required pattern #[A-Z][a-z]*#
```

### JsonFlat

Follows a json format and groups messages based on their field.

**Example**

```text
{
    "pet": [
        "age is a required field"
    ],
    "pet->id": [
        "expected integer value, string passed instead"
    ],
    "pet->name": [
        "String is expected to be a minimum of 5 characters",
        "String does not match the required pattern #[A-Z][a-z]*#"
    ]
}
```

### JsonNested

Follows a json format and groups messages based on their field, child fields are nested within parent fields.

**Example**

```text
{
    "errors": []
    "fields": [
        "pet": [
            "errors": [
                "age is a required field"
            ],
            "fields": [
                "id": [
                    "errors": [
                        "expected integer value, string passed instead"
                    ],
                    "fields": []
                ],
                "name" : [
                    "errors": [
                        "String is expected to be a minimum of 5 characters",
                        "String does not match the required pattern #[A-Z][a-z]*#"
                    ],
                    "fields": []
                ]
            ]
        ]
    ]
}
```
