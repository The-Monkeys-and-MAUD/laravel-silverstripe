![The Monkeys](http://www.themonkeys.com.au/img/monkey_logo.png)

Silverstripe Adapter for Laravel
================================

We wanted to use the fantastic Silverstripe CMS but still keep the brilliant application development framework provided
by Laravel, so we found a way to have both.

This package provides:

- a thin layer to access Silverstripe model objects from within your Laravel code
- a new kind of route that enables page URLs defined in the Silverstripe CMS to be handled by Laravel routes
- automatic configuration of Silverstripe's database settings based on your Laravel configuration
- automatic coupling of Silverstripe's log system through to Laravel's log system

Installation
------------

### Install the package

To get the latest version of the package simply require it in your composer.json file by running:

```bash
composer require themonkeys/laravel-silverstripe:dev-master --no-update
composer update themonkeys/laravel-silverstripe
```

Once installed, you need to register the service provider with the application. Open up `app/config/app.php` and find
the `providers` key.

```php
'providers' => array(
    'Themonkeys\Silverstripe\SilverstripeRoutingServiceProvider',
)
```

The package provides a facade through which you may access some common CMS functionality. To make it easier to use, you
can add it to the aliases in your `app/config/app.php` file too:

```php
'aliases' => array(
    'CMS' => 'Themonkeys\Silverstripe\Silverstripe',
)
```

Sadly, there are some classnames in Silverstripe that conflict with the aliases defined in Laravel's default `app.php`
config (thankfully Laravel uses namespaces so we're not entirely screwed). The best solution we've come up with so far
requires you to do some work... rename the following aliases in your `app/config/app.php` file, and stick to using the
new aliases where necessary in the rest of your work:

```php
'aliases' => array(
    'L_Config'       => 'Illuminate\Support\Facades\Config',
    'L_Controller'   => 'Illuminate\Routing\Controllers\Controller',
    'L_Cookie'       => 'Illuminate\Support\Facades\Cookie',
    'L_File'         => 'Illuminate\Support\Facades\File',
    'L_Form'         => 'Illuminate\Support\Facades\Form',
    'L_Session'      => 'Illuminate\Support\Facades\Session',
    'L_Validator'    => 'Illuminate\Support\Facades\Validator',
)
```

You don't have to use `L_`, you can use whatever you like.

Everywhere except blade templates, we prefer to use `use` statements and then we can continue to use the normal Laravel
names. For example, say you have a controller that needs to use Laravel's `Validator`. Code it like this:

```php
<?php

use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class YourController extends BaseController {
    ...

    public static function postForm() {
        Input::flash();
        $validator = Validator::make(Input::all(), static::$rules);

        ...
    }
}
```

> **Important**: The default `app/controllers/BaseController.php` that ships with Laravel relies on the `Controller`
alias as the name of its parent class, so you need to either rename `Controller` to `L_Controller` or add a `use`
statement:

```php
<?php

use Illuminate\Routing\Controllers\Controller;

class BaseController extends Controller {
```

### Install Silverstripe

Visit http://www.silverstripe.org/ and decide which version of Silverstripe you'd like to use. We've tested the Laravel
integration with versions 3.0.5, 3.1.0-beta3, and the 3.1 development version (at the time of writing).

1. Create a folder inside your Laravel project called `public/silverstripe/`.
2. Install Silverstripe into that folder. For example, to install the 3.1 development version, execute the following
   from the base directory of your project:

    ```bash
    composer create-project silverstripe/installer ./public/silverstripe/ 3.1.x-dev
    ```

   The command will advise you to create an `_ss_environment.php` file; don't do that. The script then finishes with
   an error but don't worry, that's only because Silverstripe's database connection details haven't yet been set up.

3. Add the following to your `.htaccess` file **before** the Laravel rewrite rule:

    ```ApacheConf
    # ------------------------------------------------------------------------------
    # | Silverstripe CMS                                                           |
    # ------------------------------------------------------------------------------
    <IfModule mod_rewrite.c>
        RewriteRule ^admin/?$ /silverstripe/admin/ [R,L]
        RewriteRule ^assets/(.*)$ /silverstripe/assets/$1 [L]
    </IfModule>
    ```

4. Add a silverstripe `RewriteCond` line as the first line of the Laravel rewrite rule in your `.htaccess` file:

    ```ApacheConf
    # ------------------------------------------------------------------------------
    # | Laravel framework                                                          |
    # ------------------------------------------------------------------------------
    <IfModule mod_rewrite.c>
        Options -MultiViews
        RewriteEngine On

        RewriteCond %{REQUEST_URI} !^/silverstripe
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^ index.php [L]
    </IfModule>
    ```
5. We'll be delivering CMS content pages via Laravel rather than via Silverstripe's built-in MVC framework; so it's a
   good idea to disable the default `/silverstripe/` content URLs. You can do this by adding the following 404 rules
   to your `public/silverstripe/.htaccess` file after the existing 403 rules that ship with Silverstripe:

    ```ApacheConf
    <IfModule mod_alias.c>
        RedirectMatch 403 /silverstripe-cache(/|$)
        RedirectMatch 403 /vendor(/|$)
        RedirectMatch 403 /composer\.(json|lock)

        # Only allow the CMS admin and dev-related silverstripe URLs.
        RedirectMatch 404 /silverstripe/?$
        RedirectMatch 404 /silverstripe/(?!admin|assets|cms|framework|Security|themes|dev|gridfieldextensions)
    </IfModule>
    ```

### Delete Silverstripe's installation files

Laravel takes care of Silverstripe setup so you can delete the install files straight away:

```bash
rm public/silverstripe/install*
```

### Configure Silverstripe

As usual with Silverstripe, your custom code goes in the `public/silverstripe/mysite` folder. If you prefer to rename
`mysite` to something else, now is a good time to do it.

```bash
mv public/silverstripe/mysite public/silverstripe/awesomesauce
```

These instructions will continue to use the name `mysite`.

Edit the `public/silverstripe/mysite/_config.php` file and replace the following lines:

```php
global $database;
$database = '';

require_once('conf/ConfigureFromEnv.php');

MySQLDatabase::set_connection_charset('utf8');
```

with

```php
require_once __DIR__.'/../../../bootstrap/autoload.php';
Themonkeys\Silverstripe\Laravel::configureSilverstripe();
```

If you used Silverstripe 3.1, the `MySQLDatabase::set_connection_charset('utf8');` line won't have been there. That's
ok.

### Configure database

If you haven't already done so, create a database and user to use for development and configure it in your
`app/config/database.php` file as you would for any Laravel project:

```php
return array(
	'default' => 'mysql',
	'connections' => array(
		'mysql' => array(
			'driver'    => 'mysql',
			'host'      => 'localhost',
			'database'  => 'mysite',
			'username'  => 'mysite',
			'password'  => 'mysite',
			'charset'   => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix'    => '',
		),
	),
);
```

> The user you configure should have CREATE, ALTER, INDEX, DROP permissions so that Silverstripe can control the
  database structure.

### Optional: create a Silverstripe cache folder

We recommend creating a folder within your project for Silverstripe to use as its cache, because if you use any kind of
replication of your servers (e.g. for load balancing) then you'll definitely want the Silverstripe cache to be
replicated too. Silverstripe will use the system temp directory (e.g. `/tmp/`) if you don't intervene.

```bash
mkdir public/silverstripe/silverstripe-cache
echo *$'\n''!.gitignore' > public/silverstripe/silverstripe-cache/.gitignore
```

The name `silverstripe-cache` is special and cannot be changed.

### Bootstrap the Silverstripe database

With this package installed, you can build your database via Laravel's `artisan` tool:

```bash
php artisan silverstripe:build --flush
```

You could use Silverstripe's web-based method instead if you prefer, by visiting
http://mysite.dev/silverstripe/dev/build?flush=1.

At the end of this you should see the message _Database build completed!_.

Now you will need to set a password for the admin user so you can log in to the CMS:

```bash
php artisan silverstripe:password
```

The command above will prompt you to choose a username (Silverstripe expects you to use an email address, but it works
fine if it's just a plain old username like `admin`) and to enter and confirm your password.

### Adjust the preview URL in your Page base class

To make it possible to preview your pages in the CMS via Laravel routing, add the following method to your `Page` class
in `public/silverstripe/mysite/Page.php`:

```php
class Page extends SiteTree {

	private static $db = array(
	);

	private static $has_one = array(
	);

    public function PreviewLink($action = null) {
        $link = $this->RelativeLink($action);
        if (!starts_with($link, '/')) {
            $link = '/' . $link;
        }
        return $link;
    }
}
```

If you configure routing differently than normal, this is the method you'll need to update to ensure CMS preview still
works correctly.

### Disable Laravel's auto-redirect

Silverstripe URLs mostly end with a `/` whereas Laravel 4 prefers URLs not to end with a `/`, which causes redirect
loops. To fix this, comment out (or delete) the following line in your `bootstrap/start.php` file:

```php
$app->redirectIfTrailingSlash();
```

### Log in to Silverstripe

Visit http://mysite.dev/admin/ and login with the username and password you just set up.


Usage
-----

### Routing

This package adds a new kind of Laravel routes based on the Silverstripe class name of pages. For example:

```php
// an ordinary Laravel route
Route::get('/', 'HomeController@showWelcome');

// a Silverstripe route
// matches any URL specified in the CMS with a page type (i.e. ClassName) of Page
Route::get_silverstripe('Page', 'PageController@showPage');

// an ordinary Laravel POST route
Route::post('/form', 'FormController@saveForm');

// a Silverstripe POST route
// matches any URL specified in the CMS with a page type (i.e. ClassName) of PageWithForm and method GET
Route::post_silverstripe('PageWithForm', 'PageController@saveForm');
```

The prefixes such as `get_`, `post_` can be any method type supported by Laravel.

### Content

If you've added the above example routes to your `routes.php` file, then you'll already be able to try
http://mysite.dev/about-us/ and http://mysite.dev/contact-us/ because the default Silverstripe database comes with those
pages built-in. If you hit them then you'll get the error `Class PageController does not exist`, so create one in
`app/controllers/PageController.php`:

```php
<?php
use Illuminate\Support\Facades\View;

class PageController extends BaseController {

    public static function showPage() {
        return View::make('page', array(
            'model' => CMS::model(),
        ));
    }
}
```

And create the corresponding `app/views/page.blade.php`:

```php
<!DOCTYPE html>
<html lang="utf-8">
<head>
    <title>{{ $model->Title }}</title>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    {{ $model->MetaTags(false) }}
</head>
<body class="{{ $model->ClassName }}">

    <div class="main" role="main">
        <div class="inner typography line">
            <div class="content-container unit size3of4 lastUnit">
                <article>
                    <h1>{{ $model->Title }}</h1>
                    <div class="content">{{ ss($model->Content) }}</div>
                </article>
            </div>
        </div>
    </div>

</body>
</html>
```

Now visit http://mysite.dev/about-us/ and http://mysite.dev/contact-us/ and you'll find that they load content from
the CMS.

To avoid adding the `'model'` view data in every controller, we prefer to use a view composer:

```php
App::before(function($request) {
    $page = CMS::model();
    if ($page && $page->Exists()) {
        View::share('model', $page);
    }
});
```

Then your controller method would be simply:

```php
<?php
public static function showPage() {
    return View::make('page');
}
```

The Laravel docs don't indicate where would be a sensible place to put your view composers. We've decided to create a
new file, `app/viewcomposers.php` (alongside `filters.php`) for them. To make that work it's just a matter of adding
the following code to the bottom of your `app/start/global.php` file:

```php
/*
|--------------------------------------------------------------------------
| Require The View Composers File
|--------------------------------------------------------------------------
|
| Next we will load the view composers file for the application. This gives
| us a nice separate location to store our view composers and shared view
| data definitions instead of putting them all in the main routes file.
|
*/

require app_path().'/viewcomposers.php';
```

### The ss() helper function

You may have noticed in the example `app/views/page.blade.php` file above, we used an `ss()` function to process the
content from the model.

This is required because, to make the CMS more robust, Laravel stores certain things (such as intra-site links) in an
intermediate form instead of as finished HTML. So because we're bypassing Silverstripe's built-in MVC framework we need
to manually trigger this rendering. The `ss()` function is provided by this package to make this as painless as
possible.

If you want to do any further processing of CMS-authored content, the `ss()` function provides a mechanism to allow you
to do this. Simply write an implementation of the `\Themonkeys\Silverstripe\ContentProcessor` class, and bind it into
Laravel's IoC container (for example in `app/start/global.php`):

```php
App::bind('\Themonkeys\Silverstripe\ContentProcessor', 'MyContentProcessor');
```

### Filtering content

The `Silverstripe` (alias `CMS`) facade included in this package is good for loading the Silverstripe model for the a
given URL, but that's all it can do. For more complex data queries you can simply use the [Silverstripe datamodel API]
(http://doc.silverstripe.org/framework/en/topics/datamodel) directly.

As usual in Silverstripe, the Stage (draft) or Live version of the record will be loaded automatically depending on the
`?stage=` querystring parameter.


Configuration
-------------

To configure the package, you can use the following command to copy the configuration file to
`app/config/packages/themonkeys/silverstripe`.

```sh
php artisan config:publish themonkeys/silverstripe
```

Or you can just create new files in that folder and only override the settings you need.

The settings themselves are documented inside the config files.


Contribute
----------

In lieu of a formal styleguide, take care to maintain the existing coding style.

License
-------

MIT License
(c) [The Monkeys](http://www.themonkeys.com.au/)
