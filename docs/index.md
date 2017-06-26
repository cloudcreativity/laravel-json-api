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

## Demo

We've created a simple [demo application](https://github.com/cloudcreativity/demo-laravel-json-api) that is
available to download, view the code and play around with as needed.

## Theory of Operation

Your application will have one (or many) APIs that conform to the JSON API spec. You define an API in your via routes, while JSON API settings are configured in a config file for each API. If you have multiple APIs, each has a unique *name*.

A JSON API contains a number of *resource types* that are available within your API. Each resource type
relates directly to a PHP object class. We refer to instances of JSON API resource types as *resources*, and instances of your PHP classes as *records*. 

Each resource type has the following units that serve a particular purpose:

1. **Adapter**: Defines how to load a record using a JSON API resource identifier, and how to query for many records when the client fetches many resources.
2. **Hydrator**: Deserializes data from a JSON API resource into the record to which it relates.
3. **Schema**: Serializes a record into its JSON API representation.
4. **Validators**: Provides validator instances to validate JSON API query parameters and HTTP content body.

Optionally you can also add an **Authorizer** instance to authorize incoming JSON API request, either for multiple resource types or for a specific resource type.

This may sound like a lot of units, but we believe the single purpose approach makes these highly testable and easy to reason about! 

### Namespacing

JSON API units are expected to be stored in a root namespace, which defaults to the `JsonApi` namespace in your application - e.g. `App\JsonApi`.

We expect you to store units within this namespace in one of two ways:

1. **By Resource**: You have a namespace per resource type and the units for the type are stored in this namespace. E.g. for a `posts` resource you would have `App\JsonApi\Posts\{Adapter,Schema...}`

2. **By Unit**: You have a namespace per JSON API unit, with the classes named according to the the resource type. E.g. for a `posts` resource you would have `App\JsonApi\{Adapters,Schemas...}\Post`

For both, note that the namespace is plural, and the class name is singular. The package includes generator Artisan commands to keep this simple.

### Why *Records* not *Models*?

In Laravel the phrase *model* is potentially confusing with Eloquent models. While some applications might solely encode Eloquent models to JSON API resources, others will use a mixture of Eloquent models and other PHP classes, or might not even be using Eloquent models.

So we decided to refer to PHP object instances that are converted to JSON API resources as *records*.
