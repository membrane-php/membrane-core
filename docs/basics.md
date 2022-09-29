# The Basics

## Result

All input passed through Membrane will return as part of the data object [Result](result.md).

All Results have these two properties:

* value - this is what your input will have become after passing through Membrane.
* result - this is to inform you whether Membrane has considered the input valid or invalid

An invalid Result has one additional property

* messageSet - this will contain information on why the input has failed

## Processors

### Field

Is a single item of data which filters can act upon and validators can validate.
The type of data is not restricted so can be a single scalar value, an array or an object.

loops through Filters and Validators, will stop immediately if it finds an invalid result.
This allows you to verify that the data is the correct format before moving on to more specific Filters or Validators.

### Field Set

A field set is

### Collection

Is a keyed array of data items, which can be acted upon as a group or individually by their key

## Filters

Filters ensure that input is the correct format and may attempt
to change the input to match the correct format.

If you do not wish to change the input: See [Validators](validators.md)

## Validators

Validators check that input is in the correct format, it will not attempt to change the
input.

If you wish to change the input: See [Filters](filters.md).
