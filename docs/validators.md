# Validators

Validators check that input is in the correct format, it will not attempt to change the
input. Results returning from Validators will always be valid or invalid.

If you wish to change the input: See [Filters](filters.md).

## Array

### Count

Checks that an array has a number of values between a specified minimum and maximum.

## Collection

### Identical

Checks that all values in a list are equal.

## DateTime

### Range

Checks if a DateTime object corresponds to a time between a specified minimum and maximum

### RangeDelta

Checks if a DateTime object corresponds to a time between a specified minimum and maximum time from now.

## Logical

### Not

Causes other validators to return the opposite result.

## Numeric

### Range

Checks if an integer/float is between a specified minimum and maximum.

## Object

### RequiredFields

Checks if array contains keys corresponding to all required fields.

## String

### DateString

Checks if string follows specified DateTime format.

### Length

Checks if string is between specified minimum and maximum lengths.

### Regex

Checks if string follows specified regex pattern.

## Type

### IsArray

Checks if input is an array.

### IsBool

Checks if input is a boolean.

### IsFloat

Checks if input is a float.

### IsInt

Checks if input is an integer.

### IsList

Checks if input is a list.

### IsString

Checks if input is a string.

## Utility

### AllOf

Takes a chain of validators to run in succession.
If the entire chain is considered valid AllOf will return `Result::valid`.

### OneOf

Takes a chain of validators to run in succession.
If any of the chain is considered valid then OneOf will return `Result::valid`

### Passes

This will always return `Result::valid`.

### Indifferent

This will always return `Result::noResult`.

### Fails

This will always return `Result::invalid`.
