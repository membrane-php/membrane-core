# Building From Attributes

## Specification

```php
ClassWithAttributes($className)
```

This Specification takes a class representing the data you wish to receive.

| Parameter  | Type   | Notes                                                  |
|------------|--------|--------------------------------------------------------|
| $className | string | The class-string of the data object you wish to create |

Add attributes to determine how Membrane will process it.

## Example

Use-Case: A user wishes to make a blog post.

Let's start a BlogPost class to hold the user's data.

```php
class BlogPost
{
    public function __construct(
        public string $title,
        public string $body,
        #[Subtype('string')]
        public array $tags = []
    ) {
    }
}
```

Okay brilliant, we've got a minimal example, note the Subtype attribute on the $tags array.  
This is required, Membrane will not accept arrays without defined subtypes.

```php
$specification = new ClassWithAttributes(BlogPost::class);
$membrane = new Membrane();
$examples = [
    ['title' => 'My Post', 'body' => 'My content'],
    [],
];

foreach ($examples as $example) {
    $result = $membrane->process($example, $specification);
    var_dump($result->value);
    if ($result->isValid()) {
        echo ' is valid';
    } else {
        echo ' is invalid' . "\n";
        foreach($result->messageSets as $messageSet) {
            foreach ($messageSet->messages as $message) {
                echo $message->rendered() . "\n";
            }
        }
    }
    echo "\n";
}
```

Outputs

```text
['title' => 'My Post', 'body' => 'My content'] is valid
[] is valid
```

The first example is valid, that's great. The second example has no input, we shouldn't be accepting that; we'll make
sure the user MUST provide required fields.

### Required Fields

```php
#[SetFilterOrValidator(new RequiredFields('title', 'body'), Placement::BEFORE)]
class BlogPost
{
    public function __construct(
        public string $title,
        public string $body,
        #[Subtype('string')]
        public array $tags = [],
    ) {
    }
}
```

To build a BlogPost, **before** everything else: a title and body are required.

```php
$specification = new ClassWithAttributes(BlogPost::class);
$membrane = new Membrane();
$examples = [
    ['title' => false, 'body' => null],
    [],
];

foreach ($examples as $example) {
    $result = $membrane->process($example, $specification);
    var_dump($result->value);
    if ($result->isValid()) {
        echo ' is valid';
    } else {
        echo ' is invalid' . "\n";
        foreach($result->messageSets as $messageSet) {
            foreach ($messageSet->messages as $message) {
                echo $message->rendered() . "\n";
            }
        }
    }
    echo "\n";
}
```

Outputs

```text
['title' => false, 'body' => null] is valid

[] is invalid
title is a required field
body is a required field

```

The results are valid as long as it contains a title and body, but it does not currently care what the title and body
are. But our BlogPost class requires strings, certainly not nulls or booleans. So we need another attribute.

### Is It A String?

```php
#[SetFilterOrValidator(new RequiredFields('title', 'body'), Placement::BEFORE)]
class BlogPost
{
    public function __construct(
        #[FilterOrValidator(new IsString())]
        public string $title,
        #[FilterOrValidator(new IsString())]
        public string $body,
        #[FilterOrValidator(new IsString())]
        #[Subtype('string')]
        public array $tags,
    ) {
    }
}
```

To build a BlogPost, **before** everything else: a title and body are required.

The title must be a string.

The body must be a string.

If tags are provided, they must be a string.

```php
$specification = new ClassWithAttributes(BlogPost::class);
$membrane = new Membrane();
$examples = [
    ['title' => 'My Post', 'body' => 'My content'],
    ['title' => false, 'body' => null],
];

foreach ($examples as $example) {
    $result = $membrane->process($example, $specification);
    var_dump($result->value);
    if ($result->isValid()) {
        echo ' is valid';
    } else {
        echo ' is invalid' . "\n";
        foreach($result->messageSets as $messageSet) {
            foreach ($messageSet->messages as $message) {
                echo $message->rendered() . "\n";
            }
        }
    }
    echo "\n";
}
```

Outputs

```text
['title' => 'My Post', 'body' => 'My content'] is valid
['title' => false, 'body' => null] is invalid
IsString validator expects string value, boolean passed instead
IsString validator expects string value, NULL passed instead
```

Now the input must be a string,
we can safely assume our valid result contains data which can create a BlogPost object.

But is that too strict?

If we use a filter instead, we can try to change the data. We could check if we can turn it into a string ourselves,
before we flag the input as invalid.

### Can We Make It A String?

```php
#[SetFilterOrValidator(new RequiredFields('title', 'body'), Placement::BEFORE)]
class BlogPost
{
    public function __construct(
        #[FilterOrValidator(new ToString())]
        public string $title,
        #[FilterOrValidator(new ToString())]
        public string $body,
        #[FilterOrValidator(new ToString())]
        #[Subtype('string')]
        public array $tags,
    ) {
    }
}
```

To build a BlogPost, **before** everything else: a title and body are required.

The title must be convertable to a string.

The body must be convertable to a string.

If tags are provided, they must be convertable to a string.

```php
$specification = new ClassWithAttributes(BlogPost::class);
$membrane = new Membrane();
$examples = [
    ['title' => false, 'body' => null],
    ['title' => ['a', 'b'], 'body' => 'My content'],
];

foreach ($examples as $example) {
    $result = $membrane->process($example, $specification);
    var_dump($result->value);
    if ($result->isValid()) {
        echo ' is valid';
    } else {
        echo ' is invalid' . "\n";
        foreach($result->messageSets as $messageSet) {
            foreach ($messageSet->messages as $message) {
                echo $message->rendered() . "\n";
            }
        }
    }
    echo "\n";
}
```

Ouputs

```text
    ['title' => false, 'body' => null] is valid
    ['title' => ['a', 'b'], 'body' => 'My content'] is invalid
    ToString filter only accepts objects, null or scalar values, array given
```

Brilliant! It's a bit more flexible, but we can still rest assured a valid Result contains string values. Let's add some
more control to our tags.

### Maximum Number Of Tags

```php
#[SetFilterOrValidator(new RequiredFields('title', 'body'), Placement::BEFORE)]
class BlogPost
{
    public function __construct(
        #[FilterOrValidator(new ToString())]
        public string $title,
        #[FilterOrValidator(new ToString())]
        public string $body,
        #[SetFilterOrValidator(new Count(0, 5), Placement::BEFORE)]
        #[FilterOrValidator(new ToString())]
        #[Subtype('string')]
        public array $tags,
    ) {
    }
}
```

To build a BlogPost, **before** everything else: a title and body are required.

The title must be convertable to a string.

The body must be convertable to a string.

If tags are provided, they must be convertable to a string but
**before** that it will check the number of tags does not exceed 5.

```php
$specification = new ClassWithAttributes(BlogPost::class);
$membrane = new Membrane();
$examples = [
    ['title' => '', 'body' => '', 'tags' => ['a', 'b', 'c']],
    ['title' => '', 'body' => '', 'tags' => ['a', 'b', 'c', 'd', 'e', 'f']],
];

foreach ($examples as $example) {
    $result = $membrane->process($example, $specification);
    var_dump($result->value);
    if ($result->isValid()) {
        echo ' is valid';
    } else {
        echo ' is invalid' . "\n";
        foreach($result->messageSets as $messageSet) {
            foreach ($messageSet->messages as $message) {
                echo $message->rendered() . "\n";
            }
        }
    }
    echo "\n";
}
```

Outputs

```text
    ['title' => '', 'body' => '', 'tags' => ['a', 'b', 'c']] is valid
    ['title' => '', 'body' => '', 'tags' => ['a', 'b', 'c', 'd', 'e', 'f']] is invalid
    Array is expected have a maximum of 5 values
```

Okay that should keep those tags under control, but what is going on with these titles?
We're going to need some structure.

### Regex and Max Length

```php
#[SetFilterOrValidator(new RequiredFields('title', 'body'), Placement::BEFORE)]
class BlogPost
{
    public function __construct(
        #[FilterOrValidator(new ToString())]
        #[FilterOrValidator(new Length(5, 50))]
        #[FilterOrValidator(new Regex('#^([A-Z][a-z]*\s){0,9}([A-Z][a-z]*)$#'))]
        public string $title,
        #[FilterOrValidator(new ToString())]
        public string $body,
        #[SetFilterOrValidator(new Count(0, 5), Placement::BEFORE)]
        #[FilterOrValidator(new ToString())]
        #[Subtype('string')]
        public array $tags,
    ) {
    }
}
```

To build a BlogPost, **before** everything else: a title and body are required.

The title must be convertable to a string, between 5-50 characters and match the regular expression provided.

The body must be convertable to a string.

If tags are provided, they must be convertable to a string but
**before** that it will check the number of tags does not exceed 5.

```php
$specification = new ClassWithAttributes(BlogPost::class);
$membrane = new Membrane();
$examples = [
    ['title' => 'My Title', 'body' => '', 'tags' => ['a', 'b', 'c']],
    ['title' => 'mY tItLe tHat iS uNnEcEsSaRiLlY lOnG wItH InCoRrEcT cApItIlIzAtIoN', 'body' => '', 'tags' => ['a', 'b', 'c']],
];

foreach ($examples as $example) {
    $result = $membrane->process($example, $specification);
    var_dump($result->value);
    if ($result->isValid()) {
        echo ' is valid';
    } else {
        echo ' is invalid' . "\n";
        foreach($result->messageSets as $messageSet) {
            foreach ($messageSet->messages as $message) {
                echo $message->rendered() . "\n";
            }
        }
    }
    echo "\n";
}
```

Outputs

```text
    ['title' => 'My Title', 'body' => '', 'tags' => ['a', 'b', 'c']] is valid
    ['title' => 'mY tItLe tHat iS uNnEcEsSaRiLlY lOnG wItH InCoRrEcT cApItIlIzAtIoN', 'body' => '', 'tags' => ['a', 'b', 'c']] is invalid
    String is expected to be a maximum of 50 characters
```

Perfect, now our titles have proper capitalization and must be between 1 and 10 words thanks to the Regex Validator.
They must also be 5-50 characters long thanks to Length Filter.

However, we only got the error for the Length Validator since that applied first.
It would be better if we could find out all the errors at once.

### List All The Errors

```php
#[SetFilterOrValidator(new RequiredFields('title', 'body'), Placement::BEFORE)]
class BlogPost
{
    public function __construct(
        #[FilterOrValidator(new ToString())]
        #[FilterOrValidator(new AllOf(new Length(5,50), new Regex('#^([A-Z][a-z]*\s){0,9}([A-Z][a-z]*)$#')))]
        public string $title,
        #[FilterOrValidator(new ToString())]
        public string $body,
        #[SetFilterOrValidator(new Count(0, 5), Placement::BEFORE)]
        #[FilterOrValidator(new ToString())]
        #[Subtype('string')]
        public array $tags,
    ) {
    }
}
```

To build a BlogPost, **before** everything else: a title and body are required.

The title must be convertable to a string and **all of the following:**

* between 5-50 characters
* match the regular expression provided

The body must be convertable to a string.

If tags are provided, they must be convertable to a string but
**before** that it will check the number of tags does not exceed 5.

```php
$specification = new ClassWithAttributes(BlogPost::class);
$membrane = new Membrane();
$examples = [
    ['title' => 'My Title', 'body' => '', 'tags' => ['a', 'b', 'c']],
    ['title' => 'mY tItLe tHat iS uNnEcEsSaRiLlY lOnG wItH InCoRrEcT cApItIlIzAtIoN', 'body' => '', 'tags' => ['a', 'b', 'c']],
];

foreach ($examples as $example) {
    $result = $membrane->process($example, $specification);
    var_dump($result->value);
    if ($result->isValid()) {
        echo ' is valid';
    } else {
        echo ' is invalid' . "\n";
        foreach($result->messageSets as $messageSet) {
            foreach ($messageSet->messages as $message) {
                echo $message->rendered() . "\n";
            }
        }
    }
    echo "\n";
}
```

```text
['title' => 'My Title', 'body' => '', 'tags' => ['a', 'b', 'c']] is valid
['title' => 'mY tItLe tHat iS uNnEcEsSaRiLlY lOnG wItH InCoRrEcT cApItIlIzAtIoN', 'body' => '', 'tags' => ['a', 'b', 'c']] is invalid
String is expected to be a maximum of 50 characters
String does not match the required pattern ^([A-Z][a-z]*\s){1,10}$
```

Brilliant, now if a user makes an error with their title,
we can provide them all the detail they need to fix it immediately.

Finally, once we're happy with the input, we'll want to create our BlogPost data object.

### Build Your Blog Post From Named Arguments

```php
#[SetFilterOrValidator(new RequiredFields('title', 'body'), Placement::BEFORE)]
#[SetFilterOrValidator(new WithNamedArguments(BlogPost::class), Placement::AFTER)]
class BlogPost
{
    public function __construct(
        #[FilterOrValidator(new ToString())]
        #[FilterOrValidator(new AllOf(new Length(5,50), new Regex('#^([A-Z][a-z]*\s){0,9}([A-Z][a-z]*)$#')))]
        public string $title,
        #[FilterOrValidator(new ToString())]
        public string $body,
        #[SetFilterOrValidator(new Count(0, 5), Placement::BEFORE)]
        #[FilterOrValidator(new ToString())]
        #[Subtype('string')]
        public array $tags,
    ) {
    }
}
```

To build a BlogPost, **before** everything else: a title and body are required.

The title must be convertable to a string and **all of the following:**

* between 5-50 characters
* match the regular expression provided

The body must be convertable to a string.

If tags are provided, they must be convertable to a string but
**before** that it will check the number of tags does not exceed 5.

**After** everything else: build a BlogPost from named arguments.

```php
$specification = new ClassWithAttributes(BlogPost::class);
$data = [
    'title' => 'My Title',
    'body' => 'My content',
    'tags' => ['tag1', 'tag2', 'tag3', 'tag4'],
];
$membrane = new Membrane();

$result = $membrane->process($data, $specification);
    
$blogPost = $result->isValid() ? $result->value : null;

echo $blogPost?->title; // My Title
echo $blogPost?->body; // My content
echo $blogPost?->tags; // ['tag1', 'tag2', 'tag3', 'tag4']
```

Now you've successfully validated data to ensure you can safely create your BlogPost class.
