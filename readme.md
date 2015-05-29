#Fractal wrapper for Laravel 5#
This is a package, or rather, an **opinionated/laravelic use case of the famous [thephpleague/fractal](https://github.com/thephpleague/fractal) package in Laravel 5 environment**. 

This project was started to fulfill a personal RESTful API service needs. In an initial attempt to evaluate various php API packages for Laravel, I found that the features of those packages providing are well excessive for my requirement. 
 
Using this package, I didn't want to sacrifice Laravel's best coding practices which I learned from the official documentation. And I wanted users of this project to be able to easily/freely modify this package, and build his/her API service quickly based on the examples provided.

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
- [Access API Endpoints from a Client](#client)

---

<a name="goal"></a>
##Goal
- Provides easy access to Fractal instance for Laravel 5 (ServiceProvider).
- Provides configuration capability for Fractal and response format.
- Provides use case example, so that users can quickly copy & paste to his/her project.

<a name="install"></a>
##Install
This package is provided as a composer package. Define `"appkr/fractal": "dev-master"` at your project `composer.json`'s require section. Or require it at a console.  

```
composer require appkr/fractal
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
php artisan vendor:publish
```

Configuration file is located at `config/fractal.php`.

Done !

>**Note** This package depends upon php5.5 syntax: `Namespace\ClassName::class` instead of string reference of `'Namespace\ClassName'`. 

---

<a name="example"></a>
##Bundled Example

The package is bundled with some simple example. Example classes are namespaced under `Appkr\Example`. That includes:

- Database migrations and seeder
- routes definition
- Eloquent Model and corresponding Controller
- FormRequest
- Transformer

If you want to see the the working example right away, head over to `vendor/appkr/fractal/src/ApiServiceProfider.php`, uncomment the lines, republish assets, and migrate/seed tables.

```
// Uncomment 2 lines at vendor/appkr/fractal/src/ApiServiceProfider.php
realpath(__DIR__ . '/../database/migrations/') => database_path('migrations')
include __DIR__.'/./example/routes.php';

// Republish assets at a console
php artisan vendor:publish

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

>**Note** If you finished evaluating the example, don't forget to rollback the migration and comment at `vendor/appkr/fractal/src/ApiServiceProfider.php`
>
>```
>// Comment 2 lines at vendor/appkr/fractal/src/ApiServiceProfider.php
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
You can define your routes just laravelic way.
 
```
// app/Http/routes.php

Route::group(['prefix' => 'api/v1'], function() {
    Route::resource(
        'resource',
        YourController::class,
        ['except' => ['create', 'edit']]
    );
});
```

<a name="controller"></a>
###Controller
It is recommended to extend `Appkr\Fractal\Controller` instead of `App\Http\Controllers\Controller`. By extending the abstract controller of this package YourController can access to `Appkr\Fractal\ApiHelper` trait's various helper methods.

```
class YourController extends Appkr\Fractal\Controller {}
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

>**Note** Open `Appkr\Fractal\ApiHelper` and check what methods are available there. For example, you can respond a transformed item at the `show()` method like below:
>
>```
>// app/Http/Controllers/YourController.php
>
>public function show($id) {
>    return $this->respondItem(
>        YourEloquentModel::findOrFail($id),
>        new YourEloquentModelTransformer
>    );
>}
>```

<a name="form-request"></a>
###FormRequest
It is recommended to extend `Appkr\Fractal\Request` instead of `App\Http\Requests\Request`. By extending the abstract request of this package, validation or authorization errors are properly formatted as you configured at the `config\fractal.php`.

```
class YourFormRequest extends Appkr\Fractal\Request {}
```

Or, you may copy code from `Appkr\Fractal\Request` and paste to your abstract request.

<a name="transformer"></a>
###Transformer
This package follows original Fractal Transformer spec. Refer to the original [documentation](http://fractal.thephpleague.com/transformers/).

<a name="csrf"></a>
###Work around for TokenMismatchException
Laravel 5 throws TokenMismatchException when an client sends a post request(create, update, or delete resource) to the API endpoint. Because the clients exists separate domain or environment (e.g. android native application), no way for your server to publish csrf token to the client. It's more desirable to achieve a level of security through jwt token or equivalent measure.
 
So, in my small project, I did it like this:

```
// app/Http/Middleware/VerifyCsrfToken.php

public function handle($request, Closure $next) {
    if ($request->ajax() || $request->wantsJson()) {
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
    if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
        or $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
        return $this->respondNotFound();
    }

    return parent::render($request, $e);
}
```

---

<a name="client"></a>
##Access API Endpoints from a Client

Every API request from a client should contain the following header:

```
Accept: application/json
```

Laravel is using method spoofing for `PUT` and `DELETE` request, your client also does request as so.

Http verb|Endpoint address|Mandatory params|Controller method|Description
---|---|---|---
GET|//host/api/v1/photo| |`index()`|Get a collection of photo
GET|//host/api/v1/photo/{id}| |`show()`|Get the specified photo 
POST|//host/api/v1/photo| |`store()`|Create new photo
POST|//host/api/v1/photo/{id}|`_method=put`|`update()`|Update the specified photo
POST|//host/api/v1/photo/{id}|`_method=delete`|`delete()`|Delete the specified photo

---

[The MIT License](https://raw.githubusercontent.com/appkr/fractal/master/LICENSE) 
