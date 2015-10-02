#`league/fractal` WRAPPER FOR LARAVEL 5/LUMEN#

[![Latest Stable Version](https://poser.pugx.org/appkr/fractal/v/stable)](https://packagist.org/packages/appkr/fractal) 
[![Total Downloads](https://poser.pugx.org/appkr/fractal/downloads)](https://packagist.org/packages/appkr/fractal) 
[![Latest Unstable Version](https://poser.pugx.org/appkr/fractal/v/unstable)](https://packagist.org/packages/appkr/fractal) 
[![License](https://poser.pugx.org/appkr/fractal/license)](https://packagist.org/packages/appkr/fractal)

##ABOUT

This is a package, or rather an **opinionated/laravelish use case of the famous [`league/fractal`](https://github.com/thephpleague/fractal) package for Laravel 5 and Lumen**. This package was started to fulfill a personal RESTful API service needs. And provided as a separate package, hoping users quickly build his/her RESTful API. 

Among **1. METHOD**, **2. RESOURCE**, and **3. RESPONSE**, which is 3 pillars of REST, this package is mainly focusing on a **3. RESPONSE(=view layer)**. For others, I recommend you to follow Laravel/Lumen's native usage. By reading this readme and following along the bundled examples, I hope you understand REST principles, and build a beautiful APIs that everybody can understand easily.

I know RESTful API is a big topic. Take a deep breadth, and let's get start. One caveat before get started is that REST is not a strict spec or rule, but a guideline. The more RESTful, the easier to be read by your API clients.

**`Note`** _A slide explaining RESTful API is available at [RESTful API 제대로 만들기 by Appkr](http://bit.ly/restful-api), [RESTful API Design by apigee](http://www.slideshare.net/apigee/restful-api-design-second-edition). A code base for RESTful API service that utilize this package is available at [https://github.com/appkr/rest](https://github.com/appkr/rest)._

###GOAL OF THIS PACKAGE

1. Provides easy access to the `league/fractal`'s core instance (ServiceProvider).
2. Provides easy way of make transformed/serialized http response.
3. Provides configuration capability for the `league/fractal` and API response format.
4. Provides examples, so that users can quickly copy &amp; paste into his/her project.

---

##LARAVEL/LUMEN IMPLEMENTATION EXAMPLE

**1. METHOD** and **2. RESOURCE** can be easily handled by Laravel/Lumen routes file, `app/Http/routes.php`.

```php
Route::resource(
    'things',
    ThingsController::class,
    ['except' => ['create', 'edit']]
);
```

In response to Laravel/Lumen route definition, `Appkr\Fractal\Http\Response` instance is being injected into `ThingsController` to properly make JSON responses in RESTful fashion (**3. RESPONSE**).

```php
<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ThingsRequest;
use App\Thing;
use App\Transformers\ThingTransformer;
use Appkr\Fractal\Http\Response;

class ThingsController extends Controller
{
    protected $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function index()
    {
        return $this->response->withPagination(
            Thing::latest()->paginate(25),
            new ThingTransformer
        );
    }

    public function store(ThingsRequest $request)
    {
        return $this->response->created(Thing::create(array_merge(
            $request->all(),
            $request->user()->id
        )));
    }

    public function show($id)
    {
        return $this->response->withItem(
            Thing::findOrFail($id),
            new ThingTransformer
        );
    }

    public function update(ThingsRequest $request, $id)
    {
        $thing = Thing::findOrFail($id);

        return ($thing->update($request->all()))
            ? $this->response->success('Updated')
            : $this->response->error('Fail to update');
    }

    public function destroy($id)
    {
        $thing = Thing::findOrFail($id);

        return ($thing->delete())
            ? $this->response->success('Deleted')
            : $this->response->error('Fail to delete');
    }
}
```

---

##INDEX

- [PACKAGE INSTALL](#install)
- [BUNDLED EXAMPLE](#example)
- [AVAILABLE RESPONSE METHOD](#method)

---

<a name="install"></a>
##PACKAGE INSTALL

**Setp #1:** Composer.

```json
// composer.json
"require": {
  "appkr/fractal": "0.5.*",
  "league/fractal": "@dev",
}
```

```bash
$ composer update
```

**`Important`** _This package depends on the `setMeta()` api of the `league/fractal` which is available only at 0.13.*@dev. But the `league/fractal` has not yet been tagged as stable, so we need to explicitly designate `league/fractal` version at our root project's composer.json. Note that I will update this readme as soon as the `league/fractal` being tagged._

**Step #2:** Add the service provider.

```php
// For Laravel - config/app.php
'providers'=> [
    Appkr\Fractal\ApiServiceProvider::class,
]

// For Lumen - boostrap/app.php
$app->register(Appkr\Fractal\ApiServiceProvider::class);
```

**Step #3:** [OPTIONAL] Publish assets.

```bash
// For Laravel only
$ php artisan vendor:publish --provider="Appkr\Fractal\ApiServiceProvider"
```

The config file is located at `config/fractal.php`.

Done !

---

<a name="example"></a>
##BUNDLED EXAMPLE

The package is bundled with a simple API example. It includes:

- Database migrations and seeder
- routes definition, Eloquent Model and corresponding Controller
- FormRequest *(Laravel only)*
- Transformer
- Integration Test

Follow the guide to activate and test the example.

**Step #1:** Activate examples

```php
// Uncomment the line at vendor/appkr/fractal/src/ApiServiceProvider.php
$this->publishExamples();
```

**Step #2:** Migrate and seed tables

```bash
// Migrate/seed tables at a console
$ php artisan migrate --path="vendor/appkr/fractal/database/migrations"
$ php artisan db:seed --class="Appkr\Fractal\Example\DatabaseSeeder"
```

**Step #3:** Boot up a local server and open at a browser

```bash
// Boot up a local server
$ php artisan serve
```

Head on to `http://localhost:8000/v1/things`, and you should see a well formatted json response.

**Step #4:** [OPTIONAL] Run integration test

```bash
// For Laravel
$ phpunit vendor/appkr/fractal/src/example/ThingApiTestForLaravel.php

// For Lumen
$ phpunit vendor/appkr/fractal/src/example/ThingApiTestForLumen.php
```

**`Note`** _If you finished evaluating the example, don't forget to rollback the migration and re-comment the unnecessary lines at `ApiServiceProvider`._

---

<a name="method"></a>
##AVAILABLE RESPONSE METHODS

The following is a list of response methods that `Appkr\Fractal\Http\Response` provides, and that you can use in `YourController` to format API response.

```php
// Generic response. 
// If valid callback parameter is provided, jsonp response can be provided.
// This is a very base method. All other responses are utilizing this.
respond(array $payload);

// Respond collection of resources
// If $transformer is not given as the second argument,
// this class does its best to transform the payload to a simple array
withCollection(
    \Illuminate\Database\Eloquent\Collection $collection, 
    \League\Fractal\TransformerAbstract|null $transformer, 
    string|null $resourceKey // for JsonApiSerializer only
);

// Respond single item
withItem(
    \Illuminate\Database\Eloquent\Model $model, 
    \League\Fractal\TransformerAbstract|null $transformer, 
    string|null $resourceKey // for JsonApiSerializer only
);

// Respond collection of resources with pagination
withPagination(
    \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator, 
    \League\Fractal\TransformerAbstract|null $transformer, 
    string|null $resourceKey // for JsonApiSerializer only
);

// Respond json formatted success message
// fractal.php provides configuration capability
success(string|array $message);

// Respond 201
// If an Eloquent model is given at an argument,
// the class tries its best to transform the model to a simple array
created(string|array|\Illuminate\Database\Eloquent\Model $primitive);

// Respond 204
noContent();

// Generic error response
// This is another base method. Every other error responses use this.
// If an instance of \Exception is given as an argument,
// this class does its best to properly format a message and status code
error(string|array|\Exception|null $message);

// Respond 401
// Note that this actually means unauthenticated
unauthorizedError(string|array|null $message);

// Respond 403
// Note that this actually means unauthorized
forbiddenError(string|array|null $message);

// Respond 404
notFoundError(string|array|null $message);

// Respond 406
notAcceptableError(string|array|null $message);

// Respond 409
conflictError(string|array|null $message);

// Respond 422
unprocessableError(string|array|null $message);

// Respond 500
internalError(string|array|null $message);

// Set http status code
// This method is chainable
setStatusCode(int $statusCode);

// Set http response header
// This method is chainable
setHeaders(array $headers);

// Set additional meta data
// This method is chainable
setMeta(array $meta);
```

####AVAILABLE HELPER METHODS
```
// Determine if the current framework is Laravel
is_laravel();

// Determine if the current framework is Lumen
is_lumen();

// Determine if the current version of framework is based on 5.1
is_51();

// Determine if the current request is generated from an api client
is_api_request();

// Determine if the request is for update
is_update_request();

// Determine if the request is for delete
is_delete_request();
```

---

##LICENSE

[The MIT License](https://raw.githubusercontent.com/appkr/fractal/master/LICENSE)
