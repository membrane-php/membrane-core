# Result

## Value

This is the external data after it has passed through Membrane.

## Result

This lets you know whether the external data passed through Membrane successfully or unsuccessfully.

It has three states:

* `Result::valid` means that the external data has been passed validation.
* `Result::noResult` means that the external data has neither passed nor failed validation.  
  A common reason may be that it has only passed through Filters and no Validators have been used.
* `Result::invalid` means that the external data has failed validation.

A result is considered valid as long as it is not invalid.

## Message Set

In the case that the external data fails validation i.e. `Result::invalid` the Result object
will contain at least one Message Set.

Message Sets provide details on why the external data has failed validation. 

They consist of two properties:

### FieldName

This specifies what part of the data was being validated when it failed.

### Message

This specifies how the data failed validation. Message Sets can contain multiple messages.
A message can be read using the rendered() method
```php
$message = new Message('this message is an %s', ['example']);
echo $message->rendered(); // this message is an example
```
