# The Builder

Membrane's Processors can contain any number of Validators,Filters, or even more Processors.
You can set this up manually, or you can make use of the Builder.

The Builder is here to simplify the creation of Processors, by providing it with a class and your desired attributes
it can create processors to your specification.

## Examples

### Blog Post

Use-Case: A user wishes to make a blog post. A blog post must contain a title and a body.

#### Required Fields
```php
#[SetFilterOrValidator(new RequiredFields('title', 'body', 'tags'), Placement::BEFORE)]
class BlogPost
{
    public function __construct(
        public string $title,
        public string $body,
        #[Subtype('string')]
        public array $tags,
    ) {
    }

}
```

```php
$builder = new Builder();
$processor = $builder->fromClass(BlogPost::class);

$input = ['title' => 'My Post', 'body' => 'My content', 'tags' => ['tag1']]
$result = $processor->process($input)

echo $result->value; // ['title' => 'My Post', 'body' => 'My content', 'tags' => ['tag1']]
var_dump($result->isValid()); // true

$input = ['title' => 'My Post', 'body' => 'My content']
$result = $processor->process($input)

echo $result->messageSets[0]->messages[0]->rendered(); // tags is a required field
var_dump($result->isValid()); // false
```

In this example the input contains a title and a body, 
it will return a valid result as all required fields have been filled.

Currently, this only checks that an input exists for both fields, but we may want to check these inputs are strings.

#### Is It A String?

```php
#[SetFilterOrValidator(new RequiredFields('title', 'body', 'tags'), Placement::BEFORE)]
class BlogPost
{
    public function __construct(
        #[SetFilterOrValidator(new IsString(), Placement::BEFORE)]
        public string $title,
        #[SetFilterOrValidator(new IsString(), Placement::BEFORE)]
        public string $body,
        #[FilterOrValidator(new IsString())]
        #[Subtype('string')]
        public array $tags,
    ) {
    }

}
```

```php
$builder = new Builder();
$processor = $builder->fromClass(BlogPost::class);

$input = ['title' => 'My Post', 'body' => 'My content', 'tags' => ['tag1', 'tag2']]
$result = $processor->process($input)

echo $result->value; // ['title' => 'My Post', 'body' => 'My content', 'tags' => ['tag1', 'tag2']]
var_dump($result->isValid()); // true

$input = ['title' => 123, 'body' => 'My content', 'tags' => ['tag1', 'tag2', 'tag3']]
$result = $processor->process($input)

echo $result->messageSets[0]->messages[0]->rendered();
// Value passed to IsString validator is not a string,integer passed instead
var_dump($result->isValid()); // false
```

Now the input must be a string, but what if that is too strict? 
We could instead check if we can turn it into a string ourselves, before we flag the input as invalid.

#### Can We Make It A String?

```php
#[SetFilterOrValidator(new RequiredFields('title', 'body', 'tags'), Placement::BEFORE)]
class BlogPost
{
    public function __construct(
        #[SetFilterOrValidator(new ToString(), Placement::BEFORE)]
        public string $title,
        #[SetFilterOrValidator(new ToString(), Placement::BEFORE)]
        public string $body,
        #[FilterOrValidator(new ToString())]
        #[Subtype('string')]
        public array $tags,
    ) {
    }

}
```

```php
$builder = new Builder();
$processor = $builder->fromClass(BlogPost::class);

$input = ['title' => 123, 'body' => 'My content', 'tags' => ['tag1', 'tag2', 'tag3', 'tag4', 'tag5', 'tag6', 'tag7']]
$result = $processor->process($input)

echo $result->value; 
// ['title' => '123', 'body' => 'My content', 'tags' => ['tag1', 'tag2', 'tag3', 'tag4', 'tag5', 'tag6', 'tag7']]
var_dump($result->isValid()); // true

$input = [
    'title' => [1, 2, 3],
    'body' => 'My content',
    'tags' => ['tag1', 'tag2', 'tag3', 'tag4', 'tag5', 'tag6', 'tag7']]
$result = $processor->process($input)

echo $result->messageSets[0]->messages[0]->rendered();
// ToString filter only accepts objects, null or scalar values, array given
var_dump($result->isValid()); // false
```

Brilliant! It's a bit more flexible now but our users are starting to get a bit tag-happy here.
It would be nice to make sure they stick to 5 tags or fewer.

#### Maximum Number Of Tags

```php
#[SetFilterOrValidator(new RequiredFields('title', 'body', 'tags'), Placement::BEFORE)]
class BlogPost
{
    public function __construct(
        #[SetFilterOrValidator(new ToString(), Placement::BEFORE)]
        public string $title,
        #[SetFilterOrValidator(new ToString(), Placement::BEFORE)]
        public string $body,
        #[SetFilterOrValidator(new Count(0, 5), Placement::BEFORE)]
        #[FilterOrValidator(new ToString())]
        #[Subtype('string')]
        public array $tags,
    ) {
    }
}
```

```php
$builder = new Builder();
$processor = $builder->fromClass(BlogPost::class);

$input = ['title' => 'TITLE IN UPPER CASE', 'body' => 'My content', 'tags' => ['tag1', 'tag2', 'tag3', 'tag4']]
$result = $processor->process($input)

echo $result->value; 
// ['title' => 'title in lower case', 'body' => 'My content', 'tags' => ['tag1', 'tag2', 'tag3', 'tag4']]
var_dump($result->isValid()); // true

$input = [
    'title' => '',
    'body' => 'My content',
    'tags' => ['tag1', 'tag2', 'tag3', 'tag4', 'tag5', 'tag6', 'tag7']]
$result = $processor->process($input)

echo $result->messageSets[0]->messages[0]->rendered();
// Array is expected have a maximum of 5 values
var_dump($result->isValid()); // false
```

Okay that should keep those tags under control, but what is going on with these titles? 
We're going to need some more structure.

```php
#[SetFilterOrValidator(new RequiredFields('title', 'body', 'tags'), Placement::BEFORE)]
class BlogPost
{
    public function __construct(
        #[SetFilterOrValidator(new ToString(), Placement::BEFORE)]
        #[SetFilterOrValidator(new Regex('^([A-Z][a-z]*\s){1,10}$'), Placement::AFTER)]
        public string $title,
        #[SetFilterOrValidator(new ToString(), Placement::BEFORE)]
        public string $body,
        #[SetFilterOrValidator(new Count(0, 5), Placement::BEFORE)]
        #[FilterOrValidator(new ToString())]
        #[Subtype('string')]
        public array $tags,
    ) {
    }
}
```

```php
$builder = new Builder();
$processor = $builder->fromClass(BlogPost::class);

$input = ['title' => 'Title With Proper Capitalization', 'body' => 'My content', 'tags' => ['tag1', 'tag2', 'tag3', 'tag4']]
$result = $processor->process($input)

echo $result->value; 
// ['title' => 'Title With Proper Capitalization', 'body' => 'My content', 'tags' => ['tag1', 'tag2', 'tag3', 'tag4']]
var_dump($result->isValid()); // true

$input = [
    'title' => '',
    'body' => 'My content',
    'tags' => ['tag1', 'tag2', 'tag3', 'tag4', 'tag5', 'tag6', 'tag7']]
$result = $processor->process($input)

echo $result->messageSets[0]->messages[0]->rendered();
// String does not match the required pattern ^([A-Z][a-z]*\s){1,10}$
var_dump($result->isValid()); // false
```

Perfect, now our titles have proper capitilization and must be between 1 and 10 words. 
But there's no limit on the number of characters yet, our title could use a character limit.
