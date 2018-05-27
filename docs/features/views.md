# Encoding in Views

## Introduction

Sometimes it is necessary to encode JSON API resources into an HTML document. For example, you might want to
pre-load data into a page for your Javascript components to use. This package provides Blade helpers to do this,
plus there are also instructions below on how to do this in regular PHP view files.

## Blade

### Choosing An Encoder

You can set the encoder to use with the `@jsonapi` directive. For example, `@jsonapi('v1')` will use an encoder
from your JSON API named `v1`.

You can also configure the encoding options using the second argument, and the encoding depth using the third. 
The options and depth are identical to the options and depth passed to the native PHP `json_encode()` function.
For example:

```html
@jsonapi('v1', JSON_PRETTY_PRINT, 250)
```

Note that if you do not call the `@jsonapi` directive in your templates, then the default API will be used.

### Encoding Data

To encode in Blade templates, use the `@encode` directive. For example:

```html
@jsonapi('v1', JSON_PRETTY_PRINT)
<script type="application/vnd.api+json">
    @encode($post)
</script>
```

This will output:

```html
<script type="application/vnd.api+json">
    {
        "data": {
            "type": "posts",
            "id": "1",
            "attributes": {
                "content": "Hello World"
            },
            "relationships": {
                "author": {
                    "data": {
                        "type": "users",
                        "id": "2"
                    }
                }
            }
        }
    }
</script>
```

The `@encode` directive takes two additional arguments - the `include` paths and the `fields` to encode. The
include paths can be a string or an array of strings. The fields must be an array keyed by resource type, with the
value being an array of fields to encode.

```html
@jsonapi('v1', JSON_PRETTY_PRINT)
<script type="application/vnd.api+json">
    @encode($post, 'author', ['author' => ['name']])
</script>
```

This will output:

```html
<script type="application/vnd.api+json">
    {
        "data": {
            "type": "posts",
            "id": "1",
            "attributes": {
                "content": "Hello World"
            },
            "relationships": {
                "author": {
                    "data": {
                        "type": "users",
                        "id": "2"
                    }
                }
            }
        },
        "include": [
            {
                "type": "users",
                "id": "2",
                "attributes": {
                    "name": "Frankie Manning"
                }
            }
        ]
    }
</script>
```

## Non-Blade

If you are not using Blade, you can still use the same functionality in your PHP view scripts. To choose an
encoder:

```php
<?php app('json-api.renderer')->with('v1', JSON_PRETTY_PRINT); ?>
```

And then to encode data:

```php
<?php echo app('json-api.renderer')->encode($post, 'author'); ?>
```
