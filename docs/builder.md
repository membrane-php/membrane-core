# The Builder

Membrane's Processors can contain any number of Validators,Filters, or even more Processors.
You can set this up manually, or you can make use of the Builder.

The Builder is here to simplify the creation of Processors, by providing it with a class and your desired attributes
it can create processors to your specification.

## Examples

### Blog Post

Use-Case: A user wishes to make a blog post.

#### Required Fields
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
$builder = new Builder();
$processor = $builder->fromClass(BlogPost::class);

$resultA = $processor->process(['title' => 'My Post', 'body' => 'My content']);
$resultB = $processor->process(['title' => 123, 'body' => '']);
$resultC = $processor->process(['title' => 'My Post']);

echo $resultA->value; // ['title' => 'My Post', 'body' => 'My content']
var_dump($resultA->isValid()); // bool(true)

echo $resultB->value; // ['title' => 123, 'body' => '']
var_dump($resultB->isValid()); // bool(true)

echo $resultC->messageSets[0]->messages[0]->rendered(); // body is a required field
var_dump($resultC->isValid()); // bool(false)
```

In this example the result will be valid as long as it contains a title and body, 
it does not check for tags.

It does not currently care what the title and body are, as long as they exist.  
This is an issue because our BlogPost class requires string values.

#### Is It A String?

```php
#[SetFilterOrValidator(new RequiredFields('title', 'body', 'tags'), Placement::BEFORE)]
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
$builder = new Builder();
$processor = $builder->fromClass(BlogPost::class);

$resultA = $processor->process(
        [
            'title' => 'My Post',
            'body' => 'My content',
            'tags' => ['tag1', 'tag2'],
        ]
    );
      
$resultB = $processor->process(
        [
            'title' => 123,
            'body' => 'My content',
            'tags' => ['tag1', 'tag2'],
        ]
    );

echo $resultA->value; // ['title' => 'My Post', 'body' => 'My content', 'tags' => ['tag1', 'tag2']]
var_dump($resultA->isValid()); // bool(true)

echo $resultB->messageSets[0]->messages[0]->rendered();
// Value passed to IsString validator is not a string, integer passed instead
var_dump($resultB->isValid()); // bool(false)
```

Now the input must be a string,
we can safely assume our valid result contains data which can create a BlogPost object.

But is that too strict?  

If we use a filter instead, we can try to change the data. We could check if we can turn it into a string ourselves, before we flag the input as invalid.

#### Can We Make It A String?

```php
#[SetFilterOrValidator(new RequiredFields('title', 'body', 'tags'), Placement::BEFORE)]
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
$builder = new Builder();
$processor = $builder->fromClass(BlogPost::class);

$resultA = $processor->process(
        [
            'title' => 123,
            'body' => 'My content',
            'tags' => ['tag1', 'tag2', 'tag3', 'tag4', 'tag5', 'tag6', 'tag7'],
        ]
    );
      
$resultB = $processor->process(
        [
            'title' => [1, 2, 3],
            'body' => 'My content',
            'tags' => ['tag1', 'tag2', 'tag3', 'tag4', 'tag5', 'tag6', 'tag7']],
        ]
    );

echo $resultA->value; 
// ['title' => '123', 'body' => 'My content', 'tags' => ['tag1', 'tag2', 'tag3', 'tag4', 'tag5', 'tag6', 'tag7']]
var_dump($resultA->isValid()); // bool(true)

echo $resultB->messageSets[0]->messages[0]->rendered();
// ToString filter only accepts objects, null or scalar values, array given
var_dump($resultB->isValid()); // bool(false)
```

Brilliant! It's a bit more flexible now but our users are getting a bit tag-happy.
It would be nice to make sure they stick to 5 tags or fewer.

#### Maximum Number Of Tags

```php
#[SetFilterOrValidator(new RequiredFields('title', 'body', 'tags'), Placement::BEFORE)]
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
$builder = new Builder();
$processor = $builder->fromClass(BlogPost::class);

$resultA = $processor->process(
        [
            'title' => '',
            'body' => 'My content',
            'tags' => ['tag1', 'tag2', 'tag3', 'tag4'],
        ]
    );
      
$resultB = $processor->process(
        [
            'title' => 'TITLE WRITTEN ENTIRELY IN UPPER CASE AND UNNECESSARILY LONG',
            'body' => 'My content',
            'tags' => ['tag1', 'tag2', 'tag3', 'tag4', 'tag5', 'tag6', 'tag7']],
        ]
    );

echo $resultA->value; 
// ['title' => '', 'body' => 'My content', 'tags' => ['tag1', 'tag2', 'tag3', 'tag4']]
var_dump($resultA->isValid()); // bool(true)

echo $resultB->messageSets[0]->messages[0]->rendered();
// Array is expected have a maximum of 5 values
var_dump($resultB->isValid()); // bool(false)
```

Okay that should keep those tags under control, but what is going on with these titles? 
We're going to need some structure.

#### Regex and Max Length

```php
#[SetFilterOrValidator(new RequiredFields('title', 'body', 'tags'), Placement::BEFORE)]
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
$builder = new Builder();
$processor = $builder->fromClass(BlogPost::class);

$resultA = $processor->process(
        [
            'title' => 'Title With Proper Capitalization',
            'body' => 'My content',
            'tags' => ['tag1', 'tag2', 'tag3', 'tag4'],
        ]
    );
      
$resultB = $processor->process(
        [
            'title' => 'TITLE WRITTEN ENTIRELY IN UPPER CASE AND UNNECESSARILY LONG',
            'body' => 'My content',
            'tags' => ['tag1', 'tag2', 'tag3', 'tag4']],
        ]
    );

echo $resultA->value; 
// ['title' => 'Title With Proper Capitalization', 'body' => 'My content', 'tags' => ['tag1', 'tag2', 'tag3', 'tag4']]
var_dump($resultA->isValid()); // bool(true)

echo $resultB->messageSets[0]->messages[0]->rendered();
// String is expected to be a maximum of 50 characters
var_dump($resultB->isValid()); // bool(false)
```

Perfect, now our titles have proper capitalization and must be between 1 and 10 words thanks to the Regex Validator.
They must also be 5-50 characters long thanks to Length Filter.

However, we only got the error for the Length Validator since that applied first. 
It would be better if we could find out all the errors at once.

#### List All The Errors

```php
#[SetFilterOrValidator(new RequiredFields('title', 'body', 'tags'), Placement::BEFORE)]
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
$builder = new Builder();
$processor = $builder->fromClass(BlogPost::class);

$resultA = $processor->process(
        [
            'title' => 'Title With Proper Capitalization',
            'body' => 'My content',
            'tags' => ['tag1', 'tag2', 'tag3', 'tag4'],
        ]
    );
      
$resultB = $processor->process(
        [
            'title' => 'TITLE WRITTEN ENTIRELY IN UPPER CASE AND UNNECESSARILY LONG',
            'body' => 'My content',
            'tags' => ['tag1', 'tag2', 'tag3', 'tag4']],
        ]
    );

echo $resultA->value; 
// ['title' => 'Title With Proper Capitalization', 'body' => 'My content', 'tags' => ['tag1', 'tag2', 'tag3', 'tag4']]
var_dump($resultA->isValid()); // bool(true)

echo $resultB->messageSets[0]->messages[0]->rendered();
// String is expected to be a maximum of 50 characters
echo $resultB->messageSets[0]->messages[1]->rendered();
// String does not match the required pattern ^([A-Z][a-z]*\s){1,10}$
var_dump($resultB->isValid()); // bool(false)
```

Brilliant, now if a user makes an error with their title, 
we can provide them all the detail they need to fix it immediately.

Finally, once we're happy with the input, we'll want to create our BlogPost data object.

#### Build Your Blog Post From Named Arguments

```php
#[SetFilterOrValidator(new RequiredFields('title', 'body', 'tags'), Placement::BEFORE)]
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
$builder = new Builder();
$processor = $builder->fromClass(BlogPost::class);

$result = $processor->process(
        [
            'title' => 'Title With Proper Capitalization',
            'body' => 'My content',
            'tags' => ['tag1', 'tag2', 'tag3', 'tag4'],
        ]
    );
    
$blogPost = null;

if($result->isValid()) {
    $blogPost = $result->value;
}

echo $blogPost?->title; // Title With Proper Capitalization
echo $blogPost?->body; // My content
echo $blogPost?->tags; // ['tag1', 'tag2', 'tag3', 'tag4']
```
