# Processors

For most use-cases you will not need to interact with Processors directly,
Membrane will build a Processor object for you behind the scenes
tailored to validating data against your Specification(s).

Each Processor is unique, designed with a specific purpose in mind.  
That said, all Processors have two methods in common:

```php
processes(): string
```

`processes()` returns the name of the field it processes

```php
process(FieldName $parentFieldName, mixed $value): Result
```

`process()` returns the [Result](result.md) object from validating `$value`.  
It also asks for the `$parentFieldName` in the case of nested Processors this will make
your [MessageSet's](result.md#message-set) [FieldName](result.md#field-name)
more precise.

## General-Use Processors

### Field

A Field validates a single item of data. i.e. A scalar value.

```php
new Field($processes, ...$chain)
```

| Parameter  | Type                |
|------------|---------------------|
| $processes | string              |
| ...$chain  | Filter or Validator |

When processing lists of scalar values, you will find a Field nested inside a [Collection](processors.md#collection),
validating the scalar items.  
When processing arrays with scalar properties, you will find Fields nested inside a [FieldSet](processors.md#field-set),
validating each scalar
property.

### Collection

A Collection processes a list. i.e. `['a', 'b', 'c']`

```php
new Collection($processes, ...$chain)
```

| Parameter  | Type      | Notes                                                                |
|------------|-----------|----------------------------------------------------------------------|
| $processes | string    |                                                                      |
| ...$chain  | Processor | It can only take one BeforeSet, one AfterSet and one other Processor |

A Collection takes a sequence of other Processors to validate a list, as well as the items inside it.

A Collection may take:

- one (and only one) [BeforeSet](processors.md#before-set), a specialized Field object that always processes first.
- one (and only one) [AfterSet](processors.md#after-set), a specialized Field object that always processes last.
- any number of other processors

**Please Note**: If other Processors are used they expect to process a list. As such if you create a BeforeSet that
changes the datatype, those Processors will throw an Exception.

### Field Set

A FieldSet processes an array, i.e. `['a' => 1, 'b' => 2, 'c' => 3]`

```php
new FieldSet($processes, ...$chain)
```

| Parameter  | Type      | Notes                                           |
|------------|-----------|-------------------------------------------------|
| $processes | string    |                                                 |
| ...$chain  | Processor | It can only take one BeforeSet and one AfterSet |

A FieldSet may take:

- one (and only one) [BeforeSet](processors.md#before-set), a specialized Field object that always processes first.
- one (and only one) [AfterSet](processors.md#after-set), a specialized Field object that always processes last.
- any number of other processors

**Please Note**: If other Processors are used they expect to process a list. As such if you create a BeforeSet that
changes the datatype, those Processors will throw an Exception.

### After Set

AfterSets are a specialized [Field](processors.md#field). They work on a List or Array as a whole and **will always be
the final processor to
act.**

```php
new AfterSet(...$chain)
```

| Parameter | Type                |
|-----------|---------------------|
| ...$chain | Filter or Validator |

An AfterSet takes a chain of [Filters](filters.md) or [Validators](validators.md) that act on the entire List/Array,
they do not validate individual items within it.

### Before Set

AfterSets are a specialized [Field](processors.md#field). They work on a List or Array as a whole and **will always be
the first processor to
act.**

```php
new BeforeSet(...$chain)
```

| Parameter | Type                |
|-----------|---------------------|
| ...$chain | Filter or Validator |

A BeforeSet takes a chain of [Filters](filters.md) or [Validators](validators.md) that act on the entire List/Array,
they do not validate individual items within it.

## OpenAPI Processors

The following processors are intended for Open-API specific use cases.  
Alternative uses are not recommended.

### AllOf

Designed specifically to deal with the 'allOf' keyword of OpenAPI:  
The AllOf processor takes a chain of processors (one for each schema within the 'allOf') and makes sure that all return
a valid result.

| Parameter  | Type      |
|------------|-----------|
| $processes | string    |
| ...$chain  | Processor |

### AnyOf

| Parameter  | Type      |
|------------|-----------|
| $processes | string    |
| ...$chain  | Processor |

Designed specifically to deal with the 'anyOf' keyword of OpenAPI:  
The AnyOf processor takes a chain of processors (one for each schema within the 'anyOf') and makes sure that at least
one processor returns a valid result.

### OneOf

| Parameter  | Type      |
|------------|-----------|
| $processes | string    |
| ...$chain  | Processor |

Designed specifically to deal with the 'oneOf' keyword of OpenAPI:  
The OneOf processor takes a chain of processors (one for each schema within the 'oneOf') and makes sure that one and
only one processor returns a valid result.

### Json

| Parameter | Type      |
|-----------|-----------|
| $wrapped  | Processor |

Designed specifically to wrap a processor that is expecting a json object. The Json processor attempts to decode a
string into a json object before passing it into the wrapped processor.

### Request

Designed specifically to convert HTTP Requests into an array format, like so:

```php
[
    'path' => '...',
    'query' => '...',
    'header' => [...],
    'cookie' => [...],
    'body' => '...'
]
```

If data is passed into the request processor in this format already, this will pass as well.
