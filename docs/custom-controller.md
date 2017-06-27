# Custom Controllers

Sometimes, we might need to customize what happens within a controller and still be able to make full use of this package.

## ReplyTrait

In order to customize the controller's responses, we must first include the `use ReplyTrait` in the controller.

```php
namespace App\Http\Controllers\Api;
 
use App\JsonApi\Users;
use App\User;
use CloudCreativity\JsonApi\Contracts\Http\ApiInterface;
use CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterface as JsonApiRequest;
use CloudCreativity\LaravelJsonApi\Http\Responses\ReplyTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller;
 
class UsersController extends Controller
{
    use ReplyTrait;
    
    /**
     * @var Users\Hydrator
     */
    private $hydrator;
    
    /**
     * UsersController constructor.
     *
     * @param Users\Hydrator $hydrator
     */
    public function __construct(Sites\Hydrator $hydrator)
    {
        $this->hydrator = $hydrator;
    }
}
```

## Structure

As mentioned before, there are mainly five functions: `index()`, `create()`, `read()`, `update()`, `delete()`. They are able to accept a few parameters and must make use of the reply trait to return a response.

The following are examples covering these functions. Do take note that you will need to import the namespaces correctly.

### Index

```php
    /**
     * @param ApiInterface $api
     * @param JsonApiRequest $request
     * @return mixed
     */
    public function index(ApiInterface $api, JsonApiRequest $request)
    {
        $store = $api->getStore();
        return $this->reply()->content($store->query(
            $request->getResourceType(),
            $request->getParameters()
        ));
    }
```

### Create

```php
    /**
     * @param JsonApiRequest $request
     * @return mixed
     */
    public function create(JsonApiRequest $request)
    {
        $resource = $request->getDocument()->getResource();
        // Add custom DB transaction & password hashing
        DB::transaction(function () {
          $record = new User($resource->getId()); // client generated id.
          $this->hydrator->hydrate($request->getDocument()->getResource(), $record);
          $record->password = Hash::make($record->password);
          $record->save();
        });
        return $this->reply()->created($record);
    }
```

### Read
```php
    /**
     * @param JsonApiRequest $request
     * @return mixed
     */
    public function read(JsonApiRequest $request)
    {
        return $this->reply()->content($request->getRecord());
    }
```
### Update
```php
    /**
     * @param JsonApiRequest $request
     * @return mixed
     */
    public function update(JsonApiRequest $request)
    {
        /** @var User $record */
        $record = $request->getRecord();
        $old_password = $record->password;
        $resource = $request->getDocument()->getResource();
        $this->hydrator->hydrate($resource, $record);
        // Check if password has changed
        if ($old_password !== $record->password) {
          $record->password = Hash::make($record->password);
        }
        $record->save();
        return $this->reply()->content($record);
    }
```

### Delete
```php
    /**
     * @param JsonApiRequest $request
     * @return mixed
     */
    public function delete(JsonApiRequest $request)
    {
        /** @var Site $record */
        $record = $request->getRecord();
        $record->delete();
        return $this->reply()->noContent();
    }
```
