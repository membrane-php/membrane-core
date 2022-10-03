# The Basics

## Result

All input passed through Membrane will return as part of the data object [Result](result.md).

All Results can have these three properties:

### Value

This is what your input will have become after passing through Membrane.

### Result

This is to inform you whether Membrane has considered the input valid, invalid or no result.

No result occurs if only filters have been applied to the data. It has not been flagged as invalid, 
however no validator has been applied to validate it.

The isValid() method considers anything valid as long as it is not invalid. It is recommended to use this
method instead of relying on the Result property alone.


### MessageSet

Invalid Results will contain a Message Set.

This will contain information on why the input has failed.

## Processors

### Field

Is a single item of data which filters can act upon and validators can validate.
The type of data is not restricted so can be a single scalar value, an array or an object.

loops through Filters and Validators, will stop immediately if it finds an invalid result.
This allows you to verify that the data is the correct format before moving on to more specific Filters or Validators.

### Field Set

A Field Set is a keyed array of data items, which can be acted upon as a group or individually by their key

### Collection

Is a list of values (integer, 0 indexed, consecutive keys) which should be,
conceptually, the same thing eg a group of tags. You can act upon these as a group or individually,
however unlike a fieldset, the same set of filters and validators will be applied to each item in the array.

## Filters

Filters ensure that input is the correct format and may attempt
to change the input to match the correct format.

If you do not wish to change the input: See [Validators](validators.md)

## Validators

Validators check that input is in the correct format, it will not attempt to change the
input.

If you wish to change the input: See [Filters](filters.md).
