# Filtering

This package supports the specification of [JSON API Fetching of Filtered Resources](http://jsonapi.org/format/1.0/#fetching-filtering). Filtering allows requests to remove certain resources not required.

# Using Filter Parameter

Per the specification, the client is able to request for the inclusion of related resources through the following HTTP request:

```http
GET /api/posts?filter[approved]=1 HTTP/2.0
Accept: application/vnd.api+json
``` 

However, by default, this package denies filtering attributes via parameter and would throw the follow error message.

```json
{
    "errors": [
        {
            "title": "Filter should contain only allowed values.",
            "source": {
                "parameter": "filter"
            }
        }
    ]
}

```

To allow certain attributes to be filtered using request parameters, it must be manually enabled through the resource's `Validators.php` file.

```php
namespace App\JsonApiV1\Posts;

class Validators extends AbstractValidatorProvider
{
    protected $allowedFilteringParameters = [
        'approved'
    ];

    /* Your code here ...*/
}

```

And then to put the filtering into action, you add the following part to your `Adapter.php` file.

```php
class Adapter extends AbstractAdapter
{
    /**
     * @param $query
     * @param Collection $filters
     * @return mixed
     */
    protected function filter($query, Collection $filters)
    {
        foreach ($filters as $filter_key => $filter_value) {
            if ($filter_key === 'approved') {
                $query->where($filter_key, $filter_value);
            }
        }
        return $query;
    }
}
```