# Inclusion

## Introduction

This package supports the specification of [JSON API Inclusion of Related Resources](http://jsonapi.org/format/1.0/#fetching-includes). This allows you to load all the data of the specified resources that is bounded by relationship.

### Using Include Parameter

Per the specification, the client is able to request for the inclusion of related resources through the following HTTP request:

```http
GET /api/posts?include=comments HTTP/2.0
Accept: application/vnd.api+json
``` 

However, by default, this package denies inclusion of related resources via parameter and would throw the follow error message.

```json
{
    "errors": [
        {
            "title": "Include paths should contain only allowed ones.",
            "source": {
                "parameter": "include"
            }
        }
    ]
}

```

To allow certain resources to be included using request parameters, it must be manually enabled through the resource's `Validators.php` file.

```php
namespace App\JsonApiV1\Posts;

class Validators extends AbstractValidatorProvider
{
    protected $allowedIncludePaths = [
        'comments'
    ];

    /* Your code here ...*/
}
```


### Auto Inclusion

It is possible to force the inclusion of certain related resources; displaying it even when the client did not specify the related resource in the include parameter. To enable the automatic inclusion of resources, edit the resource's `Schema.php` file.

```php
namespace App\JsonApiV1\Posts;

class Schema extends EloquentSchema
{

    /* Your code here ...*/

    public function getIncludePaths()
    {
        return [
            'comments'
        ];
    }
}
```

And when the client sends the following request:

```http
GET /api/posts HTTP/2.0
Accept: application/vnd.api+json
``` 

The response would look something like this:

```json
{
  "data": [
    {
      "type": "posts",
      "id": "6118bf58-7374-4e9a-9843-d1b0e53ac0b9",
      "attributes": {
        "created-at": "2017-10-14T07:56:44+00:00",
        "updated-at": "2017-10-14T07:56:44+00:00",
        "title": "Bernadine McClure",
        "description": "Provident recusandae est rem consequatur. Et alias ut culpa architecto eligendi et temporibus. Aliquam ipsa vitae mollitia totam fuga."
      },
      "relationships": {
        "comments": {
          "data": [
            {
              "type": "comments",
              "id": "3813a5ba-3d7b-432d-aa0d-eb0c3bd4e1a1"
            },
            {
              "type": "comments",
              "id": "bb3dd8a2-8678-480e-b477-87c8fddc5702"
            }
          ],
          "meta": {
            "total": 2
          },
          "links": {
            "self": "http://localhost:8000/api/v1/posts/6118bf58-7374-4e9a-9843-d1b0e53ac0b9/relationships/comments",
            "related": "http://localhost:8000/api/v1/posts/6118bf58-7374-4e9a-9843-d1b0e53ac0b9/comments"
          }
        }
      },
      "links": {
        "self": "http://localhost:8000/api/v1/posts/6118bf58-7374-4e9a-9843-d1b0e53ac0b9"
      }
    }
  ],
  "included": [
    {
      "type": "comments",
      "id": "3813a5ba-3d7b-432d-aa0d-eb0c3bd4e1a1",
      "attributes": {
        "created-at": "2017-10-14T09:47:09+00:00",
        "updated-at": "2017-10-14T09:47:09+00:00",
        "description": "Excellent post!"
      },
      "relationships": {
        "post": {
          "data": {
            "type": "posts",
            "id": "6118bf58-7374-4e9a-9843-d1b0e53ac0b9"
          },
          "links": {
            "self": "http://localhost:8000/api/v1/comments/3813a5ba-3d7b-432d-aa0d-eb0c3bd4e1a1/relationships/post",
            "related": "http://localhost:8000/api/v1/comments/3813a5ba-3d7b-432d-aa0d-eb0c3bd4e1a1/post"
          }
        }
      }
    },
    {
      "type": "comments",
      "id": "bb3dd8a2-8678-480e-b477-87c8fddc5702",
      "attributes": {
        "created-at": "2017-10-14T07:57:52+00:00",
        "updated-at": "2017-10-14T07:57:52+00:00",
        "description": "I really like your post!"
      },
      "relationships": {
        "post": {
          "data": {
            "type": "posts",
            "id": "6118bf58-7374-4e9a-9843-d1b0e53ac0b9"
          },
          "links": {
            "self": "http://localhost:8000/api/v1/comments/bb3dd8a2-8678-480e-b477-87c8fddc5702/relationships/post",
            "related": "http://localhost:8000/api/v1/comments/bb3dd8a2-8678-480e-b477-87c8fddc5702/post"
          }
        }
      }
    }
  ]
}

```