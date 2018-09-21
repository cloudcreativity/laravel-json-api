# Sending HTTP Requests

## Introduction

This package is primarily concerned with your application acting as a JSON API server. However,
it includes a client implementation that allows you to re-use your 
[resource schemas](../basics/schemas.md) to serialize records and send them as outbound HTTP requests.

The implementation uses
[Guzzle 6](http://docs.guzzlephp.org/en/stable/) and you will need to install Guzzle via Composer:

```php
composer require guzzlehttp/guzzle:^6.3
```

> An example use case for this feature is where your API sends events to external webhooks, like the Stripe
API. By using the client implementation in this package, you can serialize and send records to the
external webhook in the same format that they would be encoded in your API's HTTP responses.

## Remote APIs

You can use the JSON API configuration for your application's JSON API's to send requests to an external location.
This will mean that resources are encoded using exactly the same schemas as are used in your API's HTTP responses.

If you need to encode resources differently, you will need to define configuration for the remote JSON API.
Remote JSON APIs are defined in exactly the same way as application JSON APIs - i.e. you have a config file per
API. Use the generator to create a new config file:

```bash
$ php artisan make:json-api external
```

This will create a `json-api-external.php` config file, i.e. the API is named 'external'. Configure the settings
in this file (particularly the `namespace` option), then create a schema for this external API using:

```bash
$ php artisan make:json-api:schema posts external
```

When using configuration files for remote APIs, note that the `url` configuration option still relates to 
the URLs in your own application. This means that URLs in encoded requests specify where the resource exists 
within your own application. You can fully control where requests are sent using Guzzle configuration options.

## Creating Clients

You can create a JSON API client using via the `json_api()` helper method as follows:

```php
/** @var \CloudCreativity\LaravelJsonApi\Contracts\Client\ClientInterface $client */
$client = json_api()->client('http://external.com/webhooks');
```

> This will create a client using the schemas from your default API. If you need a client for a 
different API, pass the API name to the `json_api()` method, e.g. `json_api('v1')->client(...)`.

The first argument to the `client` method can be any of the following:

- A `string` base URI of the external host. As your JSON API config contains the API namespace,
the API namespace will be appended to the base URI. For example, if you provide `http://external.com`
as the base URI and your API config has the namespace as `/api/v1`, a request for the `posts` resource
type will be sent to `http://external.com/api/v1/posts`.
- An array of Guzzle options. If the options do not include a `base_uri` option, the host and API
namespace from your JSON API config will be used as the base URI.
- A Guzzle client.

```php
$guzzleClient = new GuzzleHttp\Client(['base_uri' => 'http://external.com/webhooks/']);
$client = json_api()->client($guzzleClient);
```

## Resource Requests

### Index

To send a query (index) request for a resource type, use the `query` method:

```php
/** @var \Psr\Http\Message\ResponseInterface $response */
$response = $client->query('posts');
```

You can also send parameters with the request:

```php
$response = $client->query('posts', [
  'filter' => ['author' => '123'],
  'sort' => 'title,-created-at',
]);
```

For example, this will send:

```http
GET http://external.com/webhooks/posts?filter['author']=123&sort=title,-created-at HTTP/1.1
Accept: application/vnd.api+json
```

### Create

To send a create resource request, use the `createRecord` method and provide the record to serialize
as the first argument:

```php
/** @var \App\Post $post */
/** @var \Psr\Http\Message\ResponseInterface $response */
$response = $client->createRecord($post);
```

This will use your schema for the post to serialize the record for the JSON API body content.
For example, the request sent will be:

```http
POST http://external.com/webhooks/posts HTTP/1.1
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json

{
  "data": {
    "type": "posts",
    "id": "123",
    "attributes": {...}
    "relationships": {...}
  }
}
```

> You must refer to the serialization documentation below for how to customise the request body.

You can also send a create resource request by providing the JSON payload manually, using the
`create` method:

```php
$response = $client->create('posts', [
    'data' => [
        'type' => 'posts',
        'attributes' => [
            // ...
        ],
    ],
]);
```

Both the `createRecord` and `create` methods take request query parameters as their final argument,
e.g.:

```php
$client->createRecord($post, ['include' => 'author']);
$client->create('posts', $payload, ['include' => 'author']);
```

### Read

To send a read resource request, use the `read` method:

```php
/** @var \Psr\Http\Message\ResponseInterface $response */
$response = $client->read('posts', '123');
```

You can also send parameters with the request:

```php
$response = $client->read('posts', '123', ['include' => 'author,tags']);
```

For example, this will send:

```http
GET http://external.com/webhooks/posts/123?include=author,tags HTTP/1.1
Accept: application/vnd.api+json
```

You can also send a read request using an existing record, for example:

```php
/** @var \App\Post $post */
/** @var \Psr\Http\Message\ResponseInterface $response */
$response = $client->readRecord($post);
```

This will use your schema to work out the resource type and id for the request. You
can also pass query parameters as the second argument.

### Update

To send an update resource request, use the `updateRecord` method and provide the record to serialize
as the first argument:

```php
/** @var \App\Post $post */
/** @var \Psr\Http\Message\ResponseInterface $response */
$response = $client->updateRecord($post);
```

This will use your schema for the post to serialize the record for the JSON API body content.
For example, the request sent will be:

```http
PATCH http://external.com/webhooks/posts HTTP/1.1
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json

{
  "data": {
    "type": "posts",
    "id": "123",
    "attributes": {...}
    "relationships": {...}
  }
}
```

> You must refer to the serialization documentation below for how to customise the request body.

You can also send an update resource request by providing the JSON payload manually, using the
`update` method:

```php
$response = $client->update('posts', '123', [
    'data' => [
        'type' => 'posts',
        'id' => '123',
        'attributes' => [
            // ...
        ],
    ],
]);
```

Both the `updateRecord` and `update` methods take request query parameters as their final argument,
e.g.:

```php
$client->updateRecord($post, ['include' => 'author']);
$client->update('posts', '123', $payload, ['include' => 'author']);
```

### Delete

To send a delete resource request, use the `delete` method:

```php
/** @var \Psr\Http\Message\ResponseInterface $response */
$response = $client->delete('posts', '123');
```

For example, this will send:

```http
DELETE http://external.com/webhooks/posts/123
Accept: application/vnd.api+json
```

You can also send parameters with the request:

```php
$response = $client->delete('posts', '123', $parameters);
```

You can also send a delete request using an existing record, for example:

```php
/** @var \App\Post $post */
/** @var \Psr\Http\Message\ResponseInterface $response */
$response = $client->deleteRecord($post);
```

This will use your schema to work out the resource type and id for the request. You
can also pass query parameters as the second argument if needed.

## Relationship Requests

### Read Related

To send a request to read the related record in a relationship, use the `readRelated` method.
For example to read the author related to a specific post:

```php
/** @var \Psr\Http\Message\ResponseInterface $response */
$response = $client->read('posts', '123', 'author');
```

You can also send parameters with the request:

```php
$response = $client->read('posts', '123', 'author', ['include' => 'sites']);
```

For example, this will send:

```http
GET http://external.com/webhooks/posts/123/author?include=sites HTTP/1.1
Accept: application/vnd.api+json
```

You can also send this request using an existing record, for example:

```php
/** @var \App\Post $post */
/** @var \Psr\Http\Message\ResponseInterface $response */
$response = $client->readRecordRelated($post, 'author');
```

This will use your schema to work out the resource type and id for the request. You
can also pass query parameters as the third argument if needed.

### Read Relationship

To send a request to read a relationship, use the `readRelationship` method.
For example to get the resource identifier of the author related to a post:

```php
/** @var \Psr\Http\Message\ResponseInterface $response */
$response = $client->readRelationship('posts', '123', 'author');
```

For example, this will send:

```http
GET http://external.com/webhooks/posts/123/relationships/author HTTP/1.1
Accept: application/vnd.api+json
```

You can also send parameters with the request:

```php
$response = $client->read('posts', '123', 'author', $parameters);
```

You can also send this request using an existing record, for example:

```php
/** @var \App\Post $post */
/** @var \Psr\Http\Message\ResponseInterface $response */
$response = $client->readRecordRelationship($post, 'author');
```

This will use your schema to work out the resource type and id for the request. You
can also pass query parameters as the third argument if needed.

### Replace Relationship

To send a request to replace a relationship with provided resource(s), use the 
`replaceRecordRelationship` method. You must provide the record that the relationship is on,
and the records that should be set as the related resources.

For example, to set all the tags for a post:

```php
/** @var \App\Post $post */
/** @var \Psr\Http\Message\ResponseInterface $response */
$response = $client->replaceRecordRelationship($post, $post->tags, 'tags');
```

This will use your schema for the post to work out the resource type and id for the request URI,
and the tag schema to serialize the resource identifiers in the request body. For example,
the following request will be sent:

```http
PATCH http://external.com/webhooks/posts/123/relationships/tags HTTP/1.1
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json

{
  "data": [
    { "type": "tags", "id": "34" },
    { "type": "tags", "id": "56" }
  ]
}
```

You can also send a replace relationship request by providing the JSON payload manually, using the
`replaceRelationship` method:

```php
$response = $client->replaceRelationship('posts', '123', 'tags', [
    'data' => [
        ['type' => 'tags', 'id' => '34'],
        ['type' => 'tags', 'id' => '56'],
    ],
]);
```

Both the `replaceRecordRelationship` and `replaceRelationship` methods take request query parameters as 
their final argument, e.g.:

```php
$client->replaceRecordRelationship($post, $post->tags, 'tags', ['foo' => 'bar']);
$client->replaceRelationship('posts', '123', 'tags', $payload, ['foo' => 'bar']);
```

### Add-To Relationship

To send a request to add to a relationship, use the `addToRecordRelationship` method. 
You must provide the record that the relationship is on, and the records that should be 
added as the related resources.

For example, to add tags to a post:

```php
/** @var \App\Post $post */
/** @var \App\Tag[] $tags */
/** @var \Psr\Http\Message\ResponseInterface $response */
$response = $client->addToRecordRelationship($post, $tags, 'tags');
```

This will use your schema for the post to work out the resource type and id for the request URI,
and the tag schema to serialize the resource identifiers in the request body. For example,
the following request will be sent:

```http
POST http://external.com/webhooks/posts/123/relationships/tags HTTP/1.1
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json

{
  "data": [
    { "type": "tags", "id": "34" },
    { "type": "tags", "id": "56" }
  ]
}
```

You can also send an add to relationship request by providing the JSON payload manually, using the
`addToRelationship` method:

```php
$response = $client->addToRelationship('posts', '123', 'tags', [
    'data' => [
        ['type' => 'tags', 'id' => '34'],
        ['type' => 'tags', 'id' => '56'],
    ],
]);
```

Both the `addToRecordRelationship` and `addToRelationship` methods take request query parameters as 
their final argument, e.g.:

```php
$client->addToRecordRelationship($post, $post->tags, 'tags', ['foo' => 'bar']);
$client->addToRelationship('posts', '123', 'tags', $payload, ['foo' => 'bar']);
```

### Remove From Relationship

To send a request remove records from a relationship, use the `removeFromRecordRelationship` method. 
You must provide the record that the relationship is on, and the records that should be 
remove from the related resources.

For example, to remove tags from a post:

```php
/** @var \App\Post $post */
/** @var \App\Tag[] $tags */
/** @var \Psr\Http\Message\ResponseInterface $response */
$response = $client->removeFromRecordRelationship($post, $tags, 'tags');
```

This will use your schema for the post to work out the resource type and id for the request URI,
and the tag schema to serialize the resource identifiers in the request body. For example,
the following request will be sent:

```http
DELETE http://external.com/webhooks/posts/123/relationships/tags HTTP/1.1
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json

{
  "data": [
    { "type": "tags", "id": "34" },
    { "type": "tags", "id": "56" }
  ]
}
```

You can also send a remove from relationship request by providing the JSON payload manually, using the
`removeFromRelationship` method:

```php
$response = $client->removeFromRelationship('posts', '123', 'tags', [
    'data' => [
        ['type' => 'tags', 'id' => '34'],
        ['type' => 'tags', 'id' => '56'],
    ],
]);
```

Both the `removeFromRecordRelationship` and `removeFromRelationship` methods take request query
parameters as their final argument, e.g.:

```php
$client->removeFromRecordRelationship($post, $post->tags, 'tags', ['foo' => 'bar']);
$client->removeFromRelationship('posts', '123', 'tags', $payload, ['foo' => 'bar']);
```

## Serialization

The JSON API client will serialize records that you provide for both create and update requests,
as described below. This serialization uses your API's [resource schemas](../basics/schemas.md)
to create the JSON API request body.

Resource schemas in this package are primarily designed for encoding server responses, rather than
serializing client requests. The client provides a number of helper methods to customise how
records are serialized when sending create or update requests.

All helper methods return a new client instance, ensuring that the original client is immutable.
We have implemented it this way in case you are using dependency injection to inject a singleton
client from the service container.

### Including Relationships

You can include relationships in the serialized request using the `withIncludePaths` method on the
client.

> If your schema is configured to only include relationship data if an include path is present,
you **must** use the `withIncludePaths` method on the client when sending records.

For example, to include the author and tags when creating a post:

```php
$response = $client->withIncludePaths('author', 'tags')->createRecord($post);
```

This would result in the following request:

```http
POST http://external.com/webhooks/posts HTTP/1.1
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json

{
  "data": {
    "type": "posts",
    "attributes": {...}
    "relationships": {
      "author": {
        "data": {
          "type": "users",
          "id": "99"
        }
      },
      "tags": {
        "data": [
          {
            "type": "tags",
            "id": "456"
          }
        ]
      }
    }
  }
}
```

By default the client does not send a [compound document](http://jsonapi.org/format/#document-compound-documents)
so the related resources are not included in the request. If you do want to include them, use the
`withCompoundDocuments()` method:

```php
$response = $client
    ->withIncludePaths('author', 'tags')
    ->withCompoundDocuments()
    ->createRecord($post);
```

> It is worth noting that the JSON API spec says that relationships must not exist in create and update
requests unless they have a `data` member. We therefore strip out any such relationships when serializing
the primary resource of the request.

### Sparse Fieldsets

It is possible to choose which fields to send when serializing a record for a request. Use the
`withFields` method, providing the resource type and a list of fields to serialize. For example:

```php
$response = $client->withFields('posts', ['title', 'content'])->update($post);
```

This would result in a request that only sent the `title` and `content` fields for the post:

```http
PATCH http://external.com/webhooks/posts/123 HTTP/1.1
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json

{
  "data": {
    "type": "posts",
    "id": "123",
    "attributes": {
      "title": "Hello World!",
      "content": "..."
    }
  }
}
```

If you are including related resources, you can also specify the fields of the related resources
that should be serialized. For example:

```php
$response = $client
    ->withIncludePaths('author')
    ->withCompoundDocuments()
    ->withFields('posts', ['title', 'author'])
    ->withFields('users', 'name')
    ->updateRecord($post);
```

This would only serialize the `name` attribute when including the author in the compound document.

### Links

By default links are not included in the serialized JSON API document. This is because these links
would normally refer to your server implementation, not the remote server that the request is being
sent to.

If you do need to send links in your request, use the `withLinks()` method. For example:

```php
$response = $client->withLinks()->createRecord($post);
```

## Request Options

As well as providing Guzzle options when creating the client, you can also provide options when
making a specific request using the `withOptions()` method:

```php
$response = $client->withOptions(['allow_redirects' => false])->read('posts', '123');
```

The `withOptions()` method returns a new JSON API client instance, so the above example only has
the `allow_redirects` option for the `read` request. If you wanted to use the options for
multiple requests, you can do the following:

```php
$client = $client->withOptions(['allow_redirects' => false]);
$client->updateRecord($post);
$response = $client->read('tags', '123');
```

## Errors

If you are using a Guzzle client with `http_errors` enabled (which they are by default), then the JSON
API client will throw a exceptions if a HTTP 400 or 500 response is received. If you disable HTTP errors 
in your Guzzle client, the JSON API client will not throw exceptions.

Type hint `CloudCreativity\LaravelJsonApi\Exceptions\ClientException` to catch errors. This provides
the following helper methods:

| Method | Description |
| :--- | :--- |
| `getRequest()` | Get the PSR request that the client sent. |
| `getResponse()` | Get the PSR response from the remote server. Note that there will not be a response if the error occurred before a response was received. |
| `hasResponse()` | Check if the exception has a PSR response. |
| `getHttpCode()` | Get the HTTP status code of the response. Returns `null` if there is no response. |
| `getErrors()` | Return a Laravel collection containing the JSON API errors in the response body. If there is no response, or if the body does not contain JSON API errors, the collection will be empty. |

Example usage is as follows:

```php
try {
    $client->create($post);
} catch (\CloudCreativity\LaravelJsonApi\Exceptions\ClientException $ex) {
    if ($ex->getErrors()->contains('code', 'payment-failed')) {
        throw new \App\Exceptions\PaymentFailed();
    }
    
    throw $ex;
}
```

