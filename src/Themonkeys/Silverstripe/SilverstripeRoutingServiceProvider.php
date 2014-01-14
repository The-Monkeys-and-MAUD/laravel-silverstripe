<?php namespace Themonkeys\Silverstripe;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\RoutingServiceProvider;

class SilverstripeRoutingServiceProvider extends RoutingServiceProvider {

    /**
     * Register the router instance.
     *
     * @return void
     */
    protected function registerRouter()
    {
        $this->app['router'] = $this->app->share(function($app)
        {
            $router = new Router($app['events'], $app);

            // If the current application environment is "testing", we will disable the
            // routing filters, since they can be tested independently of the routes
            // and just get in the way of our typical controller testing concerns.
            if ($app['env'] == 'testing')
            {
                $router->disableFilters();
            }

            return $router;
        });
    }

    public function boot() {
        $this->app['silverstripe.commands.build'] = $this->app->share(function($app) {
            return new BuildCommand();
        });
        $this->app['silverstripe.commands.password'] = $this->app->share(function($app) {
            return new PasswordCommand();
        });

        Event::listen('artisan.start', function($artisan) {
            Artisan::resolve('silverstripe.commands.build');
            Artisan::resolve('silverstripe.commands.password');
        });
    }
}