# Filters

Filters attempt to change data to the correct format.  
If you do not want to change incoming data; See [Validators](validators.md)

All Filters implement the Membrane\Filter interface:

```php
interface Filter
{
    public function filter(mixed $value): Result;
}
```

[Results](result.md) returned from Filters will always be [Result::NO_RESULT or Result::INVALID](result.md#result).  
Filters cannot validate data, they can only invalidate data.

Best practice is to use Filters in combination with Validators to ensure data is returned in the correct format.

## Create Object

Create objects from external data.

### FromArray

construct new data object from an array. $className must correspond to a class with a method named 'fromArray'

```php
new FromArray($className)
```

| Parameter  | Type   |
|------------|--------|
| $className | string |

**Example**

```php
$classWithMethod = new class () {
    public static function fromArray(array $values): string
    {
        return implode('_', $values);
    }
};

$fromArray = new FromArray(get_class($classWithMethod));

$result = $fromArray->filter(['a' => 'foo', 'b' => 'bar'])

echo $result->value;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
foo_bar
Result was valid
```

### WithNamedArguments

Constructs a new object using its constructor.

This relies upon the named arguments feature in PHP,
so the parameter names of your object must match the keys in the data array.

You can use the shape filters to modify the data structure to match if need be.

```php
new WithNamedArguments($className)
```

| Parameter  | Type   |
|------------|--------|
| $className | string |

**Example**

```php
$classWithNamedArguments = new class (a: 'default', b: 'arguments') {
    public function __construct(public string $a, public string $b)
    {
    }
};

$withNamedArgs = new WithNamedArguments(get_class($classWithNamedArguments));

$result = $withNamedArgs->filter(['a' => 'new', 'b' => 'values']);

echo $result->value->a . ' ' . $result-> value->b;
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
new values
Result was valid
```

## Shape

Methods that alter the shape of arrays/lists.

### Collect

Collect key-value pairs specified by $fields from the parent array and
append their values to a nested list specified by $newField.

```php
new Collect($newField, ...$fields)
```

| Parameter  | Type   |
|------------|--------|
| $newField  | string |
| ...$fields | string |

**Example**

```php
$array = ['a' => 1, 'b' => 2, 'c' => 3]
$collect = new Collect('collected fields', 'a', 'c')

$result = $collect->filter($array);

var_export($result->value);
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
['b' => 2, 'collected fields' => [1, 3]]
Result was valid
```

### Delete

Deletes a specified key-value pairs from an array.

```php
new Delete(...$fieldNames)
```

| Parameter      | Type   |
|----------------|--------|
| ...$fieldNames | string |

### Nest

Opposite of Pluck.

Collect key-value pairs specified by $fields from the parent array and
appends them to a nested array specified by $newField.

```php
new Nest($newField, ...$fields)
```

| Parameter  | Type   |
|------------|--------|
| $newField  | string |
| ...$fields | string |

**Example**

```php
$array = ['a' => 1, 'b' => 2, 'c' => 3]
$nest = new Nest('nested fields', 'a', 'c')

$result = $nest->filter($array);

var_export($result->value);
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
['b' => 2, 'nested fields' => ['a' => 1, 'c' => 3]]
Result was valid
```

### Pluck

Opposite of Nest.

Collect key-value pairs specified by $fieldNames from a nested array specified by $fieldSet and
appends them to the parent array.

```php
new Pluck($fieldSet, ...$fieldnames)
```

| Parameter      | Type   |
|----------------|--------|
| $fieldSet      | string |
| ...$fieldNames | string |

**Example**

```php
$array = ['b' => 2, 'nested fields' => ['a' => 1, 'c' => 3]]
$pluck = new Pluck('nested fields', 'a', 'c')

$result = $pluck->filter($array);

var_export($result->value);
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
['a' => 1, 'b' => 2, 'c' => 3, 'nested fields' => ['a' => 1, 'c' => 3]]
Result was valid
```

### Rename

Renames a specified string key in an array.

```php
new Rename($old, $new)
```

| Parameter | Type   | Notes               |
|-----------|--------|---------------------|
| $old      | string | Must not equal $new |
| $new      | string | Must not equal $old |

**Example**

```php
$array = ['a' => 1, 'b' => 2, 'c' => 3]
$rename = new Rename('a', 'd')

$result = $rename->filter($array);

var_export($result->value);
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
['b' => 2, 'c' => 3, 'd' => 1]
Result was valid
```

### Truncate

Deletes as many values as necessary from the end of a list to avoid exceeding the specified maximum length.

```php
new Truncate($maxLength)
```

| Parameter  | Type | Notes                          |
|------------|------|--------------------------------|
| $maxLength | int  | Only accepts positive integers |

**Example**

```php
$list = ['a', 'b', 'c']
$truncate = new Truncate(2)

$result = $truncate->filter($list);

var_export($result->value);
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
['a', 'b']
Result was valid
```

## String

### JsonDecode

Filters a string into a json object, as long as it follows json format.

```php
new JsonDecode()
```

**Example**

```php
$json = '{"id": 1, "name": "Spike", "type": "dog"}'
$jsonDecode = new JsonDecode();

$result = $jsonDecode->filter($json);

echo json_encode($result->value, JSON_PRETTY_PRINT);
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
{
    "id": 1,
    "name": "Spike",
    "type": "dog"
}
Result was valid
```

## Type

### ToBool

Filters scalar values that can represent boolean into boolean values.

```php
new ToBool()
```

**Example**

```php
$toBool = new ToBool();
$values = [1, 'false', 'on', 'off']

foreach($values as $value) {
$result = $toBool->filter($value);
echo (string)$value, 'becomes ';
echo $result->value ? 'true' : 'false';
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
}
```

The above example will output the following

```text
1 becomes true
Result was valid
false becomes false
Result was valid
on becomes true
Result was valid
off becomes false
Result was valid
```

### ToDateTime

Filter strings into DateTime/DateTimeImmutable objects.

```php
new ToDateTime($format, $immutable)
```

| Parameter  | Type   | Default | Notes                                              |
|------------|--------|---------|----------------------------------------------------|
| $format    | string |         | A DateTime format i.e. 'Y-m-d'                     |
| $immutable | bool   | true    | Set to true if you want a DateTimeImmutable object |

**Example**

```php
$toDateTime = new ToDateTime('Y-m-d');
$value = '1970-01-01';

$result = $toDateTime->filter($value);
echo $result->value->format('d-M-y'), "\n";
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```

The above example will output the following

```text
01-jan-1970
Result was valid
```

### ToFloat

Filters value to float. Works for null and scalar values (excluding non-numeric strings).

```php
new ToFloat()
```

**Example**

```php
$toFloat = new ToFloat();
$values = [12, '12', '1.2', true, null]

foreach($values as $value) {
    $result = $toFloat->filter($value);
    var_dump($value);
    echo 'Becomes ', $result->value;
    echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
}
```

The above example will output the following

```text
int(12)
Becomes 12.0
Result was valid
string(2) "12"
Becomes 12.0
Result was valid
string(3) "1.2"
Becomes 1.2
Result was valid
bool(true)
Becomes 1.0
Result was valid
NULL
Becomes 0.0
Result was valid
```

### ToInt

Filters value to integer. Works for null and scalar values (excluding non-numeric strings).

```php
new ToInt()
```

**Example**

```php
$toInt = new ToInt();
$values = [12, '12', '1.2', true, null]

foreach($values as $value) {
    $result = $toInt->filter($value);
    var_dump($value);
    echo 'Becomes ', $result->value;
    echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
}
```

The above example will output the following

```text
int(12)
Becomes 12
Result was valid
string(2) "12"
Becomes 12
Result was valid
string(3) "1.2"
Becomes 1
Result was valid
bool(true)
Becomes 1
Result was valid
NULL
Becomes 0
Result was valid
```

### ToList

Filters value to a list. Works for list and array values.

```php
new ToList()
```

```php
$toList = new ToList();
$values = [
    [],
    [1, 2, 3],
    ['a' => 1, 'b' => 2, 'c' => 3]
]

foreach($values as $value) {
    $result = $toList->filter($value);
    var_export($value);
    echo 'Becomes';
    var_export($result->value);
    echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
}
```

The above example will output the following

```text
[]
Becomes
[]
Result was valid
[1, 2, 3]
Becomes
[1, 2, 3]
Result was valid
['a' => 1, 'b' => 2, 'c' => 3]
Becomes
[1, 2, 3]
Result was valid
```

### ToNumber

Filters value to a float/integer. Works for integers, floats and numeric strings.

```php
new ToNumber()
```

**Example**

```php
$toNumber = new ToNumber();
$values = [12, 1.2, '12', '1.2']

foreach($values as $value) {
    $result = $toNumber->filter($value);
    var_dump($value);
    echo 'Becomes the ', gettype($result->value), ' value: ', $result->value;
    echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
}
```

The above example will output the following

```text
int(12)
Becomes the integer value 12
Result was valid
float(1.2)
Becomes the double value 1.2
Result was valid
string(2) "12"
Becomes the integer value 12
Result was valid
string(3) "1.2"
Becomes the double value 1.2
Result was valid
```

### ToString

Filters value to string. Works on null or scalar values, also works on classes implementing the __toString() method.

```php
new ToString()
```

**Example**

```php
$toString = new ToString();
$values = ['string', 12, 1.2, true, null,]

foreach($values as $value) {
    $result = $toString->filter($value);
    var_dump($value);
    echo 'Becomes the ', gettype($result->value), ' value: "', $result->value, '"';
    echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
}
```

The above example will output the following

```text
string(6) "string"
Becomes the string value "string"
Result was valid
int(12)
Becomes the string value "12"
Result was valid
float(1.2)
Becomes the string value "1.2"
Result was valid
bool(true)
Becomes the string value "1"
Result was valid
NULL
Becomes the string value ""
Result was valid
```
