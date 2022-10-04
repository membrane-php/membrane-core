# Filters

Filters ensure that input is the correct format and may attempt
to change the input to match the correct format.  
If you do not wish to change the input: See [Validators](validators.md)

All Filters implement the Membrane\Filter interface:
```php
interface Filter
{
    public function filter(mixed $value): Result;
}
```

Results returning from Filters will always be either noResult or invalid if it
is unable to process the data provided. You should not rely on this behaviour,
common best practice is to use validators to ensure the data passed can be filtered.

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
```
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
```
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

echo $result->value
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```
The above example will output the following
```
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

echo $result->value
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```
The above example will output the following
```
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

echo $result->value
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```
The above example will output the following
```
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

echo $result->value
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```
The above example will output the following
```
['b' => 2, 'c' => 3, 'd' => 1]
Result was valid
```

### Truncate

Deletes as many values as necessary from the end of a list to avoid exceeding the specified maximum length.

```php
new Truncate($maxLength)
```

| Parameter | Type | Notes                          |
|------------|-----|--------------------------------|
| $maxLength | int | Only accepts positive integers |

**Example**

```php
$list = ['a', 'b', 'c']
$truncate = new Truncate(2)

$result = $truncate->filter($list);

echo $result->value
echo $result->isValid() ? 'Result was valid' : 'Result was invalid';
```
The above example will output the following
```
['a', 'b']
Result was valid
```
