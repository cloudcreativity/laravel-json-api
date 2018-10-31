# Filtering

The JSON API specification reserves the `filter` query parameter for 
[filtering resources](http://jsonapi.org/format/1.0/#fetching-filtering). 
Filtering allows clients to search resources and reduce the number of resources returned in a response.

Although the specification reserves this parameter for filtering operations, it is agnostic about the strategy
that a server should implement for filtering operations. We concur with this conclusion because filtering is
highly coupled with the application's logic and choice of data storage.

This package therefore provides the following capabilities:

- Validation of the `filter` parameter.
- An easy hook in the Eloquent adapter to convert validated filter parameters to database queries. 

## Example Requests

Filtering logic is applied when:

- Fetching resources, e.g. `GET /api/posts`.
- Fetching a resource, e.g. `GET /api/posts/123`.
- Fetching related resources, e.g. `GET /api/countries/1/posts`.
- Fetching relationship identifiers, e.g. `GET /api/countries/1/relationships/posts`.

As an example, imagine our `posts` resource has a `title` filter that searches for posts that have titles
starting with the provided value.

This request would return any post that has a title starting with `Hello`:

```http
GET /api/posts?filter[title]=Hello HTTP/1.1
Accept: application/vnd.api+json
```

This request would return post `123` if that post has a title starting with `Hello`:

```http
GET /api/posts/123?filter[title]=Hello HTTP/1.1
Accept: application/vnd.api+json
```

This request would return any post that is related to country `1` that has a title starting with `Hello`:

```http
GET /api/countries/1/posts?filter[title]=Hello HTTP/1.1
Accept: application/vnd.api+json
```

This request would return the resource identifiers of any post that is related to country `1` that has
a title starting with `Hello`:

```http
GET /api/countries/1/relationships/posts?filter[title]=Hello HTTP/1.1
Accept: application/vnd.api+json
```

## Disallowing Filtering

If your resource does not support filtering, you should reject any request that contains the `filter`
parameter. You can do this by disallowing filtering parameters on your [Validators](../basics/validators.md)
class as follows:

```php
class Validators extends AbstractValidators
{
    // ...
    
    protected $allowedFilteringParameters = [];

}
```

## Validation

Filter parameters should always be validated to ensure that their use in database queries is valid. You can
validate them in your [Validators](../basics/validators.md) query rules. For example:

```php
class Validators extends AbstractValidators
{
    // ...

    protected function queryRules(): array
    {
        return [
            'filter.title' => 'filled|string',
            'filter.slug' => 'filled|string',
            'filter.authors' => 'array|min:1',
            'filter.authors.*' => 'integer',
        ];
    }

}
```

By default we allow a client to submit any filter parameters as we assume that you will validate the values
of expected filters as in the example above. However, you can whitelist expected filter parameters by listing
them on the `$allowedFilteringParameters` of your validators class. For example:

```php
class Validators extends AbstractValidators
{
    // ...
    
    protected $allowedFilteringParameters = ['title', 'authors'];

    protected function queryRules(): array
    {
        return [
            'filter.title' => 'filled|string',
            'filter.slug' => 'filled|string',
            'filter.authors' => 'array|min:1',
            'filter.authors.*' => 'integer',
        ];
    }

}
```

Any requests that contain filter keys that are not in your allowed filtering parameters list will be rejected
with a `400 Bad Request` response, for example:

```http
HTTP/1.1 400 Bad Request
Content-Type: application/vnd.api+json

{
    "errors": [
        {
            "title": "Invalid Query Parameter",
            "status": "400",
            "detail": "Filter parameter foo is not allowed.",
            "source": {
                "parameter": "filter"
            }
        }
    ]
}
```

## Implementing Filtering

The Eloquent adapter provides a `filter` method that allows you to implement your filtering logic.
This method is provided with an Eloquent query builder and the filters provided by the client.

For example, our `posts` adapter filtering implementation could be:

```php
class Adapter extends AbstractAdapter
{
    /**
     * @param $query
     * @param Collection $filters
     * @return void
     */
    protected function filter($query, Collection $filters)
    {
        if ($title = $filters->get('title')) {
            $query->where('posts.title', 'like', "{$title}%");
        }
        
        if ($authors = $filters->get('authors')) {
            $query->whereIn('posts.user_id', $authors); 
        }
    }
}
```

> As filters are also applied when filtering the resource through a relationship, it is good practice
to qualify any column names.
