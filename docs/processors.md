# Processors

Processors chain together a sequence of [Filters](filters.md) and [Validators](validators.md) to be performed.

## Field

On its own a Field applies its $chain to all items in any single item of data.
The type of data is not restricted so can be a single scalar value, an array or an object.
By using it as part of a FieldSet it will only apply its $chain to a key in the array
matching the string $processes.

```php
new Field($processes, ...$chain)
```

| Parameter  | Type                |
|------------|---------------------|
| $processes | string              |
| ...$chain  | Filter or Validator |

## Collection

A collection takes a $chain of Processors and applies their Filters and Validators in succession
to all elements in a list.

BeforeSets will be applied first, across the entire array.

AfterSets will be applied last, across the entire array.

Other Processors will be applied in between and **require list values.** If other Processors are used,
ensure that your BeforeSet returns a list value.

```php
new Collection($processes, ...$chain)
```

| Parameter  | Type      | Notes                                                                |
|------------|-----------|----------------------------------------------------------------------|
| $processes | string    |                                                                      |
| ...$chain  | Processor | It can only take one BeforeSet, one AfterSet and one other Processor |

## Field Set

A FieldSet takes a $chain of Processors and applies their Filters and Validators in succession
to elements in an array either as a group or individually by their key.

BeforeSets will be applied first, across the entire array.

AfterSets will be applied last, across the entire array.

Other Processors will be applied in between and only to the keys in the array that correspond to
their individual $processes. As such other Processors **require array values.** If other Processors
are used, ensure that your BeforeSet returns an array value.

```php
new FieldSet($processes, ...$chain)
```

| Parameter  | Type      | Notes                                           |
|------------|-----------|-------------------------------------------------|
| $processes | string    |                                                 |
| ...$chain  | Processor | It can only take one BeforeSet and one AfterSet |

## After Set

AfterSets take a $chain of Filters and Validators and apply them across a whole list/array.
They will always be the final processor to act.

```php
new AfterSet(...$chain)
```

| Parameter  | Type                |
|------------|---------------------|
| ...$chain  | Filter or Validator |

## Before Set

BeforeSets take a $chain of Filters and Validators and apply them across a whole list/array.
They will always be the first processor to act.

```php
new BeforeSet(...$chain)
```

| Parameter  | Type                |
|------------|---------------------|
| ...$chain  | Filter or Validator |
