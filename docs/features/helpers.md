# Helpers

## Introduction

Laravel provides a number of global [helper PHP functions](https://laravel.com/docs/helpers). This package
provides some additional helpers that allow you to manually use capabilities within the package.

Helpers are either *global* (like Laravel's helpers) or scoped to a specific API. The latter we refer to as 
*API helpers*.

## Global Helpers

### `json_api()`

Called without any arguments, this returns the JSON API instance that is handling the inbound HTTP request.
An API is registered as handling an inbound request within any JSON API registered route.

If there is no API handling the inbound request, then your default API will be returned. If you need a
different API, call the helper with the name of the API you need.

For example:

```php
// the API handling the inbound HTTP request or the default API...
$api = json_api();
// the API named 'v1'
$api = json_api('v1');
```

See [below](#api-helpers) for helper methods on API instances.

### `json_api_request()`

This will return the JSON API request instance, or `null` if there is no inbound HTTP request.

## API Helpers

The following helpers methods are available on the API instances.

### `client()`

This helper returns a HTTP client for [sending JSON API requests](./http-clients/) to remote servers. You
must provide a Guzzle client as the first argument:

```php
$guzzleClient = new GuzzleHttp\Client(['base_uri' => 'http://example.com/api/']);
$client = json_api('v1')->client($guzzleClient);
```

### `encoder()`

Gets an encoder that is configured with the API's details. This can be used to manually serialize data to arrays, or
encode data to strings. For example:

```php
// ['data' => ['type' => 'posts', 'id' => '1', 'attributes' => ['content' => 'Hello World']]]
$data = json_api('v1')->encoder()->serializeData($post);
// {"data": {"type": "posts", "id": "1", "attributes": {"content": "Hello World"}}}
echo json_api('v1')->encoder()->encodeData($post);
```

The `encoder()` method takes two arguments - options and depth. Both of these are identical to the options and
depth arguments in PHP's native `json_encode()` method.

```php
// output a pretty-printed JSON string...
echo json_api('v1')->encoder(JSON_PRETTY_PRINT)->encodeData($post);
```

### `links()`

This helper returns a generator that allows you to create JSON API link objects for URLs in your API. For example:

```php
/** @var \Neomerx\JsonApi\Contracts\Document\LinkInterface $link */
$link = json_api('v1')->links()->index('posts');
$meta = ['foo' => 'bar'];
$link = json_api('v1')->links()->index('posts', $meta);
```

The following methods on the links generator create links for resources within your API:

- `index($type, $meta)`
- `create($type, $meta)`
- `read($type, $id, $meta)`
- `update($type, $id, $meta)`
- `delete($type, $id, $meta)`
- `relatedResource($type, $id, $relationship, $meta)`
- `readRelationship($type, $id, $relationship, $meta)`
- `replaceRelationship($type, $id, $relationship, $meta)`
- `addRelationship($type, $id, $relationship, $meta)`
- `removeRelationship($type, $id, $relationship, $meta)`

> In all of these methods, `$meta` is an optional argument.

All these methods take an additional optional argument - an array of parameters. Use this if you need to pass 
additional parameters that are required when generating the URLs within the link objects.

### `url()`

This helper returns a URL generator for the API, which makes it easy to generate string links to resources within
the API. For example:

```php
// http://localhost/api/v1/posts
$url = json_api('v1')->url()->index('posts');
// http://localhost/api/posts/1
$url = json_api('v1')->url()->read('posts', 1);
```

The available methods on the URL generator are as follows:

- `index($type)`
- `create($type)`
- `read($type, $id)`
- `update($type, $id)`
- `delete($type, $id)`
- `relatedResource($type, $id, $relationship)`
- `readRelationship($type, $id, $relationship)`
- `replaceRelationship($type, $id, $relationship)`
- `addRelationship($type, $id, $relationship)`
- `removeRelationship($type, $id, $relationship)`

All these methods take an additional optional argument - an array of parameters. Use this if you need to pass 
additional parameters that are required when generating the URLs.
