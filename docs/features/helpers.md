# Helpers

## Introduction

Laravel provides a number of global [helper PHP functions](https://laravel.com/docs/helpers). This package
provides some additional helpers that allow you to manually use capabilities within the package.

## Global Helpers

### `json_api()`

Called without any arguments, this return the JSON API instance that is handling the inbound HTTP request. An API 
is registered as handling an inbound request within any JSON API registered route.

If there is no API handling the inbound request, then an exception will be thrown if this helper is called
without any arguments. If you are using this helper outside of a JSON API route, you will need to provide the
name of the API to use. Generally you will need to do this if using the helper within any queued jobs.

For example:

```php
// the API handling the inbound HTTP request...
$api = json_api();
// the API named 'v1'
$api = json_api('v1');
```

See [below](#api-helpers) for helper methods on API instances.

### `json_api_request()`

This will return the JSON API request instance, or `null` if there is no inbound HTTP request.

## API Helpers

The following helpers methods are available on the API instances.

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

### `client()`

This helper returns a HTTP client for [sending JSON API requests](./http-clients/) to remote servers. You
must provide a Guzzle client as the first argument:

```php
$guzzleClient = new GuzzleHttp\Client(['base_uri' => 'http://example.com/api/']);
$client = json_api('v1')->client($guzzleClient);
```
