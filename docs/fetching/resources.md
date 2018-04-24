# Fetching Resources

## Introduction

A client can fetch resource objects from a number of different endpoints. It can either query a collection of
resources, fetch a specific resource, or fetch a related resource via a relationship.

The examples in this chapter assume an [API URL namespace](../basics/api.md#URL) of `/api` and the
following [routing](../basics/routing.md):

```php
JsonApi::register('default', ['namespace' => 'Api'], function ($api, $router) {
    $api->resource('posts', [
        'has-one' => 'author',
        'has-many' => 'tags'
    ]);
});
```

All querying of resources is handled via the [Adapter](../basics/adapters.md) class for the resource type that
is subject of the request.

## Resource Collections

The following request fetches a collection of `posts`:

```http
GET /api/posts HTTP/1.1
Accept: application/vnd.api+json
```

This will return an array of `posts` resources in the top-level `data` member of the JSON API document. For example:

```http
HTTP/1.1 200 OK
Content-Type application/vnd.api+json

{
    "data": [
        {
            "type": "posts",
            "id": "1",
            "attributes": {
                "title": "My First Post",
                "slug": "my-first-post"
            },
            "links": {
                "self": "/api/posts/1"
            }
        },
        {
            "type": "posts",
            "id": "2",
            "attributes": {
                "title": "Hello World",
                "slug": "hello-world"
            },
            "links": {
                "self": "/api/posts/1"
            }
        }
    ]
}
```

If there are no `posts` to return, the response will be as follows:

```http
HTTP/1.1 200 OK
Content-Type application/vnd.api+json

{
    "data": []
}
```

This request is handled by the Adapter's `query` method. By default the Eloquent adapter will return all
models for a resource collection query that does not provide any pagination or filtering parameters.

> If there are potentially hundreds of resources that could be returned for a resource collection, we strongly
recommend forcing pagination to prevent the client from requesting too many resources. See the 
[Pagination chapter](./pagination.md) for details.

## Resources

A specific resource can be fetched as follows:

```http
GET /api/posts/1 HTTP/1.1
Accept: application/vnd.api+json
```

This will return a singular resource in the `data` member of the JSON API document, as follows:

```http
HTTP/1.1 200 OK
Content-Type application/vnd.api+json

{
    "data": {
        "type": "posts",
        "id": "1",
        "attributes": {
            "title": "My First Post",
            "slug": "my-first-post"
        },
        "links": {
            "self": "/api/posts/1"
        }
    }
}
```

If the resource does not exist, a `404 Not Found` response will be sent:

```http
HTTP/1.1 404 Not Found
Content-Type application/vnd.api+json

{
    "errors": [
        {
            "title": "Not Found",
            "status": "404"
        }
    ]
}
```

This request is handled by the Adapters `read` method.

## Related Resources

A resource object may include a `related` link in a relationship. For example:

```json
{
    "type": "posts",
    "id": "1",
    "relationships": {
        "author": {
            "links": {
                "related": "/api/posts/1/author"
            }
        }        
    }
}
``` 

For these queries, the adapter's `related` method is used to obtain a relationship adapter for the named relationship.
The related resource(s) are then obtained from the relationship adapter using the `query` method.

### To-One

The related author can be obtained at this endpoint as follows:

```http
GET /api/posts/1/author HTTP/1.1
Accept: application/vnd.api+json
```

This requests the author that is related to the `posts` resource with an id of `1`. For a `to-many` relationship,
the server will reply with the related resource in the `data` member of the document. For example:

```http
HTTP/1.1 200 OK
Content-Type application/vnd.api+json

{
    "data": {
        "type": "users",
        "id": "123",
        "attributes": {
            "name": "John Doe"
        },
        "links": {
            "self": "/api/users/123"
        }
    }
}
```

### To-Many

If a `to-many` relationship is empty, the `data` member will be `null`:

```http
HTTP/1.1 200 OK
Content-Type application/vnd.api+json

{
    "data": null
}
```

For a `to-many` relationship, the `data` member will be an array of resources, or an empty array if there are
no related resources. For example this request:

```http
GET /api/posts/1/tags HTTP/1.1
Accept: application/vnd.api+json
```

Would generate the following response:

```http
HTTP/1.1 200 OK
Content-Type application/vnd.api+json

{
    "data": [
        {
            "type": "tags",
            "id": "456",
            "attributes": {
                "name": "news"
            },
            "links": {
                "self": "/api/tags/456"
            }
        }
    ]
}
```

### Not Found

A request for related resources will return a `404 Not Found` response if the primary resource does not exist. So
for the example above, if no `posts` resource with an `id` of `1` exists, the following would be returned:

```http
HTTP/1.1 404 Not Found
Content-Type application/vnd.api+json

{
    "errors": [
        {
            "title": "Not Found",
            "status": "404"
        }
    ]
}
```
