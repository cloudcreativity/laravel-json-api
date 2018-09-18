# Broadcasting

## Introduction

Sometimes you will need to broadcast data in the JSON API format. For example, we use 
[Ember.js](https://emberjs.com) for our front-ends Javascript applications, that natively supports JSON API. 
We therefore broadcast using the JSON API format.

## Broadcasting Events

We have included a `BroadcastsData` trait that can be applied to a broadcastable event to help with serializing
data to the JSON API format. This adds a `serializeData()` method to your event that you can use in Laravel's
`broadcastWith()` hook. For example:

```php
<?php

use App\Post;
use CloudCreativity\LaravelJsonApi\Broadcasting\BroadcastsData;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class PostPublished implements ShouldBroadcast
{

  use SerializesModels, BroadcastsData;

  /**
   * @var Post
   */
  public $post;

  /**
   * PostPublished constructor.
   *
   * @param Post $post
   */
  public function __construct(Post $post)
  {
    $this->post = $post;
  }

  /**
   * @return array
   */
  public function broadcastOn()
  {
    return [new Channel('public')];
  }

  /**
   * @return array
   */
  public function broadcastWith()
  {
    return $this->serializeData($this->post);
  }
}
```

### Specifying the API

In most scenarios, the broadcasting of the data will occur as a queued job. This means that the event will need to
know which JSON API to use to serialize the data.

In the example above, no API is specified so the default API will be used. If you need to use another API, you
can set the `broadcastApi` property. For example, this will use the `v1` API:

```php
class PostPublished implements ShouldBroadcast
{

  use SerializesModels, BroadcastsData;
  
  protected $broadcastApi = 'v1';
  
  // ...
}
```

If you need to programmatically work out the API, overload the `broadcastApi` method.

### Including Resources

If you want the broadcast data to be a [compound document](http://jsonapi.org/format/#document-compound-documents),
you can specify the include paths when serializing the data. Pass the include paths as the second
function argument, for example:

```php
protected function broadcastWith()
{
    return $this->serializeData($this->post, ['author', 'tags']);
}
```

### Sparse Fieldsets

You can also choose which fields to serialize when creating the broadcast data. Sparse fieldsets are
defined using an array keyed by the resource type, with the values being the fields that should be
serialized for that type.

In the following example, only the `title`, `content` and `author` fields will be serialized for `posts`
resources, and only the `name` field for `users` resources.

```php
protected function broadcastWith()
{
    $fieldsets = [
        'posts' => ['title', 'content', 'author'],
        'users' => ['name']
    ];

    return $this->serializeData($this->post, [], $fieldsets);
}
```
