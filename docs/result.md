# Result

## Value

This is the external data after it has passed through Membrane.

## Result

This lets you know whether the external data passed through Membrane successfully or unsuccessfully.

It has three states:

* `Result::valid` means that the external data has been passed validation.
* `Result::noResult` means that the external data has neither passed nor failed validation.
* `Result::invalid` means that the external data has failed validation.

A result is considered valid as long as it is not invalid.

## Message Set

In the case that the external data fails validation i.e. `Result::invalid` the Result object
will contain at least one Message Set.

Message Sets provide details on why the external data has failed validation. They consist of two properties:

* Fieldname: This specifies what part of the data was being validated when it failed.
* Message: This specifies how the data failed validation.
