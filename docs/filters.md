# Filters

Filters ensure that input is the correct format and may attempt
to change the input to match the correct format.
If you do not wish to change the input: See [Validators](validators.md)

Results returning from Filters will always be either noResult or invalid if it
is unable to process the data provided. You should not rely on this behaviour,
common best practice is to use validators to ensure the data passed can be filtered.

## Create Object

Create objects from external data.

### FromArray

construct new data object from an array. $className must correspond to a class with a method named 'fromArray'

```
new FromArray($className)
```

| Parameter  | Type   |
|------------|--------|
| $className | string |

### WithNamedArguments

Constructs a new object using its constructor.

This relies upon the named arguments feature in PHP,
so the parameter names of your object must match the keys in the data array.

You can use the shape filters to modify the data structure to match if need be.

```
new WithNamedArguments($className)
```

| Parameter  | Type   |
|------------|--------|
| $className | string |

## Shape

Methods that alter the shape of arrays/lists.

### Collect

Collect key-value pairs specified by $fields from the parent array and
append their values to a nested list specified by $newField.

```
new Collect($newField, ...$fields)
```

| Parameter  | Type   |
|------------|--------|
| $newField  | string |
| ...$fields | string |

### Delete

Deletes a specified key-value pairs from an array.

```
new Delete(...$fieldNames)
```

| Parameter      | Type   |
|----------------|--------|
| ...$fieldNames | string |

### Nest

Opposite of Pluck.

Collect key-value pairs specified by $fields from the parent array and
appends them to a nested array specified by $newField.

```
new Nest($newField, ...$fields)
```

| Parameter  | Type   |
|------------|--------|
| $newField  | string |
| ...$fields | string |

### Pluck

Opposite of Nest.

Collect key-value pairs specified by $fieldNames from a nested array specified by $fieldSet and
appends them to the parent array.

```
new Pluck($fieldSet, ...$fieldnames)
```

| Parameter      | Type   |
|----------------|--------|
| $fieldSet      | string |
| ...$fieldNames | string |

### Rename

Renames a specified string key in an array.

```
new Rename($old, $new)
```

| Parameter | Type   | Notes               |
|-----------|--------|---------------------|
| $old      | string | Must not equal $new |
| $new      | string | Must not equal $old |

### Truncate

Deletes as many values as necessary from the end of a list to avoid exceeding the specified maximum length.

```
new Truncate($maxLength)
```

| Parameter | Type | Notes                          |
|------------|-----|--------------------------------|
| $maxLength | int | Only accepts positive integers |
