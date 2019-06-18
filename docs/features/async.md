# Asynchronous Processing

The JSON API specification 
[provides a recommendation](https://jsonapi.org/recommendations/#asynchronous-processing)
for how APIs can implement long running processes. For example, if the operation to create a 
resource takes a long time, it is more appropriate to process the creation using 
[Laravel's queue system](https://laravel.com/docs/queues)
and return a `202 Accepted` response to the client.

This package provides an opt-in implementation of the JSON API's asynchronous processing recommendation
that integrates with Laravel's queue. This works by storing information about the dispatched job
in a database, and using Laravel's queue events to updating the stored information.

## Installation

### Migrations

By default this package does not run migrations to create the database tables required to store
information on the jobs that have been dispatched by the API. You must therefore opt-in to the
migrations in the `register` method of your `AppServiceProvider`:

```php
<?php

namespace App\Providers;

use CloudCreativity\LaravelJsonApi\LaravelJsonApi;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    
    // ...
    
    public function register()
    {
        LaravelJsonApi::runMigrations();
    }
    
}
```

### Migration Customisation

If you want to customise the migrations, you can publish them as follows:

```bash
$ php artisan vendor:publish --tag="json-api-migrations"
```

If you do this, you **must not** call `LaravelJsonApi::runMigrations()` in your service provider.

### Generate Resource Classes

You now need to generate JSON API classes for the resource type that will represent the asynchronous
processes in your API. We do not provide these by default because the logic of how you want to page,
filter, etc. your resources is specific to your own API. This also means you can serialize any
attributes you want in the resource schema, and use your own API's convention for attribute names.

To generate the classes, run the following command:

```php
$ php artisan make:json-api:resource -e queue-jobs
```

> Replace `queue-jobs` in the above command if you want to call the resource something different.
If you use a different name, you will need to change the `jobs.resource` config setting in your
API's configuration file.

In the generated schema, you will need to add the `AsyncSchema` trait, for example:

```php
use CloudCreativity\LaravelJsonApi\Queue\AsyncSchema;
use Neomerx\JsonApi\Schema\SchemaProvider;

class Schema extends SchemaProvider
{
    use AsyncSchema;
    
    // ...
}
```

### Model Customisation

By default the implementation uses the `CloudCreativity\LaravelJsonApi\Queue\ClientJob` model.
If you want to use a different model, then you can change this by editing the `jobs.model` config
setting in your API's configuration file.

Note that if you use a different model, you may also want to customise the migration as described
above.

If you are not extending the `ClientJob` model provided by this package, note that your custom
model must implement the `CloudCreativity\LaravelJsonApi\Contracts\Queue\AsynchronousProcess`
interface.

## Dispatching Jobs

For a Laravel queue job to appear as an asynchronous process in your API, you must add the
`CloudCreativity\LaravelJsonApi\Queue\ClientDispatchable` trait to it and use this to dispatch
the job.

For example:

```php
namespace App\Jobs;

use CloudCreativity\LaravelJsonApi\Queue\ClientDispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessPodcast implements ShouldQueue
{

    use ClientDispatchable;
    
    // ...
}

```

The job can then be dispatched as follows:

```php
/** @var \CloudCreativity\LaravelJsonApi\Queue\ClientJob $process */
$process = ProcessPodcast::client($podcast)->dispatch();
```

The object returned by the static `client` method extends Laravel's `PendingDispatch` class. This
means you can use any of the normal Laravel methods. The only difference is you **must** call the
`dispatch` method at the end of the chain so that you have access to the process that was stored
and can be serialized into JSON by your API.

You can use this method of dispatching jobs in either 
[Controller Hooks](../basics/controllers.md) or within
[Resource Adapters](../basics/adapters.md), depending on your preference.

### Dispatching in Controllers

You can use controller hooks to return asynchronous processes. For example, if you needed
to process a podcast after creating a podcast model you could use the `created` hook:

```php
use App\Podcast;
use App\Jobs\ProcessPodcast;
use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;

class PodcastsController extends JsonApiController
{

    // ...

    protected function created(Podcast $podcast)
    {
        return ProcessPodcast::client($podcast)->dispatch();
    }
}
```

> The `creating`, `created`, `updating`, `updated`, `saving`, `saved`, `deleting` and `deleted`
hooks will be the most common ones to use for asynchronous processes.

### Dispatching in Adapters

If you prefer to dispatch your jobs in a resource adapters, then the adapters support returning
asynchronous processes. To do this, return an asynchronous process from any of the adapter hooks.

For example, to process a podcast after creating it:

```php
namespace App\JsonApi\Podcasts;

use App\Jobs\ProcessPodcast;
use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

class Adapter extends AbstractAdapter
{

    // ...

    protected function created(Podcast $podcast)
    {
        return ProcessPodcast::client($podcast)->dispatch();
    }
}
```

## Linking Processes to Created Resources

If a dispatched job creates a new resource (e.g. a new model), there is one additional step you will
need to follow in the job's `handle` method. This is to link the stored process to the resource that was
created as a result of the job completing successfully. The link must exist otherwise your API
will not be able to inform a client of the location of the created resource once the job is complete. 

You can easily create this link by calling the `didCreate` method that the `ClientDispatchable` 
trait adds to your job. For example:

```php
namespace App\Jobs;

use CloudCreativity\LaravelJsonApi\Queue\ClientDispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessPodcast implements ShouldQueue
{

    use ClientDispatchable;
    
    // ...
    
    public function handle()
    {
        // ...logic to process a podcast
        
        $this->didCreate($podcast);
    }
}
```

## Manually Marking Client Jobs as Complete

This package will, in most cases, automatically mark the stored representation of the job as complete.
We do this by listening the Laravel's queue events.

There is one scenario where we cannot do this: if your job deletes a model during its `handle` method.
This is because we cannot deserialize the job in our listener without causing a `ModelNotFoundException`.

In these scenarios, you will need to manually mark the stored representation of the job as complete.
Use the `didComplete()` method, which accepts one argument: a boolean indicating success (will be
`true` if not provided).

For example:

```php
namespace App\Jobs;

use CloudCreativity\LaravelJsonApi\Queue\ClientDispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;

class RemovePodcast implements ShouldQueue
{

    use ClientDispatchable;

    // ...

    public function handle()
    {
        // ...logic to remove a podcast.

        $this->podcast->delete();
        $this->didComplete();
    }
}
```

## Routing

The final step of setup is to enable asynchronous process routes on a resource. These
routes allow a client to check the current status of a process.

For example, if our `podcasts` resource used asynchronous processes when a podcast is
created, we would need to add the following to our [route definitions](../basics/routing.md):

```php
JsonApi::register('default')->withNamespace('Api')->routes(function ($api) {
    $api->resource('podcasts')->async();
});
```

This enables the following routes:

- `GET /podcasts/queue-jobs`: this lists all `queue-jobs` resources for the `podcasts`
resource type.
- `GET /podcasts/queue-jobs/<UUID>`: this retrieves a specific `queue-jobs` resource 
for the `podcasts` resource type.

The resource type `queue-jobs` is the name used in the JSON API's recommendation for
asynchronous processing. If you want to use a resource type, then you can change this 
by editing the `jobs.resource` config setting in your API's configuration file.

Note that we assume the resource id of a process is a valid UUID. If you use something
different, then you can pass a constraint into the `async()` method, as follows:

```php
JsonApi::register('default')->withNamespace('Api')->routes(function ($api) {
    $api->resource('podcasts')->async('^\d+$');
});
```

## HTTP Requests and Responses

Once you have followed the above instructions, you can now make HTTP requests and receive
asynchronous process responses that following the 
[JSON API recommendation.](https://jsonapi.org/recommendations/#asynchronous-processing)

For example, a request to create a podcast would receive the following response:

```http
HTTP/1.1 202 Accepted
Content-Type: application/vnd.api+json
Content-Location: http://homestead.local/podcasts/queue-jobs/1680e9a0-6643-42ab-8314-1f60f0b6a6b2

{
  "data": {
    "type": "queue-jobs",
    "id": "1680e9a0-6643-42ab-8314-1f60f0b6a6b2",
    "attributes": {
      "created-at": "2018-12-25T12:00:00",
      "updated-at": "2018-12-25T12:00:00"
    },
    "links": {
      "self": "/podcasts/queue-jobs/1680e9a0-6643-42ab-8314-1f60f0b6a6b2"
    }
  }
}
```

> You are able to include a lot more attributes by adding them to your queue-jobs resource schema.

To check the status of the job process, a client can send a request to the `Content-Location` given
in the previous response:

```http
GET /podcasts/queue-jobs/1680e9a0-6643-42ab-8314-1f60f0b6a6b2 HTTP/1.1
Accept: application/vnd.api+json
```

If the job is still pending, a `200 OK` response will be returned and the content will contain the
`queue-jobs` resource.

When the job process is done, the response will return a `303 See Other` status. This will contain
a `Location` header giving the URL of the created podcast resource:

```http
HTTP/1.1 303 See other
Content-Type: application/vnd.api+json
Location: http://homestead.local/podcasts/4577
```
