# Validators

Validators check that input is in the correct format, it will not attempt to change the
input.  
If you wish to change the input: See [Filters](filters.md).

Results returning from Validators will always be valid or invalid.

## Array

### Count

Checks that an array has a number of values between a specified minimum and maximum.

```
new Count($min, $max)
```

| Parameter | Type | Default Value | Notes                              |
|-----------|------|---------------|------------------------------------|
| $min      | int  | 0             |                                    |
| $max      | int  | null          | If set to null, maximum is ignored |

## Collection

### Identical

Checks that all values in a collection are equal.

```
new Identical()
```

## DateTime

### Range

Checks if a DateTime object corresponds to a time between a specified minimum and maximum.

```
new Range($min, $max)
```

| Parameter | Type     | Default Value | Notes                              |
|-----------|----------|---------------|------------------------------------|
| $min      | DateTime | null          | If set to null, minimum is ignored |
| $max      | DateTime | null          | If set to null, maximum is ignored |

### RangeDelta

Checks if a DateTime object corresponds to a time between a specified minimum and maximum time from now.

```
new RangeDelta($min, $max)
```

| Parameter | Type         | Default Value | Notes                              |
|-----------|--------------|---------------|------------------------------------|
| $min      | DateInterval | null          | If set to null, minimum is ignored |
| $max      | DateInterval | null          | If set to null, maximum is ignored |

## Numeric

### Range

Checks if an integer/float is between a specified minimum and maximum.

```
new Range($min, $max)
```

| Parameter | Type | Default Value | Notes                              |
|-----------|------|---------------|------------------------------------|
| $min      | int  | null          | If set to null, minimum is ignored |
| $max      | int  | null          | If set to null, maximum is ignored |

## Object

### RequiredFields

Checks if array contains keys corresponding to all required fields.

```
new RequiredFields(...$fields)
```

| Parameter  | Type   |
|------------|--------|
| ...$fields | string |

## String

### DateString

Checks if string input follows specified DateTime format.

```
new DateString($format)
```

| Parameter | Type   |
|-----------|--------|
| $format   | string |

### Length

Checks if string input is between specified minimum and maximum number of characters.

```
new Length($min, $max)
```

| Parameter | Type | Default Value | Notes                              |
|-----------|------|---------------|------------------------------------|
| $min      | int  | 0             |                                    |
| $max      | int  | null          | If set to null, maximum is ignored |

### Regex

Checks if string follows specified regex pattern.

```
new DateString($pattern)
```

| Parameter | Type   |
|-----------|--------|
| $pattern  | string |

## Type

### IsArray

Checks if input is an array with key-value pairs, it accepts empty arrays but not lists.

```
new IsArray()
```

### IsBool

Checks if input is a boolean.

```
new IsBool()
```

### IsFloat

Checks if input is a float.

```
new IsFloat()
```

### IsInt

Checks if input is an integer.

```
new IsInt()
```

### IsList

Checks if input is a list, it accepts empty arrays but not arrays with key-value pairs.

```
new IsList()
```

### IsString

Checks if input is a string.

```
new IsString()
```

## Utility

### Not

Inverts the Result of another Validator.

```
new Not($invertedValidator)
```

| Parameter          | Type      |
|--------------------|-----------|
| $invertedValidator | Validator |

### AllOf

Takes a chain of validators to run in succession.  
All validators provided will be applied to the input,
unlike the behaviour of Field which stops as soon as a validator is flagged as invalid.  
This is useful if you want to check multiple validation rules are true
and return all error messages back to the user in the case of failure.


If the entire chain is considered valid AllOf will return `Result::valid`.

```
new AllOf(...$chain)
```

| Parameter | Type      |
|-----------|-----------|
| ...$chain | Validator |

### OneOf

Takes a chain of validators to run in succession.

If any of the chain is considered valid then OneOf will return `Result::valid`.

```
new OneOf(...$chain)
```

| Parameter | Type      |
|-----------|-----------|
| ...$chain | Validator |

### Passes

_(Primarily intended for test usage.)_

This will always return `Result::valid`.

```
new Passes()
```

You may find this useful if you do not use any validators but still want a valid result at the end.

### Indifferent

_(Primarily intended for test usage)_

This will always return `Result::noResult`.

```
new Indifferent()
```

### Fails

_(Primarily intended for test usage)_

This will always return `Result::invalid`.

```
new Fails()
```
