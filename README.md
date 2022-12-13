# Membrane

Membrane is a lightweight input validation layer which can be used to answer the question "Is this input something we
might be able to process?". Its
purpose is to receive "raw" user input and produce a cleaned up, fully validated object or array representing that data,
which the rest of your application
can use with confidence.

It is not intended to be a fully featured validation library, but instead focus on ensuring that the data you have
received looks correct by focusing
on data types, ranges and formats.

To put this in context, membrane could be used to validate that a user submitting a request to edit a blog post has:

- an integer id, greater than zero;
- a string title between 10 and 80 characters;
- a string post body at least 25 characters long;
- a list of no more than 5 string tags, each with a length between 3 and 15 characters.

However, it should not be used to validate that:

- the blog post already exists;
- that the user has permission to edit the blog post;
- that the blog post is unpublished and thus still allowed to be edited.

These are all business rules which should be taken care of in a separate layer.

Membrane will usually be your first line between a web request and your application, so I have made the design descision
that membrane will *NEVER* throw an
exception because of bad user data. Exceptions will only be thrown due to membrane being setup incorrectly by the
developer. As such, you will always get a
result object back when validating user input even if the input was complete garbage. 
