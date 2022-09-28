# Filters

Filters ensure that input is the correct format and may attempt
to change the input to match the correct format.
Results returning from Filters will always be noResult or invalid.

If you do not wish to change the input: See [Validators](validators.md)

## Create Object

Create objects from external data

### FromArray

create new object from an array

### WithNamedArguments

create new object from named arguments

## Shape

Methods that alter the shape of arrays/lists

### Collect

Collects specified key-value pairs from an array and nests the values in a new list

### Delete

Deletes a specified key-value pair from an array

### Nest

Collects specified key-value pairs from an array and nests them in a new array

### Pluck

Collects specified key-value pairs from an array and nests them in a new array
as the value of a new key-value pair.

### Rename

Renames a specified key from an array

### Truncate

Deletes as many values as necessary from a list to avoid exceeding the specified maximum length
