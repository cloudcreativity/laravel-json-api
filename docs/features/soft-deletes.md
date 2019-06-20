# Soft Deleting

This package provides that ability to support soft-deleting and force-deleting of Eloquent models. This is
implemented by adding a trait to your resource's Eloquent adapter.

## Implementing Soft-Deleting

By default the Eloquent resource adapter uses `$model->delete()` when a client sends a `DELETE` request.
This means that if your model implements [soft-deleting](https://laravel.com/docs/eloquent#deleting-models),
a client delete request will result in the model being soft-deleted in the database. A subsequent request to
`GET` the resource will result in a `404 Not Found` as by default the Eloquent resource adapter does not
find soft-deleted models.

This behaviour can be modified by applying the `SoftDeletesModels` trait to your Eloquent adapter, as follows: 

```php
namespace App\JsonApi\Posts;

use App\Post;
use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use CloudCreativity\LaravelJsonApi\Eloquent\Concerns\SoftDeletesModels;

class Adapter extends AbstractAdapter
{

    use SoftDeletesModels;

    // ...

}
```

With this trait applied to your adapter, the client can now send soft-delete and restore requests by `PATCH`ing
the resource, while using a `DELETE` request to force-delete a resource. These requests are described below.
In addition, a request to `GET` a soft-deleted resource will result in that resource being returned to the client,
rather than a `404 Not Found` response.

> Tip: By default the trait uses the `deleted-at` field to toggle the soft-delete status of a resource, as shown
in the example below. You would also need to add the `deleted-at` field to the `getAttributes` method on your
resource schema. The field is customisable - see below for how to change the field name.

## Soft-Deleting and Restoring Resources

Once the `SoftDeletesModels` trait is applied to your adapter, a client can send the following request to
soft-delete a resource:

```http
PATCH /api/posts/1 HTTP/1.1
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json

{
  "data": {
    "type": "posts",
    "id": "1",
    "attributes": {
      "deleted-at": "2018-12-25T12:00:00Z"
    }
  }
}
```

This will result in the model being soft-deleted by your adapter. Note that the client can choose to send
other attributes at the same time as soft-deleting the resource.

To restore a soft-deleted resource, a client can send the following request:

```http
PATCH /api/posts/1 HTTP/1.1
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json

{
  "data": {
    "type": "posts",
    "id": "1",
    "attributes": {
      "deleted-at": null
    }
  }
}
```

> See below for how to customise the soft-delete attribute name. In addition, you can use a boolean to toggle
the soft-delete status.

> Tip: Make sure you add a validation rule for the `deleted-at` attribute on your resource's validators class.

## Force-Deleting Resources

When the `SoftDeletesModels` trait is applied to your adapter, the following client request will force-delete
the resource:

```http
DELETE /api/posts/1 HTTP/1.1
Accept: application/vnd.api+json
```

## Filtering Soft-Deleted Resources

The `SoftDeletesModels` **does not** add any logic to your adapter to handle filtering soft-deleted resources. This
means without modification it will not include any soft-deleted resources in any filter requests.

This is because filtering logic is not defined by the JSON API spec, so is dependent on how your application has
chosen to implement filtering. You will therefore need to add some code to the `filter` method on your adapter
if you would like to provide options to the client to include or exclude soft-deleted resources in filter results.

This is easy to implement. For example, we could add boolean filters to allow the client to indicate if it wanted
to include soft-deleted resources in filter results:

```php
namespace App\JsonApi\Posts;

use App\Post;
use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use CloudCreativity\LaravelJsonApi\Eloquent\Concerns\SoftDeletesModels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Adapter extends AbstractAdapter
{

    use SoftDeletesModels;

    // ...

    /**
     * @param Builder $query
     * @param Collection $filters
     * @return void
     */
    protected function filter($query, Collection $filters)
    {
        if (true == $filters->get('with-trashed')) {
            $query->withTrashed();
        } else if (true == $filters->get('only-trashed')) {
            $query->onlyTrashed();
        }
        
        // ...other filter logic
    }
}
```

> By adding this to your filter logic on your adapter, this will also apply when the `posts` resource appears in
any `to-many` relationship.

## Customising the Soft-Delete Field

The `SoftDeletesModels` allows you to customise the name of the field that is used for the soft-delete attribute.
In addition, it will support the client sending a boolean instead of a date for the soft-delete attribute's value.

For example, if we wanted the soft-delete status to be a boolean with a field name of `archived`, add the
`$softDeleteField` property to your adapter:

```php
namespace App\JsonApi\Posts;

use App\Post;
use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use CloudCreativity\LaravelJsonApi\Eloquent\Concerns\SoftDeletesModels;

class Adapter extends AbstractAdapter
{

    use SoftDeletesModels;
    
    protected $softDeleteField = 'archived';

    // ...

}
```

> You can use dot notation for the `$softDeleteField` value, if you are using a nested attribute field.

The client can now send the following request to soft-delete a resource:

```http
PATCH /api/posts/1 HTTP/1.1
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json

{
  "data": {
    "type": "posts",
    "id": "1",
    "attributes": {
      "archived": true
    }
  }
}
```

And this request will restore a soft-deleted resource:

```http
PATCH /api/posts/1 HTTP/1.1
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json

{
  "data": {
    "type": "posts",
    "id": "1",
    "attributes": {
      "archived": false
    }
  }
}
```

> Tip: You would also need to add the `archived` attribute to your resource's schema.
