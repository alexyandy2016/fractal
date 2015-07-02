#Fractal wrapper for Laravel 5/Lumen#

[![Latest Stable Version](https://poser.pugx.org/appkr/fractal/v/stable)](https://packagist.org/packages/appkr/fractal) [![Total Downloads](https://poser.pugx.org/appkr/fractal/downloads)](https://packagist.org/packages/appkr/fractal) [![Latest Unstable Version](https://poser.pugx.org/appkr/fractal/v/unstable)](https://packagist.org/packages/appkr/fractal) [![License](https://poser.pugx.org/appkr/fractal/license)](https://packagist.org/packages/appkr/fractal)

This is a package, or rather, an **opinionated/laravel-istic use case of the famous [league/fractal](https://github.com/thephpleague/fractal) package in Laravel 5/Lumen environment**.

This project was started to fulfill a personal RESTful API service needs. In an initial attempt to evaluate various php API packages for Laravel, I found that the features of those packages providing are well excessive for my requirement.

If your requirement is simple like mine, this is the right package. But if you need more delicate package, head over to [chiraggude/awesome-laravel](https://github.com/chiraggude/awesome-laravel#restful-apis) to find a right one.

Using this package, I didn't want user of this package to sacrifice Laravel's recommended coding practices which we all commonly know, without having to require a package specific syntax/usage. And most importantly, I wanted he/she could build his/her API service quickly based on the examples provided.

## Usage
```php
// Respond json formatted 'Resource' model 
// including 'Manager' nesting, pagination, 
// and additional meta of ['version' => 1]
return $this->response()->setMeta(['version' => 1])->withPagination(
    Resource::with('manager')->latest()->paginate(25),
    new ResourceTransformer
);

// Respond simple json error with 422 response code
return $this->response()->unprocessableError($errors);
```

---

##Index

- [Goal](#goal)
- [Install](#install)
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

<a name="goal"></a>
##Goal
- Provides easy access to Fractal instance for Laravel 5/Lumen (ServiceProvider).
- Provides configuration capability for Fractal and response format.
- Provides use case examples, so that users can quickly copy & paste to his/her project.

<a name="install"></a>
##Install
Use composer. Define `"appkr/fractal": "0.4.*"` at your project `composer.json`'s require section and `composer update`.

Or require it directly at a console.

```bash
composer require "appkr/fractal:0.4.*"
```

Add the service provider.

```php
// For Laravel - config/app.php
'providers'=> [
    Appkr\Fractal\ApiServiceProvider::class,
]

// For Lumen - boostrap/app.php
$app->register(Appkr\Fractal\ApiServiceProvider::class);
```

Finally, issue a publish assets command at a console.

```bash
// For Laravel only
$ php artisan vendor:publish --provider="Appkr\Fractal\ApiServiceProvider"
```

Configuration file is located at `config/fractal.php` or `vendor/appkr/fractal/src/config/fractal.php`.

Done !

---

<a name="example"></a>
##Bundled Example

The package is bundled with some simple example. Example classes are namespaced under `Appkr\Fractal\Example`. Those include:

- Database migrations and seeder
- routes definition, Eloquent Model and corresponding Controller
- FormRequest *(Laravel only)*
- Transformer
- Integration Test

If you want to see the the working example right away, head over to `vendor/appkr/fractal/src/ApiServiceProvider.php`, uncomment the lines, and migrate/seed tables.

```php
// Uncomment the line at vendor/appkr/fractal/src/ApiServiceProvider.php
// For Laravel
include __DIR__ . '/./example/routes.php';

// For Lumen
include __DIR__ . '/./example/routes-lumen.php';
>>>>>>> master
```

```bash
// Migrate/seed tables at a console
$ php artisan migrate --path=vendor/appkr/fractal/database/migrations
$ php artisan db:seed --class="Appkr\Fractal\Example\DatabaseSeeder"
```

Boot up the server, 

```bash
// Boot up your local dev server
$ php artisan serve
```

and head on to `http://localhost:8000/api/v1/resource`. You should see below:

```json
// GET http://localhost:8000/api/v1/resource
{
  "data": [
    {
      "id": 100,
      "title": "Eos voluptatem officiis perferendis quas.",
      "description": null,
      "deprecated": true,
      "created_at": 1434608210,
      "manager": {
        "id": 5,
        "name": "mlittel",
        "email": "cora85@example.org",
        "created_at": 1434608210
      }
    },
    {
      "..."
    }
  ],
  "meta": {
    "version": 1,
    "pagination": {
      "total": 100,
      "count": 25,
      "per_page": 25,
      "current_page": 1,
      "total_pages": 4,
      "links": {
        "next": "http:\\/\\/localhost:8000\\/api\\/v1\\/resource\\/?page=2"
      }
    }
  }
}
```

Assuming you've already set up a test environment, you can run `phpunit`, if your project is based on 5.1.* framework. 

```bash
//For Laravel
$ phpunit vendor/appkr/fractal/src/example/ResourceApiTestForLaravel.php

// For Lumen
$ phpunit vendor/appkr/fractal/src/example/ResourceApiTestForLumen.php
```

**`Caution`** Special care should be taken, the test should not be done against the production database.

**`Note`** If you finished evaluating the example, don't forget to rollback the migration and re-comment the unnecessary lines

---

<a name="best-practices"></a>
##Best Practices

Best/fastest way to build your API service is, I think, referring the bundled examples at `vendor/appkr/fractal/src/example/`.

<a name="route"></a>
###Route (API Endpoints)
You can define your routes just like laravel-istic way.

```php
// app/Http/routes.php

Route::group(['prefix' => 'api/v1'], function() {
    Route::resource(
        'something',
        SomethingController::class,
        ['except' => ['create', 'edit']]
    );
});

// For Lumen, checkout the example at vendor/appkr/fractal/src/example/routes-lumen.php
```

<a name="controller"></a>
###Controller
It is recommended for your `SomethingController` or preferably `App\Http\Controllers\Controller` to import `Appkr\Fractal\ApiResponse` trait. By doing so, `SomethingController` can use `$this->response() or $this->respond()` as shown in the example. 

Alternatively you can inject `Appkr\Fractal\Response` to the constructor of `SomethingController`.

One lastly, you can get the `Appkr\Fractal\Response` instance from the Container, like `app('api.response')`.

```php
// Use trait
class SomethingController 
{
    use Appkr\Fractal\ApiResponse;
    
    public function index() {
        // We can use $this->response() or $this->respond() interchangeably
        $this->response()->success('Hello API');
    }
}

// Or inject Appkr\Fractal\Response
class SomethingController 
{
    protected $respond;
    
    public function __construct(\Appkr\Fractal\Response $respond)
    {
        $this->respond = $respond;
    }
    
    public function index() 
    {
        $this->respond->success('Hello API');
    }
}

// Or get the instance out of the Laravel Container
class SomethingController 
{
    public function index() {
        app('api.response')->success('Hello API');
    }
}

<a name="form-request"></a>
###FormRequest
It is recommended for `YourFormRequest` to extend `Appkr\Fractal\Request`. By extending the abstract request of this package, validation or authorization errors are properly formatted just like you configured at the `config/fractal.php`.

```php
class YourFormRequest extends \Appkr\Fractal\Request {}
```

<a name="transformer"></a>
###Transformer
This package follows original Fractal Transformer spec. Refer to the original [documentation](http://fractal.thephpleague.com/transformers/). An example transformer is provided with this package.

<a name="csrf"></a>
###Handle TokenMismatchException
Laravel 5 throws `TokenMismatchException` when an client sends a post request(create, update, or delete a resource) to the API endpoint. Because the client exists in a separate domain or environment (e.g. android native application), no way for your server to publish csrf token to the client. It's more desirable to achieve a level of security through [tymondesigns/jwt-auth](https://github.com/tymondesigns/jwt-auth) or equivalent measures. (Recommended articles: [scotch.io](https://scotch.io/tutorials/the-ins-and-outs-of-token-based-authentication), [angular-tips.com](http://angular-tips.com/blog/2014/05/json-web-tokens-introduction/))

So, let's just skip it. 

If your project is Laravel 5.1.* based, it couldn't be easier:

```php
// app/Http/Middleware/VerifyCsrfToken.php

protected $except = [
    'api/*' // or config('fractal/pattern')
];
```

In Laravel 5.0/Lumen, I did it like this:

```php
// For Laravel 5.0 - app/Http/Middleware/VerifyCsrfToken.php

public function handle($request, \Closure $next) {
    if ($request->is('api/*')) {
        return $next($request);
    }

    return parent::handle($request, $next);
}

// For Lumen, checkout Laravel\Lumen\Http\Middleware\VerifyCsrfToken
```

<a name="exception-formatting"></a>
###Formatting Laravel's General Exceptions.
For example, I thought 404 with json response was more appropriate for `Illuminate\Database\Eloquent\ModelNotFoundException`, when the request was originated from API clients, but the current version of Laravel just rendered 404 html page. To properly format this, I did:

```php
// app/Exceptions/Handlers.php

public function render($request, Exception $e) 
{
    // We can use is_api_request() helper instead of $request->is('api/*')
    if ($request->is('api/*')) { 
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

<a name="cors"></a>
###Fighting against CORS Issue in Javascript-based Web Client

I highly recommend utilize [barryvdh/laravel-cors](https://github.com/barryvdh/laravel-cors).

---

<a name="api"></a>
##Avaliable Response Methods

These are the list of methods that `Appkr\Fractal\Response` provides:

```php
// Generic response. 
// If valid callback parameter is provided, jsonp response is provided.
// All other responses are depending upon this base respond() method.
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
// The format can be configurable at fractal.successFormat
success(string|array $message)

// Respond 201
// If a model is given at the first argument of this method,
// the class tries its best to transform the model to a simple array
created(string|array|\Illuminate\Database\Eloquent\Model $primitive)

// Respond 204
noContent()

// Generic error response
// All other error response depends upon this method
// If an instance of Exception is given as the first argument,
// this class does its best to properly set message and status code
error(string|array|\Exception $message)

// Respond 401
unauthorizedError(string|array $message)

// Respond 403
forbiddenError(string|array $message)

// Respond 404
notFoundError(string|array $message)

// Respond 406
notAcceptableError(string|array $message)

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

<a name="client"></a>
##Access API Endpoints from a Client

Laravel is using method spoofing for `PUT|PATCH` and `DELETE` request, so your client should also request as so. For example if a client want to make a `PUT` request to `//host/api/v1/resource/1`, the client should send a `POST` request to the API endpoint with request body of `_method=put`.

Alternative way to achieve method spoofing in Laravel is using `X-HTTP-Method-Override` request header. The client has to send a POST request with `X-HTTP-Method-Override: PUT` header. 

Either way works, so it comes down to your preference.

Http verb|Endpoint address|Mandatory param (or header)|Controller method|Description
---|---|---|---|---
GET|//host/api/v1/something| |`index()`|Get a collection of resource
GET|//host/api/v1/something/{id}| |`show()`|Get the specified resource
POST|//host/api/v1/something| |`store()`|Create new resource
POST|//host/api/v1/something/{id}|`_method=put` `(x-http-method-override: put)`|`update()`|Update the specified resource
POST|//host/api/v1/something/{id}|`_method=delete` `(x-http-method-override: delete)`|`delete()`|Delete the specified resource

---

##LICENSE

[The MIT License](https://raw.githubusercontent.com/appkr/fractal/master/LICENSE)
