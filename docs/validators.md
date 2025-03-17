# Validators

Validators check data is in the correct format, Validators will never change data.  
If you expect that you'll need to change incoming data; See [Filters](filters.md).

## Interface

All Validators implement the `Membrane\Validator` interface:

```php
interface Validator
{
    public function validate(mixed $value): Result;
    
    public function __toString(): string;
}
```

## Methods

All Validators contain the following methods:

### Validate

```php
public function validate(mixed $value): Result
```

`validate()` will return a [Result](result.md) object detailing if and how the given value was validated.

[Results](result.md) returned from Validators will always be [Result::VALID or Result::INVALID](result.md#result).  
(Only the `Validators\Utility` namespace contains exceptions to this rule).

### __ToString

```php
__toString(): string
```

`__toString()` returns a plain english description of what the validator does, you may find this useful for debugging.
This method can also be called implicitly by typecasting your validator as string. i.e. `(string) $validator`

## Collection

### Count

Checks the number of values in a collection is between a specified minimum and maximum.

```php
new \Membrane\Validator\Collection\Count($min, $max)
```

| Parameter | Type | Default Value | Notes                              |
|-----------|------|---------------|------------------------------------|
| $min      | int  | 0             |                                    |
| $max      | int  | null          | If set to null, maximum is ignored |

**Example**

```php
<?php

$count = new \Membrane\Validator\Collection\Count(1, 2);

$examples = [
    [],
    ['a'],
    ['a', 'b'],
    ['a', 'b', 'c'],
];

foreach ($examples as $example) {
    $result = $count->validate($example);
    
    if ($result->isValid()) {
        echo json_encode($result->value) . ' is valid \n';
    } else {
        echo json_encode($result->value) . ' is invalid \n';
        foreach($result->messageSets[0]->messages as $message) {
            echo '\t' . $message->rendered() . '\n';
        }
    }
}
```

The above example will output:

```text
[] is invalid
    Array is expected have a minimum of 1 values
["a"] is valid
["a","b"] is valid
["a","b","c"] is invalid
    Array is expected have a maximum of 2 values
```

### Contained

Checks a collection contains the given value.

```php
new \Membrane\Validator\Collection\Contained(array $enum)
```

**Example**

```php
<?php

$contained = new \Membrane\Validator\Collection\Contained(['a', 'b', 'c']);

$examples = [
    'a',
    'b',
    'c',
];

foreach ($examples as $example) {
    $result = $contained->validate($example);
    
    if ($result->isValid()) {
        echo $result->value . ' is valid \n';
    } else {
        echo $result->value . ' is invalid \n';
        foreach($result->messageSets[0]->messages as $message) {
            echo '\t' . $message->rendered() . '\n';
        }
    }
}
```

The above example will output:

```text
a is invalid
    Contained validator did not find value within array
b is valid
c is invalid
    Contained validator did not find value within array
```

### Identical

Checks all values in a collection are identical.
Values that are equal but not identical are considered invalid.

```php
new \Membrane\Validator\Collection\Identical();
```

**Example**

```php
<?php

$identical = new \Membrane\Validator\Collection\Identical();

$examples = [
    [],
    ['a'],
    ['a', 'a'],
    ['a', 'b'],
    ['a', 'b', 'b'],
    ['1', 1, 1.0]
];

foreach ($examples as $example) {
    $result = $identical->validate($example);
    
    if ($result->isValid()) {
        echo json_encode($result->value) . ' is valid \n';
    } else {
        echo json_encode($result->value) . ' is invalid \n';
        foreach($result->messageSets[0]->messages as $message) {
            echo '\t' . $message->rendered() . '\n';
        }
    }
}
```

The above example will output the following

```text
[] is valid
["a"] is valid
["a","a"] is valid
["a","b"] is invalid
    Do not match
["a","b","b"] is invalid
    Do not match
["1",1,1.0] is invalid
    Do not match
```

### Unique

Checks all values in a collection are unique.  
Values that are equal but not identical are considered valid.

```php
new \Membrane\Validator\Collection\Unique()
```

**Example**

```php
<?php

$unique = new \Membrane\Validator\Collection\Unique();

$examples = [
    [],
    ['a'],
    ['a', 'a'],
    ['a', 'b'],
    ['a', 'b', 'b'],
    ['1', 1, 1.0]
];

foreach ($examples as $example) {
    $result = $unique->validate($example);
    
    if ($result->isValid()) {
        echo json_encode($result->value) . ' is valid \n';
    } else {
        echo json_encode($result->value) . ' is invalid \n';
        foreach($result->messageSets[0]->messages as $message) {
            echo '\t' . $message->rendered() . '\n';
        }
    }
}
```

The above example will output the following

```text
[] is valid
["a"] is valid
["a","a"] is invalid
    Collection contains duplicate values
["a","b"] is valid
["a","b","b"] is invalid
    Collection contains duplicate values
["1",1,1.0] is valid
```

## DateTime

### Range

Checks if a DateTime object corresponds to a time between a specified minimum and maximum.

```php
new \Membrane\Validator\DateTime\Range($min, $max)
```

| Parameter | Type     | Default Value | Notes                              |
|-----------|----------|---------------|------------------------------------|
| $min      | DateTime | null          | If set to null, minimum is ignored |
| $max      | DateTime | null          | If set to null, maximum is ignored |

**Example**

```php
<?php
$min = new \DateTime('1900-12-25 09:30:00');
$max = new \DateTime('2050-04-15 16:05:33');
$range = new \Membrane\Validator\DateTime\Range($min, $max);
$examples = [
    new \DateTime('1000-5-13 05:00:00'),
    new \DateTime('1970-01-01 00:00:00'),
    new \DateTime('2050-04-15 16:05:34'),
];

foreach ($examples as $example) {
    $result = $range->validate($example);
    
    if ($result->isValid()) {
        echo $result->value . ' is valid \n';
    } else {
        echo $result->value . ' is invalid \n';
        foreach($result->messageSets[0]->messages as $message) {
            echo '\t' . $message->rendered() . '\n';
        }
    }
}
```

The above example will output the following

```text
1000-5-13 05:00:00 is invalid
    DateTime is expected to be after 1900-12-25 09:30:00
1970-01-01 00:00:00 is valid
2050-04-15 16:05:34 is invalid
    DateTime is expected to be before 2050-04-15 16:05:33
```

### RangeDelta

Checks if a DateTime object corresponds to a time between a specified minimum and maximum time from now.

```php
new \Membrane\Validator\DateTime\RangeDelta($min, $max)
```

| Parameter | Type         | Default Value | Notes                              |
|-----------|--------------|---------------|------------------------------------|
| $min      | DateInterval | null          | If set to null, minimum is ignored |
| $max      | DateInterval | null          | If set to null, maximum is ignored |

**Example**

The minimum and maximum of RangeDelta will update constantly, thus for the purpose of demonstration:  
in this example, the date and time is 1970-01-01 00:00:00

```php
<?php
$rangeDelta = new \Membrane\Validator\DateTime\RangeDelta(new DateInterval('P100Y2M'), new DateInterval('P1M5D'));
$examples = [
    new \DateTime('1000-5-13 05:00:00'),
    new \DateTime('1970-01-01 00:00:00'),
    new \DateTime('2050-04-15 16:05:34'),
];

foreach ($examples as $example) {
    $result = $range->validate($example);
    
    if ($result->isValid()) {
        echo $result->value . ' is valid \n';
    } else {
        echo $result->value . ' is invalid \n';
        foreach($result->messageSets[0]->messages as $message) {
            echo '\t' . $message->rendered() . '\n';
        }
    }
}
```

The above example will output the following

```text
1000-5-13 05:00:00 is invalid
    DateTime is expected to be after 1869-10-00 00:00:00
1970-01-01 00:00:00 is valid
2050-04-15 16:05:34 is invalid
    DateTime is expected to be before 1970-02-06 00:00:00
```

## FieldSet

### FixedFields

Checks that array does not contain any additional fields.

```php
new \Membrane\Validator\FieldSet\FixedFields(...$fields);
```

| Parameter  | Type   |
|------------|--------|
| ...$fields | string |

**Example**

```php
<?php
$fixedFields = new \Membrane\Validator\FieldSet\FixedFields('a', 'b');
$arrayOfFields = [
        [],
        ['a' => 1],
        ['a' => 1, 'b' => 2],
        ['c' => 3],
        ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5],
    ]

foreach ($arrayOfFields as $fields) {
    $result = $fixedFields->validate($fields);
    
    if ($result->isValid()) {
        echo json_encode($result->value) . ' is valid \n';
    } else {
        echo json_encode($result->value) . ' is invalid \n';
        foreach($result->messageSets[0]->messages as $message) {
            echo '\t' . $message->rendered() . '\n';
        }
    }
}
```

The above example will output the following

```text
{} is valid
{"a":1} is valid
{"a":1,"b":2} is valid
{"c":3} is invalid
    c is not a fixed field
{"a":1,"b":2,"c":3, "d": 4, "e": 5} is invalid
    c is not a fixed field
    d is not a fixed field
    e is not a fixed field
```

### RequiredFields

Checks if array contains keys corresponding to all required fields.

```php
new \Membrane\Validator\FieldSet\RequiredFields(...$fields)
```

| Parameter  | Type   |
|------------|--------|
| ...$fields | string |

**Example**

```php
<?php
$requiredFields = new \Membrane\Validator\FieldSet\RequiredFields('a', 'c');
$arrayOfFields = [
        [],
        ['a' => 1],
        ['a' => 1, 'b' => 2],
        ['c' => 3],
        ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5],
    ]

foreach ($arrayOfFields as $fields) {
    $result = $requiredFields->validate($fields);
    
    if ($result->isValid()) {
        echo json_encode($result->value) . ' is valid \n';
    } else {
        echo json_encode($result->value) . ' is invalid \n';
        foreach($result->messageSets[0]->messages as $message) {
            echo '\t' . $message->rendered() . '\n';
        }
    }
}
```

The above example will output the following

```text
{} is invalid
    a is a required field
    c is a required field
{"a":1} is invalid
    c is a required field
{"a":1,"b":2} is invalid
    c is a required field
{"c":3} is invalid
    a is a required field
{"a":1,"b":2,"c":3, "d": 4, "e": 5} is valid
```

## Numeric

### Maximum

Checks if a given value complies with specified maximum.

```php
new \Membrane\Validator\Numeric\Maximum($max, $exclusive)
```

| Parameter  | Type         | Default Value | Notes                                                      |
|------------|--------------|---------------|------------------------------------------------------------|
| $max       | int or float |               |                                                            |
| $exclusive | boolean      | false         | determines whether it is an exclusive or inclusive maximum |

**Example 1 (Inclusive Maximum)**

```php
<?php
$inclusiveMax = new \Membrane\Validator\Numeric\Maximum(10);
$examples = [
    5,
    9.9,
    10,
    10.01
];

foreach ($examples as $example) {
    $result = $inclusiveMax->validate($example);
    
    if ($result->isValid()) {
        echo $result->value . ' is valid \n';
    } else {
        echo $result->value . ' is invalid \n';
        foreach($result->messageSets[0]->messages as $message) {
            echo '\t' . $message->rendered() . '\n';
        }
    }
}
```

The above example will output the following

```text
5 is valid
9.9 is valid
10 is valid
10.01 is invalid
    Number has an exclusive maximum of 10
```

**Example 2 (Exclusive Maximum)**

```php
<?php
$exclusiveMax = new \Membrane\Validator\Numeric\Maximum(10, true);
$examples = [
    5,
    9.9,
    10,
    10.01
];

foreach ($examples as $example) {
    $result = $exclusiveMax->validate($example);
    
    if ($result->isValid()) {
        echo $result->value . ' is valid \n';
    } else {
        echo $result->value . ' is invalid \n';
        foreach($result->messageSets[0]->messages as $message) {
            echo '\t' . $message->rendered() . '\n';
        }
    }
}
```

The above example will output the following

```text
5 is valid
9.9 is valid
10 is invalid
    Number has an exclusive maximum of 10
10.01 is invalid
    Number has an exclusive maximum of 10
```

### Minimum

Checks if a given value complies with specified minimum.

```php
new \Membrane\Validator\Numeric\Minimum($min, $exclusive)
```

| Parameter  | Type         | Default Value | Notes                                                      |
|------------|--------------|---------------|------------------------------------------------------------|
| $min       | int or float |               |                                                            |
| $exclusive | boolean      | false         | determines whether it is an exclusive or inclusive minimum |

**Example 1 (Inclusive Minimum)**

```php
<?php
$inclusiveMin = new \Membrane\Validator\Numeric\Minimum(10);
$examples = [
    5,
    9.9,
    10,
    10.01
];

foreach ($examples as $example) {
    $result = $inclusiveMin->validate($example);
    
    if ($result->isValid()) {
        echo $result->value . ' is valid \n';
    } else {
        echo $result->value . ' is invalid \n';
        foreach($result->messageSets[0]->messages as $message) {
            echo '\t' . $message->rendered() . '\n';
        }
    }
}
```

The above example will output the following

```text
5 is invalid
    Number has an inclusive minimum of 10
9.9 is invalid
    Number has an inclusive minimum of 10
10 is valid
10.01 is valid
```

**Example 2 (Exclusive Maximum)**

```php
<?php
$exclusiveMin = new \Membrane\Validator\Numeric\Minimum(10, true);
$examples = [
    5,
    9.9,
    10,
    10.01
];

foreach ($examples as $example) {
    $result = $exclusiveMin->validate($example);
    
    if ($result->isValid()) {
        echo $result->value . ' is valid \n';
    } else {
        echo $result->value . ' is invalid \n';
        foreach($result->messageSets[0]->messages as $message) {
            echo '\t' . $message->rendered() . '\n';
        }
    }
}
```

The above example will output the following

```text
5 is invalid
    Number has an exclusive minimum of 10
9.9 is invalid
    Number has an exclusive minimum of 10
10 is invalid
    Number has an exclusive minimum of 10
10.01 is valid
```

### MultipleOf

Checks if an integer/float is a multiple of a given value.

```php
new \Membrane\Validator\Numeric\MultipleOf($factor)
```

| Parameter | Type         | Notes                  |
|-----------|--------------|------------------------|
| $factor   | int or float | must be greater than 0 |

**Example**

```php
<?php
$multipleOf = new \Membrane\Validator\Numeric\MultipleOf(5);
$examples = [
    5,
    10.0,
    10,
    10.01
];

foreach ($examples as $example) {
    $result = $multipleOf->validate($example);
    
    if ($result->isValid()) {
        echo $result->value . ' is valid \n';
    } else {
        echo $result->value . ' is invalid \n';
        foreach($result->messageSets[0]->messages as $message) {
            echo '\t' . $message->rendered() . '\n';
        }
    }
}
```

The above example will output the following

```text
5 is valid
10.0 is valid
10 is valid
10.01 is invalid
    Number is expected to be a multiple of 5
```

## String

### BoolString

Validates that input is a boolean string. For an input to be considered valid it MUST be the string "true" or
"false" (case-insensitive).

For boolean values, refer to [IsBool](#isbool)

To convert values to bolean refer to [ToBool](filters.md#tobool).

**Example**

```php
<?php
use Membrane\Validator\String\BoolString;

$examples = [
    '1',
    'true',
    'FALSE',
    'yes',
    true,
    false,
];

$boolString = new BoolString()

foreach ($examples as $example) {
    $result = $boolString->validate($example);

    echo "json_encode($result->value) is" . ($result->isValid() ? 'valid' : 'invalid') . "\n";
    foreach($result->messageSets[0]->messages as $message) {
        echo "\t $message->rendered() \n";
    } 
}
```

The above example will output:

```text
"1" is invalid
  - String value must be a boolean.
"true" is valid
"FALSE" is valid
"yes is invalid"
  - String value must be a boolean.
true is invalid
  - String value expected, boolean provided.
false is invalid
  - String value expected, boolean provided.
```

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

### IntString

Validates that input is a integer string. For an input to be considered valid it MUST be a string, and that string
MUST be an integer.

For non-numeric strings refer to [IsString](#isstring).

For non-string numbers, refer to [IsFloat](#isfloat), [IsInt](#isint) and [IsNumber](#isnumber).

**Example**

```php
<?php
use Membrane\Validator\String\IntString;

$examples = [
    '1',
    '1.0',
    'five',
    5,
];

$intString = new IntString()

foreach ($examples as $example) {
    $result = $intString->validate($example);

    echo "json_encode($result->value) is" . ($result->isValid() ? 'valid' : 'invalid') . "\n";
    foreach($result->messageSets[0]->messages as $message) {
        echo "\t $message->rendered() \n";
    } 
}
```

The above example will output:

```text
"1" is valid
"1.0" is invalid
  - String value must be an integer.
"five" is invalid
  - String value must be an integer.
5 is invalid
  - string value expected, integer provided
```

### NumericString

Validates that input is a numeric string. For an input to be considered valid it MUST be a string, and that string
MUST be a number.

For non-numeric strings refer to [IsString](#isstring).

For non-string numbers, refer to [IsFloat](#isfloat), [IsInt](#isint) and [IsNumber](#isnumber).

**Example**

```php
<?php
use Membrane\Validator\String\NumericString;

$examples = [
    '1',
    '1.0',
    '2.3',
    'five',
    5,
    5.5
];

$numericString = new NumericString()

foreach ($examples as $example) {
    $result = $numericString->validate($example);

    echo "json_encode($result->value) is" . ($result->isValid() ? 'valid' : 'invalid') . "\n";
    foreach($result->messageSets[0]->messages as $message) {
        echo "\t $message->rendered() \n";
    } 
}
```

The above example will output:

```text
"1" is valid
"1.0" is valid
"2.3" is valid
"five" is invalid
  - String value must be numeric
5 is invalid
  - string value expected, integer provided
5.5 is invalid
  - string value expected, double provided
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
