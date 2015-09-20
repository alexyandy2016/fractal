#Fractal wrapper for Laravel 5/Lumen#

[![Latest Stable Version](https://poser.pugx.org/appkr/fractal/v/stable)](https://packagist.org/packages/appkr/fractal) [![Total Downloads](https://poser.pugx.org/appkr/fractal/downloads)](https://packagist.org/packages/appkr/fractal) [![Latest Unstable Version](https://poser.pugx.org/appkr/fractal/v/unstable)](https://packagist.org/packages/appkr/fractal) [![License](https://poser.pugx.org/appkr/fractal/license)](https://packagist.org/packages/appkr/fractal)

This is a package, or rather, an **opinionated/laravelish use case of the famous [league/fractal](https://github.com/thephpleague/fractal) package for Laravel 5 and Lumen**.

This project was started to fulfill a personal RESTful API service needs. In an initial attempt to evaluate various php API packages for Laravel, I found that the features of those packages providing are well excessive of my requirement.

If your requirement is simple like mine, this is the right package. But if you need more delicate package, head over to [chiraggude/awesome-laravel](https://github.com/chiraggude/awesome-laravel#restful-apis) to find a right one.

Using this package, I didn't want user of this package to sacrifice Laravel's recommended coding practices without having to learn the package specific syntax/usage. And most importantly, I wanted he/she could build his/her API service quickly based on the examples provided.

## Example Implementation
```php
<?php

namespace App\Http\Controllers\Api\V1;

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

##Index

- [Goal](#goal)
- [How to Install](#install)
- [Bundled Example](#example)
- [Best Practices](#best-practices)
    - [Route(API Endpoints)](#route)
    - [Controller](#controller)
    - [FormRequest](#form-request)
    - [Handle TokenMismatchException](#token)
    - [Formatting Laravel's General Exceptions.](#exception-formatting)
    - [CORS in Javascript Client](#cors)
- [Avaliable Response Methods](#api)
- [Access API Endpoints from a Client](#client)

---

##[Goal](#goal)
1. Provides easy access to Fractal instance at Laravel 5/Lumen (ServiceProvider).
2. Provides easy way of make a Fractal transformed/serialized http response.
3. Provides configuration capability for Fractal and response format.
4. Provides examples, so that users can quickly copy & paste into his/her project.

##[How to Install](#install)
**Setp #1:** Composer.

```json
"require": {
  "appkr/fractal": "0.5.*",
  "league/fractal": "@dev",
}
```

```bash
$ composer update
```

**`Important`** _This package depends on the `setMeta()` api of the `league/fractal` which is available only at 0.13.*@dev. But the `league/fractal` has not been tagged as stable yet, so we need to explicitly designate `league/fractal` version at our root project's composer.json. Note that I will update this readme as soon as the `league/fractal` being tagged._

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

##[Avaliable Response Methods](#api)

This is a list of apis that `Appkr\Fractal\Http\Response` provides. You can think of this as a view layer for your restful service:

```php
// Generic response. 
// If valid callback parameter is provided, jsonp response can be provided.
// This is a very base method. All other responses are utilizing this.
respond(array $payload)

// Respond collection of resources
// If $transformer is not given as the second argument,
// this class does its best to transform the payload to a simple array
withCollection(
    \Illuminate\Database\Eloquent\Collection $collection, 
    \League\Fractal\TransformerAbstract|null $transformer, 
    string $resourceKey // for JsonApiSerializer only
)

// Respond single item
withItem(
    \Illuminate\Database\Eloquent\Model $model, 
    \League\Fractal\TransformerAbstract|null $transformer, 
    string $resourceKey // for JsonApiSerializer only
)

// Respond collection of resources with pagination
withPagination(
    \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator, 
    \League\Fractal\TransformerAbstract|null $transformer, 
    string $resourceKey // for JsonApiSerializer only
)

// Respond json formatted success message
// fractal.php provides configuration capability
success(string|array $message)

// Respond 201
// If an Eloquent model is given at an argument,
// the class tries its best to transform the model to a simple array
created(string|array|\Illuminate\Database\Eloquent\Model $primitive)

// Respond 204
noContent()

// Generic error response
// This is another base method. Every other error responses use this.
// If an instance of \Exception is given as an argument,
// this class does its best to properly format a message and status code
error(string|array|\Exception $message)

// Respond 401
// Note that this actually means unauthenticated
unauthorizedError(string|array $message)

// Respond 403
// Note that this actually means unauthorized
forbiddenError(string|array $message)

// Respond 404
notFoundError(string|array $message)

// Respond 406
notAcceptableError(string|array $message)

// Respond 409
conflictError(string|array $message)

// Respond 422
unprocessableError(string|array $message)

// Respond 500
internalError(string|array $message)

// Set http status code
// This method is chainable
setStatusCode(int $statusCode)

// Set http response header
// This method is chainable
setHeaders(array $headers)

// Set additional meta data
// This method is chainable
setMeta(array $meta)
```

### Available helper methods
```
// Determine the current framework is Laravel
is_laravel()

// Determine the current framework is Lumen
is_lumen()

// Determine if the current version of framework is based on 5.1
is_51()

// Determine if the current request is generated from an api client
is_api_request()

// Determine if the request is for update
is_update_request()

// Determine if the request is for delete
is_delete_request()
```

---

##[Bundled Example](#example)

The package is bundled with some simple examples. Those include:

- Database migrations and seeder
- routes definition, Eloquent Model and corresponding Controller
- FormRequest *(Laravel only)*
- Transformer
- Integration Test

If you want to see the the working example right away...

**Step #1:** Activate examples

```php
// Uncomment the line at vendor/appkr/fractal/src/ApiServiceProvider.php
$this->publishExamples();
```

**Step #2:** Migrate and seed tables

Prepare testing environment.

```bash
// create testing database
$ touch storage/database.sqlite
```

```php
// config/database.php
'default' => app()->environment('testing') ? 'sqlite' : env('DB_CONNECTION', 'mysql'),
```

Migrate and seed test tables.

```bash
// Migrate/seed tables at a console
$ php artisan migrate --path="vendor/appkr/fractal/database/migrations" --env="testing"
$ php artisan db:seed --class="Appkr\Fractal\Example\DatabaseSeeder" --env="testing"
```

**Step #3:** Boot up a test server and open an example endpoint at a browser

```bash
// Boot up a local server
$ php artisan serve --env="testing"
```

Head on to `http://localhost:8000/v1/things`, and you should see below:

```json
{
    "data": [
        {
            "id": 1,
            "title": "Quia sunt culpa numquam blanditiis alias dignissimos aspernatur.",
            "description": null,
            "deprecated": false,
            "created_at": "2015-09-19T08:07:55+0000",
            "link": {
                "rel": "self",
                "href": "http://localhost:8000/v1/things/1?include=author"
            },
            "author": "landen08"
        },
        {"...": "..."}
    ],
    "meta": {
        "version": 1,
        "documentation": "http://localhost:8000/v1/doc",
        "pagination": {
            "total": 106,
            "count": 25,
            "per_page": 25,
            "current_page": 1,
            "total_pages": 5,
            "links": {
                "next": "http://localhost:8000/v1/things/?page=2"
            }
        }
    }
```

**Step #5:** [OPTIONAL] phpunit

```bash
$ phpunit vendor/appkr/fractal/src/example/ThingApiTestForLaravel.php
```

**`Note`** If you finished evaluating the example, don't forget to rollback the migration and re-comment the unnecessary lines at `ApiServiceProvider`.

---

##[Best Practices](#best-practices)

###[Route (API Endpoints)](#route)
You can define your routes just like laravel way.

```php
// app/Http/routes.php

Route::group(['prefix' => 'v1'], function() {
    Route::resource(
        'things',
        ThingsController::class,
        ['except' => ['create', 'edit']]
    );
});

// For Lumen, checkout the example at vendor/appkr/fractal/src/example/routes-lumen.php
```


###[Controller](#controller)
It is recommended for your `ThingsController` to inject `Appkr\Fractal\Http\Response`. Alternative ways are using `Appkr\Fractal\ApiResponse` trait, or `app('api.response')`.

```php
// Injectting Appkr\Fractal\Http\Response
class ThingsController
{
    protected $respond;
    
    public function __construct(\Appkr\Fractal\Http\Response $respond)
    {
        $this->respond = $respond;
    }
    
    public function index() 
    {
        $this->respond->success('Hello API');
    }
}
```

```php
// Using trait
class ThingsController
{
    use Appkr\Fractal\Http\ApiResponse;
    
    public function index() {
        // We can use $this->response() or $this->respond() interchangeably
        $this->response()->success('Hello API');
    }
}
```

```php
// Or get the instance out of the Service Container
class ThingsController
{
    public function index() {
        app('api.response')->success('Hello API');
    }
}
```

###[FormRequest](#form-request)
It is recommended for `YourFormRequest` to extend `Appkr\Fractal\Request`. By extending the abstract request of this package, validation or authorization errors are properly formatted just like you configured at the `config/fractal.php`. Or you may move the class content of `Appkr\Fractal\Http\Request` to your `App\Http\Requests\Request`.

```php
class YourFormRequest extends \Appkr\Fractal\Http\Request {}
```

###[Transformer](#transformer)
This package follows original Fractal Transformer spec. Refer to the original [documentation](http://fractal.thephpleague.com/transformers/). An example transformers are provided with this package.

###[Handle TokenMismatchException](#csrf)
Laravel 5/Lumen throws `TokenMismatchException` when an client sends a post request(create, update, or delete) to the API endpoint. Because the client can exist in a separate domain or environment (e.g. android native application), no way for your server to publish csrf token to the client. It's more desirable to achieve a level of security through JWT [tymondesigns/jwt-auth](https://github.com/tymondesigns/jwt-auth) or equivalent measures. (Recommended articles: [scotch.io](https://scotch.io/tutorials/the-ins-and-outs-of-token-based-authentication), [angular-tips.com](http://angular-tips.com/blog/2014/05/json-web-tokens-introduction/))

So, let's just skip it. 

If your project is Laravel 5.1.* based, it couldn't be easier:

```php
// app/Http/Middleware/VerifyCsrfToken.php

protected $except = [
    'v1/*' // or config('fractal/pattern')
];
```

In Laravel 5.0/Lumen, I did it like this:

```php
// For Laravel 5.0 - app/Http/Middleware/VerifyCsrfToken.php

public function handle($request, \Closure $next) {
    if ($request->is('v1/*')) {
        return $next($request);
    }

    return parent::handle($request, $next);
}

// For Lumen, checkout Laravel\Lumen\Http\Middleware\VerifyCsrfToken 
// instead of App\Http\Middleware\VerifyCsrfToken.php
```

###[Formatting Laravel's General Exceptions](#exception-formatting)
For example, I thought 404 with json response was more appropriate for `Illuminate\Database\Eloquent\ModelNotFoundException`, when the request was originated from API clients, but the current version of Laravel just rendered 404 html page. To properly format this, I did:

```php
// app/Exceptions/Handlers.php

public function render($request, Exception $e) 
{
    // We can use is_api_request() helper instead of $request->is('v1/*')
    if ($request->is('v1/*')) { 
        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
            or $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return app('api.response')->notFoundError(
                    'Sorry, the resource you requested does not exist.'
                );
        }
        
        if ($e instanceof \Symfony\Component\Routing\Exception\MethodNotAllowedException
            or $e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                return app('api.response')->setStatusCode(405)->error(
                    'Sorry, the endpoint does not exist.'
                );
        }
        
        // Add yours ...
    }

    return parent::render($request, $e);
}
```

###[Fighting against CORS Issue in Javascript-based Web Client](#cors)

I highly recommend utilize [barryvdh/laravel-cors](https://github.com/barryvdh/laravel-cors).

---

##[Access API Endpoints from a Client](#client)

Laravel is using method spoofing(a.k.a. method overriding) for `PUT|PATCH` and `DELETE` request, so your client should also request as so. For example if a client want to make a `PUT` request to `//host/v1/things/1`, the client should send a `POST` request to the API endpoint with additional request body of `_method=put`.

Alternative way to achieve method spoofing in Laravel is using `X-HTTP-Method-Override` request header. For example, `X-HTTP-Method-Override: put`.

Either way works, so it comes down to your preference.

Following table illustrates how an api client can access your api endpoint:

Http verb|Endpoint address|Mandatory param (or header)|Controller method|Description
---|---|---|---|---
GET|//host/v1/things| |`index()`|Get a collection of resource
GET|//host/v1/things/{id}| |`show()`|Get the specified resource
POST|//host/v1/things| |`store()`|Create new resource
POST|//host/v1/things/{id}|`_method=put` `(x-http-method-override: put)`|`update()`|Update the specified resource
POST|//host/v1/things/{id}|`_method=delete` `(x-http-method-override: delete)`|`delete()`|Delete the specified resource

---

##LICENSE

[The MIT License](https://raw.githubusercontent.com/appkr/fractal/master/LICENSE)
