# Introduction

Add [jsonapi.org](http://jsonapi.org) compliant APIs to your Laravel 5 application.
Based on the framework agnostic packages [neomerx/json-api](https://github.com/neomerx/json-api) and
[cloudcreativity/json-api](https://github.com/cloudcreativity/json-api).

## What is JSON API?

From [jsonapi.org](http://jsonapi.org)

> If you've ever argued with your team about the way your JSON responses should be formatted, JSON API is your anti-bikeshedding weapon.
>
> By following shared conventions, you can increase productivity, take advantage of generalized tooling, and focus on what matters: your application. Clients built around JSON API are able to take advantage of its features around efficiently caching responses, sometimes eliminating network requests entirely.

For full information on the spec, plus examples, see [http://jsonapi.org](http://jsonapi.org).

## Theory of Operation

Your application will have route group(s) that represent your API endpoints. These groups are defined as JSON API
endpoints by using the `json-api` middleware. Middleware ensures that:

1. The client has included the [correct headers](http://jsonapi.org/format/#content-negotiation-clients) in its request.
2. The definitions of how to convert PHP objects to JSON API resources for the route group are loaded.

Your route group represents the group of JSON API resources that are available within your API. Each *resource type*
relates directly to a PHP object class. We refer to instances of resource types as *resources*, and instances of
your PHP classes as *records*. For each resource type you have:

1. A **request** that handles validation of query parameters that are acceptable for the specific
resource type, as well as parsing HTTP request body content. This operates along the lines of
[Laravel form request validation](https://laravel.com/docs/5.2/validation#form-request-validation) in that the
request is handled prior to your controller method.
2. A **validator** that is injected into the request so that it knows how to validate HTTP request content for the
specific resource type.
3. A **controller** that receives the validated and parsed request object and handles any specific application logic
before returning the correct JSON API response using our response factory.
4. A **schema** that converts a record into its JSON API resource representation when that response is encoded to its
JSON representation.

Optionally you can also have the following per resource type:

1. An **authorizer**, which if injected into a request will handle checking whether the client has the authority to
carry out the request.
2. A **hydrator** that contains the logic for transferring data from a resource received from a client into a record.
This is used within the controller and is useful to keep your controller lightweight.

#### Why *Records* not *Models*?

In Laravel the phrase *model* is potentially confusing with Eloquent models. While some applications might solely
encode Eloquent models to JSON API resources, others will use a mixture of Eloquent models and other PHP classes, or
might not even be using Eloquent models.

So we decided to refer to PHP object instances that are converted to JSON API resources as *records*.

## Namespaces

The following base namespaces are used by this package:

| Namespace | Details |
| :-- | :-- |
| `Neomerx\JsonApi` | The base framework-agnostic package. Provides encoding, codec matching and HTTP request header/parameter parsing. |
| `CloudCreativity\JsonApi` | Extension framework-agnostic package, adding in HTTP body content validation, decoding to standard object interfaces, etc. |
| `CloudCreativity\LaravelJsonApi` | Integrates both framework-agnostic packages for use in a Laravel application |
