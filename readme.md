#Fractal wrapper for Laravel 5#
This is a package, or rather, an **opinionated/laravel-istic use case of the famous [league/fractal](https://github.com/thephpleague/fractal) package in Laravel 5.0/5.1 environment**.

This project was started to fulfill a personal RESTful API service needs. In an initial attempt to evaluate various php API packages for Laravel, I found that the features of those packages providing are well excessive for my requirement.

If your requirement is simple like mine, this is the right package. But if you need more delicate package, head over to [chiraggude/awesome-laravel](https://github.com/chiraggude/awesome-laravel#restful-apis) to find a right one.

Using this package, I didn't want to sacrifice Laravel 5's recommended coding practices which I learned from the field. And I wanted users of this project to be able to easily/freely handle/modify this package with the common knowledge/experience of Laravel 5, without having to require a package specific syntax/usage. And most importantly, I wanted he/she could build his/her API service quickly based on the examples provided.

---

##Index

- [Goal](#goal)
- [Install](#install)
- [Bundled Example](#example)
- [Best Practices](#best-practices)
    - [Route(API Endpoints)](#route)
    - [Controller](#controller)
    - [FormRequest](#form-request)
    - [Work around for TokenMismatchException](#token)
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
This package is provided as a composer package. Define `"appkr/fractal": "0.1.*"` at your project `composer.json`'s require section and `composer update`.

Or require it directly at a console.

```
composer require "appkr/fractal:0.1.*"
```

Add the service provider at the providers array of your `config/app.php`.

```
'providers'=> [
    ...
    'Appkr\Fractal\ApiServiceProvider'
]
```

Finally, issue a publish assets command at a console.

```
php artisan vendor:publish --provider="Appkr\Fractal\ApiServiceProvider"
```

Configuration file is located at `config/fractal.php`.

Done !

>**Note** This package depends upon php5.5 syntax: `Namespace\ClassName::class` instead of string reference of `'Namespace\ClassName'`.

---

<a name="example"></a>
##Bundled Example

The package is bundled with some simple example. Example classes are namespaced under `Appkr\Example`. That includes:

- Database migrations and seeder
- routes definition, Eloquent Model and corresponding Controller
- FormRequest
- Transformer
- Integration Test

If you want to see the the working example right away, head over to `vendor/appkr/fractal/src/ApiServiceProvider.php`, uncomment the lines, republish assets, and migrate/seed tables.

```
// Uncomment 2 lines at vendor/appkr/fractal/src/ApiServiceProvider.php
realpath(__DIR__ . '/../database/migrations/') => database_path('migrations')
include __DIR__.'/./example/routes.php';

// Republish assets at a console
php artisan vendor:publish --provider="Appkr\Fractal\ApiServiceProvider"

// Migrate/seed tables at a console
php artisan migrate
php artisan db:seed --class="Appkr\Fractal\Example\DatabaseSeeder"
```

Boot up the server and head over to `http://localhost:8000/api/v1/resource`.
```
// Boot up you local dev server
php artisan serve

// head over to http://localhost:8000/api/v1/resource at a browser, you sould see:
{
    "data": [
        {
            "id": 1,
            "title": "Voluptates accusamus vero velit sunt ex.",
            "description": null,
            "deprecated": true,
            "created_at": "2015-05-29 07:16:41",
            "manager": {
                "id": 2,
                "name": "nathan96",
                "email": "nick.schiller@example.com",
                "created_at": "2015-05-29 07:16:41"
            }
        },
        {
            ...
        }
    ],
    "meta": {
        "pagination": {
            "total": 100,
            "count": 25,
            "per_page": 25,
            "current_page": 1,
            "total_pages": 4,
            "links": {
                "next": "http://localhost:8000/api/v1/resource/?page=2"
            }
        }
    }
}
```

Or run `phpunit`, if your project is based on Laravel 5.1.*.

```
phpunit vendor/appkr/fractal/src/example/ResourceApiTest.php
```

>**Note** If you finished evaluating the example, don't forget to rollback the migration and re-comment at `vendor/appkr/fractal/src/ApiServiceProvider.php`
>
>```
>// Comment 2 lines at vendor/appkr/fractal/src/ApiServiceProvider.php
>// realpath(__DIR__ . '/../database/migrations/') => database_path('migrations')
>// include __DIR__.'/./example/routes.php';
>
>// Rollback migrations
>php artisan migrate:rollback
>```

---

<a name="best-practices"></a>
##Best Practices

Best/fast way to build your service is, I think, referring the bundled examples at `vendor/appkr/fractal/src/example/`.

<a name="route"></a>
###Route (API Endpoints)
You can define your routes just like laravel-istic way.

```
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
It is recommended to extend `Appkr\Fractal\Controller` instead of `App\Http\Controllers\Controller`. By extending the abstract controller of this package, YourController (in this example `ResourceController`) can access to `Appkr\Fractal\ApiHelper` trait's various helper methods.

```
class ResourceController extends Appkr\Fractal\Controller {}
```

Or, if many logics are already living in your abstract controller, you can set use statement of `Appkr\Fractal\ApiHelper` at yours.

```
// app/Http/Controllers/Controller.php

abstract class Controller extends BaseController {
    ...
    use Appkr\Fractal\ApiHelper;
    ...
}
```

>**Note** Open `Appkr\Fractal\ApiHelper` and check what methods are available there. For example, you can respond a transformed item at the `show()`, or simple json at the `destroy()` method like below:
>
>```
>// app/Http/Controllers/ResourceController.php
>
>public function show($id) {
>    return $this->respondItem(
>        \App\Resource::findOrFail($id),
>        new \App\Transformers\ResourceTransformer
>    );
>}
>
>public function destroy(ResourceRequest $request, $id) {
>    $resource = \App\Resource::findOrFail($id);
>
>    if (! $resource->delete()) {
>        return $this->respondInternalError();
>    }
>
>    return $this->respondSuccess('Deleted');
>}
>```

<a name="form-request"></a>
###FormRequest
It is recommended to extend `Appkr\Fractal\Request` instead of `App\Http\Requests\Request`. By extending the abstract request of this package, validation or authorization errors are properly formatted as you configured at the `config\fractal.php`.

```
class ResourceRequest extends Appkr\Fractal\Request {}
```

Or, you may copy code from `Appkr\Fractal\Request` and paste to your abstract request.

<a name="transformer"></a>
###Transformer
This package follows original Fractal Transformer spec. Refer to the original [documentation](http://fractal.thephpleague.com/transformers/). `vendor/appkr/fractal/src/example/` holds an example transformer.

<a name="csrf"></a>
###Work around for TokenMismatchException
Laravel 5 throws TokenMismatchException when an client sends a post request(create, update, or delete resource) to the API endpoint. Because the clients exists separate domain or environment (e.g. android native application), no way for your server to publish csrf token to the client. It's more desirable to achieve a level of security through [tymondesigns/jwt-auth](https://github.com/tymondesigns/jwt-auth) or equivalent measures.

If your project depends on Laravel 5.1, it's never easier:

```
// app/Http/Middleware/VerifyCsrfToken.php

protected $except = [
    'api/*'
];
```

In Laravel 5.0, I did it like this:

```
// app/Http/Middleware/VerifyCsrfToken.php

public function handle($request, Closure $next) {
    if ($request->is('api/*')) {
        return $this->addCookieToResponse($request, $next($request));
    }

    return parent::handle($request, $next);
}
```

<a name="exception-formatting"></a>
###Formatting Laravel's General Exceptions.
In case of `Illuminate\Database\Eloquent\ModelNotFoundException`, if the request was originated from API clients, I thought 404 with json response was more appropriate though, Laravel just rendered 404 with html response. To properly format this, I did:

```
// app/Exceptions/Handlers.php

use Appkr\Fractal\ApiHelper;

public function render($request, Exception $e) {
    if ($request->is('api/*')) {
        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
            or $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return $this->respondNotFound('The resource you requested does not exist.');
        }
    
        if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
            return $this->setResponseCode(400)->respondWithError('Invalid Credentials');
        }
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

If you are using auth package, add Authorization header accordingly on each API request.

```
// Authorization: Bearer ...
```

Laravel is using method spoofing for `PUT|PATCH` and `DELETE` request, so your client should also request as so. For example if a client want to make a `PUT` request to `//host/api/v1/resource/1`, the client should send a `POST` request to the API endpoint with request body of `_method=put`.

Alternative way to achieve method spoofing in Laravel is using `X-HTTP-Method-Override` request header. The client has to send a POST request with `X-HTTP-Method-Override: PUT` header. 

Which way to go comes down to your preference.

Http verb|Endpoint address|Mandatory param (or header)|Controller method|Description
---|---|---|---|---
GET|//host/api/v1/resource| |`index()`|Get a collection of resource
GET|//host/api/v1/resource/{id}| |`show()`|Get the specified resource
POST|//host/api/v1/resource| |`store()`|Create new resource
POST|//host/api/v1/resource/{id}|`_method=put` `(x-http-method-override: put)`|`update()`|Update the specified resource
POST|//host/api/v1/resource/{id}|`_method=delete` `(x-http-method-override: delete)`|`delete()`|Delete the specified resource

---

##LICENSE

[The MIT License](https://raw.githubusercontent.com/appkr/fractal/master/LICENSE)
