# Creating Resources

A resource can be created by sending a `POST` request to the URL that represents the collection
of resources. For example, a new `posts` resource might be created with the following request:

```php
POST /posts HTTP/1.1
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json

{
    "data": {
        "type": "posts",
        "attributes": {
            "title": "Hello World",
            "content": "..."
        },
        "relationships": {
            "tags": {
                "data": [
                    {
                        "type": "tags",
                        "id": "1"
                    }
                ]
            }
        }
    }
}
``` 

This will result in the following response (depending on what is in your `posts` `Schema` class):

```php
HTTP/1.1 201 Created
Location: http://example.com/api/posts/1
Content-Type: application/vnd.api+json

{
    "data": {
        "type": "posts",
        "id": "1",
        "attributes": {
            "title": "Hello World",
            "content": "..."
        },
        "relationships": {
            "tags": {
                "links": {
                    "self": "http://example.com/api/posts/1/relationships/tags",
                    "related": "http://example.com/api/posts/1/tags"
                }
            }
        },
        "links": {
            "self": "http://example.com/api/posts/1"
        }
    }
}
```

## Requirements

For this to work, you must:

- add the `posts` resource in your API's config, in the `resources` array; and
- register [routes](../basics/routing.md) for your `posts` resource; and
- create the [validators](../basics/validators.md), [adapter](../basics/adapters.md) and
[schema](../basics/schemas.md) classes for the `posts` resource; and
- optionally set up an [authorizer](../basics/security.md) class if you want to authorize the request.

## Supported Query Parameters

The following JSON API query parameters are supported for this request:

- [inclusion of related resources](../fetching/inclusion.md)
- [sparse fieldsets](../fetching/sparse-fieldsets.md)

> Filter, page and sort parameters are not supported because this endpoint returns a single resource
object as its primary data.

## Client Generated IDs

By default the server will reject a request to create a resource with a client-generated id. Any
such requests will receive a `403 Forbidden` response, as defined in the JSON API spec.

However you can easily enable client-generated ids for a resource by adding a rule to your
validators class, and using the `creating` hook on your adapter.

On your validators class, add a validation rule for the `id`. This tells the validators that
it can accept a client-generated id. For example:

```php
class Validators extends AbstractValidators
{
    // ...
    
    protected function rules($record = null): array
    {
        return [
            'id' => 'required|regex:/' . \Ramsey\Uuid\Uuid::VALID_PATTERN . '/',
            // ... other rules
        ];
    }
}
```

> Note that you **do not** need to check whether the id already exists. The JSON API spec
defines that the server must respond with a `409 Conflict` response when processing
a request to create a resource with a client-generated id that already exists. This package
therefore checks any ids provided by the client and sends the `409 Conflict` response if
they already exist.

The final step is to use the `creating` hook in your adapter class to transfer the
client-generated id to your model:

```php
class Adapter extends AbstractAdapter
{

    // ...

    /**
     * @param Video $video
     * @param $resource
     * @return void
     */
    protected function creating(Video $video, $resource)
    {
        $video->{$video->getKeyName()} = $resource['id'];
    }
}
```
