# Fetching Relationships

## Introduction

A server must support fetching relationship data for every relationship URL provided as a `self` link as part
of a relationship's `links` object. The relationship data returned is the resource identifiers for the
related resources.

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

All querying of resources is handled via the [Resource Adapter](../basics/adapters.md) class for the resource type that
is subject of the request. Relationship queries are handed off to a relationship adapter that is returned from
the resource adapter's `related` method.

## Relationship Data

A resource object may include a `self` link in a relationship. For example:

```json
{
    "type": "posts",
    "id": "1",
    "relationships": {
        "author": {
            "links": {
                "self": "/api/posts/1/relationships/author"
            }
        }        
    }
}
```

For these queries, the adapter's `related` method is used to obtain a relationship adapter for the named relationship.
The related resource(s) are then obtained from the relationship adapter using the `relationship` method.

### To-One 

A request for a `to-one` relationship's data is made as follows:

```http
GET /api/posts/1/relationships/author HTTP/1.1
Accept: application/vnd.api+json
``` 

If the relationship is not empty, the resource identifier of the related resource will be returned in the `data`
member of the JSON API document:

```http
HTTP/1.1 200 OK
Content-Type application/vnd.api+json

{
    "data": {
        "type": "users",
        "id": "123"
    }
}
```

If the relationship is empty, the `data` member will be `null`:

```http
HTTP/1.1 200 OK
Content-Type application/vnd.api+json

{
    "data": null
}
```

### To-Many

A request for a `to-many` relationship's data is made as follows:

```http
GET /api/posts/1/relationships/tags HTTP/1.1
Accept: application/vnd.api+json
``` 

The resource identifiers of the related resources will be returned in the `data` member of the JSON API document:

```http
HTTP/1.1 200 OK
Content-Type application/vnd.api+json

{
    "data": [
        {
            "type": "tags",
            "id": "123"
        },
        {
            "type": "tags",
            "id": 456"
        }
    ]
}
```

If the relationship is empty, an empty array will be returned:

```http
HTTP/1.1 200 OK
Content-Type application/vnd.api+json

{
    "data": []
}
```

### Not Found

A request for relationship data will return a `404 Not Found` response if the primary resource does not exist. So
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