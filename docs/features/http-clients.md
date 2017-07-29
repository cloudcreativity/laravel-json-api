# Sending HTTP Requests

## Introduction

This package allows you to send HTTP requests outbound from your application, using 
[Guzzle 6](http://docs.guzzlephp.org/en/stable/). You will need to install Guzzle using Composer:

```php
composer require guzzlehttp/guzzle:^6.3
```

## Remote APIs

You can use the JSON API configuration for your application's JSON API's to send requests to an external location.
However, if you need to encode resources differently, then you will need to define configuration for the remote
JSON API.

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
$guzzleClient = new GuzzleHttp\Client(['base_uri' => 'http://example.com/api/']);
$client = json_api('v1')->client($guzzleClient);
```

This creates a client based on the configuration for your `v1` JSON API. The Guzzle client **must** be provided 
with a `base_uri` option, as the JSON API client submits requests relative to the base URI.

## Resource Requests

### Index

To send an outbound index request for a resource type, use the `index` method:

```php
/** @var CloudCreativity\JsonApi\Contracts\Http\Responses\ResponseInterface $response */
$response = $client->index('posts');
```

You can also send parameters with the request:

```php
// Neomerx\JsonApi\Encoder\Parameters\EncodingParameters
$parameters = new EncodingParameters(
    ['author', 'comments'], // include paths
    ['author' => ['name', 'email'], // fieldsets
    ['-created-at'] // sort, 
    ['number' => 1, 'size' => '10'], // page
    ['site' => 'my-site.com'] // filters
);
$response = $client->index('posts', $parameters);
```

### Create

To create a resource on the remote API, use the `create` method:

```php
$post = new Post(['content' => 'Hello World']);
$response = $client->create($post);
$post->id = $response->getDocument()->getResource()->getId();
$post->save();
```

You can also create with a client generated ID:

```php
$post = new Post(['content' => 'Hello World']);
$post->save();
$client->create($post);
```

If you need to send `include` or `fields` parameters, you can pass `EncodingParameters` to `create` as the
second argument.

### Read

To read a resource on the remote API, use the `read` method:

```php
$postResource = $client->read('posts', '1')->getDocument()->getResource()->getId();
```

If you need to send `include` or `fields` parameters, you can pass `EncodingParameters` to `read` as the
third argument.

### Update

To update a resource on the remote API, use the `update` method:

```php
$post = Post::find(1);
$post->fill(['title' => 'New Title', 'content' => 'New content']);
$client->update($post);
$post->save();
```

This will send the entire resource to the remote API. If you only want to send specific members, pass the field
names as the second argument:

```php
$post = Post::find(1);
$post->fill(['content' => 'New content']);
$client->update($post, ['content']);
$post->save();
```

If you need to send `include` or `fields` parameters, you can pass `EncodingParameters` to `update` as the
third argument.

### Delete

To delete a resource on the remote API, use the `delete` method:

```php
$post = Post::find(1);
$client->delete($post);
$post->delete();
```

## Relationship Requests

We have not yet implemented relationship requests, but intend to.

## Errors

If you are using a Guzzle client with `http_errors` enabled (which it is by default), then these will be re-thrown
as JSON API exceptions if they are the result of a HTTP 400 or 500 response from the remote server. This means
you can then access the JSON API error objects to determine the cause of the error.

For example:

```php
try {
    $client->create($post);
} catch (Neomerx\JsonApi\Exceptions\JsonApiException $ex) {
    $errors = $ex->getErrors();
    $status = $ex->getHttpCode();
    // the previous exception is the Guzzle exception
    $psrResponse = $ex->getPrevious()->getResponse();
}
```

If you disable HTTP errors in your Guzzle client, the JSON API client will not throw exceptions.
