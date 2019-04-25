# Media Types (Content Negotiation)

The JSON API spec defines [content negotiation](http://jsonapi.org/format/#content-negotiation) that must occur
between the client and the server. When you generate a new JSON API in your application, it is configured
to support the JSON API media type - `application/vnd.api+json`.

If your API needs to support other media types, this package allows you to add code to
support additional **encodings** (how to encode response content) and/or **decodings** (how to read
request content). It is important to think about encoding and decoding separately, because a client
can send a request that has content in one media type, while requesting a response in a different
media type.

This chapter provides details of how to build JSON APIs with this package that support multiple media
types. This is illustrated with some basic examples. As support for multiple media types is not covered
by the JSON API spec, we can only provide details of how to integrate with this package, rather than
how logically your application should support other media types.

## Content Negotiation

Content negotiation occurs during the middleware stack on the routes within your API. This is
run by the `json-api.content` middleware. The default configuration for your API is setup to
support receiving JSON API content and replying with JSON API content.

### Accept Header

A client defines the content it wants in the `Accept` header. Content negotiation involves matching
the accept header media types to the **encodings** configured in your API. 

If your API is not configured to support the media types requested by a client, it will send 
a `406 Not Acceptable` response. For example:

```http
GET /api/v1/posts/1 HTTP/1.1
Accept: application/json
```

Would result in the following response:

```http
HTTP/1.1 406 Not Acceptable
Content-Type: application/json

{
  "message": "The requested resource is capable of generating only content not acceptable according to the Accept headers sent in the request."
}
```

> As the client has not requested JSON API content, your application's exception handler will render the
response. In the example above the client has asked for JSON, so the client receives Laravel's JSON
rendering of the HTTP exception.

Support can be added for additional encoding media types, as described in the [Encoding](#Encoding) section
below.

### Content-Type Header

A client specifies the media type of request content in the `Content-Type` header. Content negotiation
involves matching the content media type to the **decodings** configured in your API.

If your API is not configured to support the media type sent by a client, it will send a
`415 Unsupported Media Type` response. For example:

```http
POST /api/v1/posts HTTP/1.1
Content-Type: application/json
Accept: application/vnd.api+json

{
  "title": "Hello World",
  "content": "..."
}
```

Would result in the following response:

```http
HTTP/1.1 415 Unsupported Media Type
Content-Type: application/vnd.api+json

{
  "error": [
    {
      "title": "Unsupported Media Type",
      "status": "415",
      "detail": "The request entity has a media type which the server or resource does not support."
    }
  ]
}
```

> In the example above, the client has requested JSON API content via the `Accept` header, so the
response contains JSON API errors.

Support can be added for additional decoding media types, as described in the [Decoding](#Decoding) section
below.

## Encoding

Encodings define the response media types that are supported by your API. These allow you to define
either:

- Media types that contain JSON API response content, but are JSON encoded with different settings; or
- Media types that are supported but do not generate JSON API content.

The default media types supported by your API are listed in the `encoding` array within the API's
configuration. These media types apply to every route and action within your API. 

By default the configuration contains support for the JSON API media type:

```php
return [
    // ...
    
    'encoding' => [
        'application/vnd.api+json'
    ],
];
```

### JSON API Encoding

The `encoding` array supports media types being listed as the array values and/or the media type as
the array key and the value the options that are passed to PHP's `json_encode` function. For example,
to configure the JSON encoding for the JSON API media type:

```php
return [
    // ...
    
    'encoding' => [
        'application/vnd.api+json' => JSON_PRESERVE_ZERO_FRACTION,
    ],
],
```

Additional media types can be added to `encoding` settings. For example, if we wanted to support the
`text/plain` media type to return human-readable JSON API encoded content, we can add it as follows:

```php
return [
    // ...
    
    'encoding' => [
        'application/vnd.api+json',
        'text/plain' => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION,
    ],
];
```

The following can then be used to request human-readable content:

```http
GET /api/v1/posts/1 HTTP/1.1
Accept: text/plain
```

### Custom Encoding

If you need to support a media type that does not encode to JSON API content, add it to your
`encoding` configuration with the value set to `false`. For example:

```php
return [
    // ...
    
    'encoding' => [
        'applcation/vnd.api+json',
        'application/json' => false,
    ],
],
```

You will now need to use [controller hooks](../basics/controllers.md) to return responses for
these custom media types.

For example, if we wanted to use Laravel's Eloquent API resources for the response if the
client has requested `application/json`, the `searched` hook on our controller would be:

```php
namespace App\Http\Controllers\Api\PostsController;

use App\Http\Resources\Post as PostResource;

class PostsController extends JsonApiController
{

    /**
     * @param $posts
     *      the posts returned by your adapter.
     * @return \Illuminate\Http\Resources\Json\ResourceCollection|null
     */
    public function searched($posts)
    {
        // This check ensures we still support the JSON API media type.
        if ($this->willEncode('application/json')) {
            return PostResource::collection($posts);
        }

        return null;
    }
}
```

In this example, we create the JSON response *after* the adapter has returned results. If in this
case we wanted to return a response for the media type *before* the JSON API adapter has been
invoked, we would use the `searching` hook instead.

> The advantage with using controller hooks that run *after* results have been returned from your
adapter is that the media type `application/json` will have the same filters, paging etc
applied as they are for the `application/vnd.api+json` media type.

## Decoding

Decodings define the request media types that are supported by your API. These allow you to define
either:

- Media types that can be decoded and parsed as JSON API content; and/or
- Media types that are supported but are not to parsed as JSON API content.

The default media types supported by your API are listed in the `decoding` array within the API's
configuration. These media types apply to every route and action within your API. 

By default the configuration contains support for the JSON API media type:

```php
return [
    // ...
    
    'decoding' => [
        'application/vnd.api+json',
    ],
];
```

### JSON API Decoding

If you want to support additional media types that can be parsed as JSON API content, you can add
them to the `decoding` array.

For example if we wanted to allow the client to use an `application/json` content type, but
expect the format of the JSON to match the JSON API spec, we would add it as follows:

```php
return [
    // ...
    
    'decoding' => [
        'application/vnd.api+json',
        'application/json',
    ],
];
```

This would mean the following request would be allowed, as the JSON content complies with the JSON API
spec:

```http
POST /api/v1/posts HTTP/1.1
Content-Type: application/json
Accept: application/vnd.api+json

{
  "data": {
    "type": "posts",
    "attributes": {
      "title": "Hello World",
      "content": "..."
    }
  }
}
```

> You could use your `encoding` configuration to also allow the client to send an `Accept` header with
the `application/json` media type.

### Custom Decoding

If we want to support a media type that is not compliant with the JSON API spec, then it can be
configured as follows:

```php
return [
    'decoding' => [
        'application/vnd.api+json',
        'application/json' => \App\JsonApi\JsonDecoder::class,
    ],
];
```

The string value is any fully-qualified name or Laravel service container binding that resolves to an
implementation of `CloudCreativity\LaravelJsonApi\Contracts\Decoder\DecoderInterface`.

In the above example, our `JsonDecoder` might be:

```php
namespace App\JsonApi\JsonDecoder;

use CloudCreativity\LaravelJsonApi\Contracts\Decoder\DecoderInterface;

class JsonDecoder implements DecoderInterface
{
    
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function decode($request): array
    {
        return $request->json()->all();
    }
}
```

This means that we can now support the following request:

```http
POST /api/v1/posts HTTP/1.1
Content-Type: application/json
Accept: application/vnd.api+json

{
  "title": "Hello World",
  "content": "..."
}
```

This will mean the following array will be passed to the `posts` resource validators:

```php
return [
    'title' => 'Hello World',
    'content' => '...'
];
```

In your validators class you need to overload the relevant public method to return a validator
for the non-JSON API data. The `createValidator` method is provided to easily create a validator
for non-JSON API data:

```php
namespace App\JsonApi\Posts\Validators;

use CloudCreativity\LaravelJsonApi\Contracts\Validation\ValidatorInterface;
use CloudCreativity\LaravelJsonApi\Validation\AbstractValidators;

class Validators extends AbstractValidators
{
    // ...
    
    /**
     * @inheritdoc
     */
    public function create(array $document): ValidatorInterface
    {
        if ($this->didDecode('application/json')) {
            return $this->createValidator($document, [
                'title' => 'required|...',
                'content' => 'required|...',
            ]);
        }
    
        return parent::create($document);
    }
}
```

Then in your adapter you would need to overload the relevant public method to handle the non-JSON API
data. For example: 

```php
namespace App\JsonApi\Posts;

use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

class Adapter extends AbstractAdapter
{
    // ...
    
    public function create(array $document, EncodingParametersInterface $parameters)
    {
        if ($this->didDecode('application/json')) {
            $document = [
                'data' => [
                    'type' => 'posts',
                    'attributes' => $document,
                ],
            ];
        }
        
        return parent::create($document, $parameters);
    }
}
```

> The above is just an example, and you can do anything you need to do in your adapter to process
your decoded data. However it is advantageous to convert it to the expected JSON API format and pass
it to the parent method. This will mean that it is handled in exactly the same way as the JSON API media type,
plus the parent methods handles using the encoding parameters for eager loading, etc. 

## Content Negotiators

This package implements content negotiation using `ContentNegotiator` classes. You will need to write 
your own content negotiator classes if:

- you need to programmatically work out what media types are supported; and/or
- you want to support additional media types for specific resources or specific actions.

### Generating Content Negotiators

This package provides a generator to create content negotiator classes. You can generate either:

- *re-usable* content negotiators: can be used by either your whole API or multiple resources within
the API.
- *resource-specific* content negotiators: are used by a specific resource type.

To generate a content negotiator that is re-usable across multiple JSON API resource types, use the following:

```bash
$ php artisan make:json-api:content-negotiator <name> [<api>]
```

Where `<name>` is a unique name for the content negotiator, e.g. `default`, `json` etc.

To generate a resource-specific content negotiator, use the resource type as the name and add the 
`--resource` (or `-r`) flag. E.g. to generate a content negotiator for our `posts` resource:

```bash
$ php artisan make:json-api:content-negotiator posts -r
```

Alternatively you can generate a content negotiator when creating a resource using the 
`--content-negotiator` (or `-c`) flag:

```bash
$ php artisan make:json-api:resource posts -c
```

If your API has its `by-resource` option set to `true`, the generator will place re-usable content negotiators
in the root of your JSON API namespace, e.g. `App\JsonApi\DefaultContentNegotiator`. Resource-specific
content negotiators will be placed in the resource's namespace, e.g. `App\JsonApi\Posts\ContentNegotiator`.

If your `by-resource` option is set to `false`, re-usable and resource specific authorizers will always be
placed in the `ContentNegotiators` namespace, e.g. `App\JsonApi\ContentNegotiators\DefaultContentNegotiator`.

> **You must give your re-usable content negotiators names that do not clash with your resource types.** 

### Using Content Negotiators

To set the default content negotiator for your API, use the `defaultContentNegotiator` method when
registering the API. For example, if we wanted to use the `json` content negotiator:

```php
JsonApi::register('default')->defaultContentNegotiator('json')->routes(function ($api, $router) {
    $api->resource('posts');
    $api->resource('comments');
});
```

To use a re-usable content negotiator on specific resource types, use the `contentNegotiator` method
when registering the resource. For example, if we wanted to use the `json` content negotiator only
for the `posts` and `comments` resources, but not the `tags` resource:

 ```php
 JsonApi::register('default')->routes(function ($api, $router) {
     $api->resource('posts')->contentNegotiator('json');
     $api->resource('comments')->contentNegotiator('json');
     $api->resource('tags'); // uses the default content negotiator
 });
 ```

If you have generated a resource-specific content negotiator, it will be automatically detected so there
is no need to configure it.

### Encoding Media Types

On your content negotiator, you can configure additional encoding media types using the `encoding`
property. This uses the same array format as your API's configuration:

```php
namespace DummyApp\JsonApi\Avatars;

use CloudCreativity\LaravelJsonApi\Http\ContentNegotiator as BaseContentNegotiator;

class ContentNegotiator extends BaseContentNegotiator
{
    protected $encoding = [
        'text/csv' => false,
    ];
}
```

The media types listed on your content negotiator are **added** to the list of media types that
your API supports. They will be used for every controller action that the content negotiator
is used for.

If you need to programmatically work out the media types to support, or only want to support additional
media types on particular actions, implement either of the following methods:

- `encodingsForOne`: the encoding media types when the response will contain the resource that the
request relates to. E.g. a `GET /api/v1/posts/1` request. This method receives the domain record for
the request as its first argument. For a create request, the argument will be `null`.
- `encodingsForMany`: the encoding media types when the response will contain zero-to-many of the
resource. E.g. `GET /api/v1/posts` or when the resource is in a relationship such as
`GET /api/v1/users/1/posts`.

#### Encoding Example

For example, say we wanted to support returning an avatar's image via our API we would need to
support the media type of the stored avatar. Our avatar content negotiator may look like this:

```php
namespace App\JsonApi\Avatars;

use App\Avatar;
use CloudCreativity\LaravelJsonApi\Codec\EncodingList;
use CloudCreativity\LaravelJsonApi\Http\ContentNegotiator as BaseContentNegotiator;

class ContentNegotiator extends BaseContentNegotiator
{

    /**
     * @param Avatar|null $avatar
     * @return EncodingList
     */
    protected function encodingsForOne(?Avatar $avatar): EncodingList
    {
        $mediaType = optional($avatar)->media_type;

        return $this
            ->encodingMediaTypes()
            ->when($this->request->isMethod('GET'), $mediaType);
    }

}
```

In this example, `encodingMediaTypes()` returns the list of the encodings supported by our API. The
`when` method adds an encoding to the list if the first argument is `true` - in this case, if the
request method is `GET`.

> The `EncodingList` class also has an `unless` method, along with other helper methods.

If we added this to our resource's controller:

```php
namespace App\Http\Controllers;

use App\Avatar;
use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;
use Illuminate\Support\Facades\Storage;

class AvatarsController extends JsonApiController
{

    protected function reading(Avatar $avatar)
    {
        if ($this->willNotEncode($avatar->media_type)) {
            return null;
        }

        abort_unless(
            Storage::disk('local')->exists($avatar->path),
            404,
            'The image file does not exist.'
        );

        return Storage::disk('local')->download($avatar->path);
    }
}
```

Then the following request would download the avatar's image:

```http
GET /api/v1/avatars/1 HTTP/1.1
Accept: image/*
```

### Decoding Media Types

On your content negotiator, you can configure additional decoding media types using the `decoding`
property. This uses the same array format as your API's configuration:

```php
namespace DummyApp\JsonApi\Avatars;

use CloudCreativity\LaravelJsonApi\Http\ContentNegotiator as BaseContentNegotiator;

class ContentNegotiator extends BaseContentNegotiator
{
    protected $decoding = [
        'multipart/form-data' => \App\JsonApi\MultipartDecoder::class,
        'multipart/form-data; boundary=*' => \App\JsonApi\MultipartDecoder::class,
    ];
}
```

> The `MultipartDecoder` is a decoder you will need to write with your own logic. There's an
example of what a decoder might look like below.

The media types listed on your content negotiator are **added** to the list of media types that
your API supports. They will be used for every controller action that the content negotiator
is used for.

If you need to programmatically work out the media types to support, or only want to support additional
media types on particular actions, implement either of the following methods:

- `decodingsForResource`: the decoding media types when the request content is expected to be a
resource object. E.g. `POST /api/v1/posts` or `PATCH /api/v1/posts/1`. This method receives
the domain record for the request as its first argument. For a create request, the argument will
be `null`.
- `decodingsForRelationship`: the decoding media types when the request content is expected to be a
relationship object. E.g. `POST /api/v1/posts/1/tags`. This method receives the domain record for
the request as its first arguments, and the relationship field name as its second argument.

#### Decoding Example

For example, if we wanted to support uploading an Avatar image to create an `avatars` resource,
we would need to write a decoder that handled files:

```php
namespace App\JsonApi;

use CloudCreativity\LaravelJsonApi\Contracts\Decoder\DecoderInterface;

class MultipartDecoder implements DecoderInterface
{

    /**
     * @inheritdoc
     */
    public function decode($request): array
    {
        // return whatever array data you expect from the request.
        // in this example we are expecting a file, so we will return all files.
        return $request->allFiles();
    }
}
```

Then we would need to use this decoder in our `avatars` content negotiator:

```php
namespace App\JsonApi\Avatars;

use App\Avatar;
use App\JsonApi\MultipartDecoder;
use CloudCreativity\LaravelJsonApi\Codec\Decoding;
use CloudCreativity\LaravelJsonApi\Codec\DecodingList;
use CloudCreativity\LaravelJsonApi\Http\ContentNegotiator as BaseContentNegotiator;

class ContentNegotiator extends BaseContentNegotiator
{

    /**
     * @param Avatar|null $avatar
     * @return DecodingList
     */
    protected function decodingsForResource(?Avatar $avatar): DecodingList
    {
        $decoder = new MultipartDecoder();

        return $this
            ->decodingMediaTypes()
            ->when(is_null($avatar), Decoding::create('multipart/form-data', $decoder))
            ->when(is_null($avatar), Decoding::create('multipart/form-data; boundary=*', $decoder));
    }

}
```

In this example, `decodingMediaTypes` returns the media types supported by our API, and we add
the `multipart/form-data` media type if it is a create request (indicated by `$avatar` being `null`).

> The `DecodingList` class also has an `unless` method, along with other helper methods. You can also
access the current request in the content negotiator using the `$request` property.

We would then need to amend the `Validators` and `Adapter` classes for our `avatars` resource, as
described for custom decoding earlier in this chapter. Having done that,
a client would then be able to upload a file and get a JSON API resource in the response using
this request:

```http
POST /api/v1/avatars HTTP/1.1
Accept: application/vnd.api+json
Content-Type: mutlipart/form-data

// ...
```
