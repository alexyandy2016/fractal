#Fractal wrapper for Laravel 5#

[![Total Downloads](https://poser.pugx.org/appkr/fractal/downloads)](https://packagist.org/packages/appkr/fractal) [![License](https://poser.pugx.org/appkr/fractal/license)](https://packagist.org/packages/appkr/fractal)

This is a package, or rather, an **opinionated/laravel-istic use case of the famous [league/fractal](https://github.com/thephpleague/fractal) package in Laravel 5.0/5.1 environment**.

This project was started to fulfill a personal RESTful API service needs. In an initial attempt to evaluate various php API packages for Laravel, I found that the features of those packages providing are well excessive for my requirement.

If your requirement is simple like mine, this is the right package. But if you need more delicate package, head over to [chiraggude/awesome-laravel](https://github.com/chiraggude/awesome-laravel#restful-apis) to find a right one.

Using this package, I didn't want user of this package to sacrifice Laravel 5's recommended coding practices which we all commonly know, without having to require a package specific syntax/usage. And most importantly, I wanted he/she could build his/her API service quickly based on the examples provided.

## Usage
```php
// Respond json formatted 'Resource' model 
// including 'Manager' nesting, pagination, and additional meta
return $this->response()->setMeta(['foo' => 'bar'])->withPagination(
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
- [Access API Endpoints from a Client](#client)

---

<a name="goal"></a>
##Goal
- Provides easy access to Fractal instance for Laravel 5 (ServiceProvider).
- Provides configuration capability for Fractal and response format.
- Provides use case examples, so that users can quickly copy & paste to his/her project.

<a name="install"></a>
##Install
Use composer. Define `"appkr/fractal": "0.2.*"` at your project `composer.json`'s require section and `composer update`.

Or require it directly at a console.

```bash
composer require "appkr/fractal:0.2.*"
```

Add the service provider at the providers array of your `config/app.php`.

```php
'providers'=> [
    Appkr\Fractal\ApiServiceProvider::class,
]
```

Finally, issue a publish assets command at a console.

```bash
php artisan vendor:publish --provider="Appkr\Fractal\ApiServiceProvider"
```

Configuration file is located at `config/fractal.php`.

Done !

---

<a name="example"></a>
##Bundled Example

The package is bundled with some simple example. Example classes are namespaced under `Appkr\Fractal\Example`. Those include:

- Database migrations and seeder
- routes definition, Eloquent Model and corresponding Controller
- FormRequest
- Transformer
- Integration Test

If you want to see the the working example right away, head over to `vendor/appkr/fractal/src/ApiServiceProvider.php`, uncomment the lines, republish assets, and migrate/seed tables.

```php
// Uncomment 3 lines at vendor/appkr/fractal/src/ApiServiceProvider.php
realpath(__DIR__ . '/../database/migrations/') => database_path('migrations')
realpath(__DIR__ . '/../database/factories/') => database_path('factories')
include __DIR__.'/./example/routes.php';
```

```bash
// Republish assets at a console
php artisan vendor:publish --provider="Appkr\Fractal\ApiServiceProvider"
```

```bash
// Migrate/seed tables at a console
php artisan migrate
php artisan db:seed --class="Appkr\Fractal\Example\DatabaseSeeder"
```

Boot up the server and head over to `http://localhost:8000/api/v1/resource`.

```bash
// Boot up your local dev server
php artisan serve
```

```json
// head over to http://localhost:8000/api/v1/resource at a browser, you sould see:
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
    "foo": "bar",
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

Or run `phpunit`, if your project is based on Laravel 5.1.*.

```bash
phpunit vendor/appkr/fractal/src/example/ResourceApiTest.php
```

>**Note** If you finished evaluating the example, don't forget to rollback the migration and re-comment at `vendor/appkr/fractal/src/ApiServiceProvider.php`
>
>```php
>// Comment 3 lines at vendor/appkr/fractal/src/ApiServiceProvider.php
>// realpath(__DIR__ . '/../database/migrations/') => database_path('migrations')
>// realpath(__DIR__ . '/../database/factories/') => database_path('factories')
>// include __DIR__.'/./example/routes.php';
>```
>
>```bash
>// Rollback migrations
>php artisan migrate:rollback
>```

---

<a name="best-practices"></a>
##Best Practices

Best/fastest way to build your service is, I think, referring the bundled examples at `vendor/appkr/fractal/src/example/`.

<a name="route"></a>
###Route (API Endpoints)
You can define your routes just like laravel-istic way.

```php
// app/Http/routes.php

Route::group(['prefix' => 'api/v1'], function() {
    Route::resource(
        'resource',
        ResourceController::class,
        ['except' => ['create', 'edit']]
    );
});
```

<a name="controller"></a>
###Controller
It is recommended for `YourController` or preferably `App\Http\Controllers\Controller` to import `Appkr\Fractal\ApiHelper`. By doing so, `YourController` (in this example `ResourceController`) can get an instance of `Appkr\Fractal\Response` using `$this->response()`, and can access to various json response helper methods provided by `Appkr\Fractal|Response`.

```php
class ResourceController 
{
    use \Appkr\Fractal\ApiHelper;
}
```

>**Note** Alternatively you can get an `Appkr\Fractal\Response` by using Laravel native `app('api.response')` helper. After getting the instance, for example, you can respond a transformed item, or a simple json(p):

<a name="form-request"></a>
###FormRequest
It is recommended for `YourFormRequest` to extend `Appkr\Fractal\Request`. By extending the abstract request of this package, validation or authorization errors are properly formatted just like you configured at the `config/fractal.php`.

```php
class ResourceRequest extends \Appkr\Fractal\Request {}
```

<a name="transformer"></a>
###Transformer
This package follows original Fractal Transformer spec. Refer to the original [documentation](http://fractal.thephpleague.com/transformers/). An example transformer is enclosed with this package.

<a name="csrf"></a>
###Handle TokenMismatchException
Laravel 5 throws `TokenMismatchException` when an client sends a post request(create, update, or delete resource) to the API endpoint. Because the clients exists separate domain or environment (e.g. android native application), no way for your server to publish csrf token to the client. It's more desirable to achieve a level of security through [tymondesigns/jwt-auth](https://github.com/tymondesigns/jwt-auth) or equivalent measures. ([Recommended article on API security](https://scotch.io/tutorials/the-ins-and-outs-of-token-based-authentication))

So, let's just skip it. 

If your project is Laravel 5.1.* based, it couldn't be easier:

```php
// app/Http/Middleware/VerifyCsrfToken.php

protected $except = [
    'api/*'
];
```

In Laravel 5.0.*, I did it like this:

```php
// app/Http/Middleware/VerifyCsrfToken.php

public function handle($request, Closure $next) {
    if ($request->is('api/*')) {
        return $next($request);
    }

    return parent::handle($request, $next);
}
```

<a name="exception-formatting"></a>
###Formatting Laravel's General Exceptions.
For example, I thought 404 with json response was more appropriate for `Illuminate\Database\Eloquent\ModelNotFoundException`, when the request was originated from API clients, but the current version of Laravel just rendered 404 html page. To properly format this, I did:

```php
// app/Exceptions/Handlers.php

public function render($request, Exception $e) 
{
    if ($request->is('api/*')) {
        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
            or $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return app('api.response')->notFoundError('Sorry, the resource you requested does not exist.');
        }
        
        if ($e instanceof MethodNotAllowedException
            or $e instanceof MethodNotAllowedHttpException
        ) {
            return $this->response()->setStatusCode(405)->error('Sorry, the endpoint does not exist.');
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

<a name="client"></a>
##Access API Endpoints from a Client

Laravel is using method spoofing for `PUT|PATCH` and `DELETE` request, so your client should also request as so. For example if a client want to make a `PUT` request to `//host/api/v1/resource/1`, the client should send a `POST` request to the API endpoint with request body of `_method=put`.

Alternative way to achieve method spoofing in Laravel is using `X-HTTP-Method-Override` request header. The client has to send a POST request with `X-HTTP-Method-Override: PUT` header. 

Either way works, so it comes down to your preference.

Http verb|Endpoint address|Mandatory param (or header)|Controller method|Description
---|---|---|---|---
GET|//host/api/v1/resource| |`index()`|Get a collection of resource
GET|//host/api/v1/resource/{id}| |`show()`|Get the specified resource
POST|//host/api/v1/resource| |`store()`|Create new resource
POST|//host/api/v1/resource/{id}|`_method=put` `(x-http-method-override: put)`|`update()`|Update the specified resource
POST|//host/api/v1/resource/{id}|`_method=delete` `(x-http-method-override: delete)`|`delete()`|Delete the specified resource

>**Note** `Appkr\Fractal\Request` has helpers like `isUpdateReqeust()` and `isDeleteRequest()`.

---

##LICENSE

[The MIT License](https://raw.githubusercontent.com/appkr/fractal/master/LICENSE)
