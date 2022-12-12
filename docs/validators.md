# Validators

Validators check data is in the correct format, Validators will never change data.  
If you expect that you'll need to change incoming data; See [Filters](filters.md).

All Validators implement the Membrane\Validator interface:

```php
interface Validator
{
    public function validate(mixed $value): Result;
}
```

[Results](result.md) returned from Validators will always be [Result::VALID or Result::INVALID](result.md#result).

## Collection

### Count

Checks that an array has a number of values between a specified minimum and maximum.

```php
new Count($min, $max)
```

| Parameter | Type | Default Value | Notes                              |
|-----------|------|---------------|------------------------------------|
| $min      | int  | 0             |                                    |
| $max      | int  | null          | If set to null, maximum is ignored |

**Example 1**

```php
<?php
$count = new Count(0, 5);
$array = ['a' => 1, 'b' => 2, 'c' => 3];

$result = $count->validate($array);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
['a' => 1, 'b' => 2, 'c' => 3]
Result was valid
```

### Contained

Checks that a collection contains the given value.

```php
new Contained(array $enum)
```

**Example 1**

```php
$contained = new Contained(['a', 'b', 'c']);

$result = $contained->validate('b');

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
b
Result was valid
```

**Example 2**

```php
$contained = new Contained(['a', 'b', 'c']);

$result = $contained->validate('e');

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
e
Result was invalid
```

### Identical

Checks that all values in a collection are identical.

```php
new Identical()
```

**Example 1**

```php
<?php
$identical = new Identical();
$list = ['a', 'a', 'a'];

$result = $identical->validate($list);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
['a', 'a', 'a']
Result was valid
```

### Unique

Checks that all values in a collection are unique.

```php
new Unique()
```

**Example 1**

```php
<?php
$unique = new Unique();
$list = ['a', 'b', 'c'];

$result = $unique->validate($list);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
['a', 'b', 'c']
Result was valid
```

## DateTime

### Range

Checks if a DateTime object corresponds to a time between a specified minimum and maximum.

```php
new Range($min, $max)
```

| Parameter | Type     | Default Value | Notes                              |
|-----------|----------|---------------|------------------------------------|
| $min      | DateTime | null          | If set to null, minimum is ignored |
| $max      | DateTime | null          | If set to null, maximum is ignored |

**Example 1**

```php
<?php
$min = DateTime::createFromFormat('Y-m-d H:i:s', '1900-12-25 09:30:00');
$max = DateTime::createFromFormat('Y-m-d H:i:s', '2050-04-15 16:05:33');
$range = new Range($min, $max);
$dateTime = DateTime::createFromFormat('Y-m-d H:i:s', '1970-01-01 00:00:00');

$result = $range->validate($dateTime);

echo $result->value->format('Y-m-d H:i:s');
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
1970-01-01 00:00:00
Result was valid
```

### RangeDelta

Checks if a DateTime object corresponds to a time between a specified minimum and maximum time from now.

```php
new RangeDelta($min, $max)
```

| Parameter | Type         | Default Value | Notes                              |
|-----------|--------------|---------------|------------------------------------|
| $min      | DateInterval | null          | If set to null, minimum is ignored |
| $max      | DateInterval | null          | If set to null, maximum is ignored |

**Example 1**

```php
<?php
$rangeDelta = new RangeDelta(new DateInterval('P100Y'), new DateInterval('P100Y'));
$dateTime = DateTime::createFromFormat('Y-m-d H:i:s', '2121-01-01 00:00:00');

$result = $rangeDelta->validate($dateTime);

echo $result->value->format('Y-m-d H:i:s');
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
2121-01-01 00:00:00
Result was valid
```

## FieldSet

### RequiredFields

Checks if array contains keys corresponding to all required fields.

```php
new RequiredFields(...$fields)
```

| Parameter  | Type   |
|------------|--------|
| ...$fields | string |

**Example 1**

```php
<?php
$requiredFields = new RequiredFields('a', 'c');
$array = ['a' => 1, 'b' => 2, 'c' => 3]

$result = $requiredFields->validate($array);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
['a' => 1, 'b' => 2, 'c' => 3]
Result was valid
```

## Numeric

### Maximum

Checks if a given value complies with specified maximum.

```php
new Maximum($max, $exclusive)
```

| Parameter  | Type         | Default Value | Notes                                                      |
|------------|--------------|---------------|------------------------------------------------------------|
| $max       | int or float |               |                                                            |
| $exclusive | boolean      | false         | determines whether it is an exclusive or inclusive maximum |

**Example 1**

```php
<?php
$max = new Maximum(10);

$result = $max->validate(5);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
5
Result was valid
```

### Minimum

Checks if a given value complies with specified minimum.

```php
new Minimum($min, $exclusive)
```

| Parameter  | Type         | Default Value | Notes                                                      |
|------------|--------------|---------------|------------------------------------------------------------|
| $min       | int or float |               |                                                            |
| $exclusive | boolean      | false         | determines whether it is an exclusive or inclusive minimum |

**Example 1**

```php
<?php
$min = new Minimum(10);

$result = $min->validate(15);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
15
Result was valid
```

### MultipleOf

Checks if an integer/float is a multiple of a given value.

```php
new MultipleOf ($factor)
```

| Parameter | Type         |
|-----------|--------------|
| $factor   | int or float |

**Example 1**

```php
<?php
$multipleOf = new MultipleOf(5);

$result = $multipleOf->validate(25);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
25
Result was valid
```

## String

### DateString

Checks if string input follows specified DateTime format.

```php
new DateString($format)
```

| Parameter | Type   |
|-----------|--------|
| $format   | string |

**Example 1**

```php
<?php
$dateString = new DateString('Y-m-d');
$string = '1970-01-01';

$result = $dateString->validate($string);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
1970-01-01
Result was valid
```

### Length

Checks if string input is between specified minimum and maximum number of characters.

```php
new Length($min, $max)
```

| Parameter | Type | Default Value | Notes                              |
|-----------|------|---------------|------------------------------------|
| $min      | int  | 0             |                                    |
| $max      | int  | null          | If set to null, maximum is ignored |

**Example 1**

```php
<?php
$length = new Length(20, 30);
$string = 'this string is 28 characters';

$result = $length->validate($string);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
this string is 28 characters
Result was valid
```

### Regex

Checks if string follows specified regex pattern.

```php
new Regex($pattern)
```

| Parameter | Type   |
|-----------|--------|
| $pattern  | string |

**Example 1**

```php
<?php
$regex = new Regex('/\d{3}/');
$string = '123';

$result = $regex->validate($string);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
123
Result was valid
```

## Type

### IsArray

Checks if input is an array with key-value pairs, it accepts empty arrays but not lists.

```php
new IsArray()
```

**Example 1**

```php
<?php
$isArray = new IsArray();
$array = ['a' => 1, 'b' => 2, 'c' => 3];

$result = $isArray->validate($array);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
['a' => 1, 'b' => 2, 'c' => 3]
Result was valid
```

**Example 2**

```php
<?php
$isArray = new IsArray();
$list = [1, 2, 3];

$result = $isArray->validate($list);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
[1, 2, 3]
Result was invalid
```

### IsBool

Checks if input is a boolean.

```php
new IsBool()
```

**Example 1**

```php
<?php
$isBool = new IsBool();
$bool = false;

$result = $isBool->validate($bool);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
false
Result was valid
```

### IsFloat

Checks if input is a float.

```php
new IsFloat()
```

**Example 1**

```php
<?php
$isFloat = new IsFloat();
$float = 1.23;

$result = $isFloat->validate($float);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
1.23
Result was valid
```

### IsInt

Checks if input is an integer.

```php
new IsInt()
```

**Example 1**

```php
<?php
$isInt = new IsInt();
$int = 123;

$result = $isInt->validate($int);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
123
Result was valid
```

### IsList

Checks if input is a list, it accepts empty arrays but not arrays with key-value pairs.

```php
new IsList()
```

**Example 1**

```php
<?php
$isList = new IsList();
$list = [1, 2, 3];

$result = $isList->validate($list);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
[1, 2, 3]
Result was valid
```

**Example 2**

```php
<?php
$isList = new IsList();
$array = ['a' => 1, 'b' => 2, 'c' => 3];

$result = $isList->validate($array);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
['a' => 1, 'b' => 2, 'c' => 3]
Result was invalid
```

### IsNull

Checks if input is null.

```php
new IsNull()
```

**Example 1**

```php
<?php
$isNull = new IsNull();

$result = $isNull->validate(null);

var_dump($result->value);
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
NULL
Result was valid
```

### IsNumber

Checks if input is numeric.

```php
new IsNumber()
```

```php
<?php
$isNumber = new IsNumber();
$values = [1, 2.0, '3']

foreach($values as $value) {
    $result = $isNumber->validate($value);
    
    echo $result->value;
    echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
}


```

The above example will output the following

```text
1
Result was valid
2.0
Result was valid
"3"
Result was valid
```

### IsString

Checks if input is a string.

```php
new IsString()
```

**Example 1**

```php
<?php
$isString = new IsString();
$string = 'foo';

$result = $isString->validate($string);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
foo
Result was valid
```

## Utility

### Not

Inverts the Result of another Validator.

```php
new Not($invertedValidator)
```

| Parameter          | Type      |
|--------------------|-----------|
| $invertedValidator | Validator |

**Example 1**

```php
<?php
$notFails = new Not(new Fails());
$input = 'foo';

$result = $notFails->validate($input);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
foo
Result was valid
```

### AllOf

Takes a chain of validators to run in succession.  
All validators provided will be applied to the input,
unlike the behaviour of Field which stops as soon as a validator is flagged as invalid.  
This is useful if you want to check multiple validation rules are true
and return all error messages back to the user in the case of failure.

If the entire chain is considered valid AllOf will return `Result::valid`.

```php
new AllOf(...$chain)
```

| Parameter | Type      |
|-----------|-----------|
| ...$chain | Validator |

**Example 1**

```php
<?php
$allOf = new AllOf(new IsString(), new Length(0, 5));
$input = 'foo';

$result = $allOf->validate($input);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
foo
Result was valid
```

**Example 2**

```php
<?php
$allOf = new AllOf(new IsString(), new Length(0, 5));
$input = 'foo bar';

$result = $allOf->validate($input);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
foobar
Result was invalid
```

This example would pass the IsString Validator but fail the Length Validator as its length is greater than 5.  
AllOf requires everything to pass for a valid result.

### AnyOf

Takes a chain of validators to run in succession.

If any of the chain is considered valid then AnyOf will return `Result::valid`.

```php
new AnyOf(...$chain)
```

| Parameter | Type      |
|-----------|-----------|
| ...$chain | Validator |

**Example 1**

```php
<?php
$anyOf = new AnyOf(new IsInt(), new Length(0, 5));
$input = 'foo';

$result = $anyOf->validate($input);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
foo
Result was valid
```

This example would fail the IsInt Validator but pass the Length Validator as its length is less than 5.  
AnyOf requires only one thing to pass for a valid result.

### Passes

_(Primarily intended for test usage.)_

This will always return `Result::valid`.

```php
new Passes()
```

**Example 1**

```php
<?php
$passes = new Passes();
$input = 'foo';

$result = $passes->validate($input);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
foo
Result was valid
```

You may find this useful if you do not use any validators but still want a valid result at the end.

### Indifferent

_(Primarily intended for test usage)_

This will always return `Result::noResult`.

```php
new Indifferent()
```

**Example 1**

```php
<?php
$indifferent = new Indifferent();
$input = 'foo';

$result = $indifferent->validate($input);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
foo
Result was valid
```

### Fails

_(Primarily intended for test usage)_

This will always return `Result::invalid`.

```php
new Fails()
```

**Example 1**

```php
<?php
$fails = new Fails();
$input = 'foo';

$result = $fails->validate($input);

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
foo
Result was invalid
```
