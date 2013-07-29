<?php

namespace Themonkeys\Silverstripe;
use Illuminate\Routing\Router as L4_Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Illuminate\Routing\Route;

class Router extends L4_Router {
    protected $silverstripeRoutes = array();

    public function get_silverstripe($pageType, $action)
    {
        return $this->createSilverStripeRoute('get', $pageType, $action);
    }

    public function post_silverstripe($pageType, $action)
    {
        return $this->createSilverStripeRoute('post', $pageType, $action);
    }

    /**
     * Create a new route instance.
     *
     * @param  string  $method
     * @param  string  $pattern
     * @param  mixed   $action
     * @return \Illuminate\Routing\Route
     */
    protected function createSilverStripeRoute($method, $pageType, $action)
    {
        $pattern = 'silverstripe::' . $pageType;
        // We will force the action parameters to be an array just for convenience.
        // This will let us examine it for other attributes like middlewares or
        // a specific HTTP schemes the route only responds to, such as HTTPS.
        if ( ! is_array($action))
        {
            $action = $this->parseAction($action);
        }

        $groupCount = count($this->groupStack);

        // If there are attributes being grouped across routes we will merge those
        // attributes into the action array so that they will get shared across
        // the routes. The route can override the attribute by specifying it.
        if ($groupCount > 0)
        {
            $index = $groupCount - 1;

            $action = $this->mergeGroup($action, $index);
        }

        // We will create the routes, setting the Closure callbacks on the instance
        // so we can easily access it later. If there are other parameters on a
        // routes we'll also set those requirements as well such as defaults.
        $route = with(new Route($pattern))->setOptions(array(

            '_call' => $this->getCallback($action),

        ))->setRouter($this)->addRequirements($this->patterns);

        $route->setRequirement('_method', $method);

        // Once we have created the route, we will add them to our route collection
        // which contains all the other routes and is used to match on incoming
        // URL and their appropriate route destination and on URL generation.
        $this->setAttributes($route, $action, array());

        $name = $this->getName($method, $pattern, $action);
        $this->routes->add($name, $route);

        $map = isset($this->silverstripeRoutes[$pageType]) ? $this->silverstripeRoutes[$pageType] : array();
        $map[strtoupper($method)] = $route;
        $this->silverstripeRoutes[$pageType] = $map;

        return $route;
    }

    /**
     * Create a new URL matcher instance.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return \Symfony\Component\Routing\Matcher\UrlMatcher
     */
    protected function getUrlMatcher(Request $request)
    {
        $context = new RequestContext;

        $context->fromRequest($request);

        return new UrlMatcher($this->routes, $context);
    }


    /**
     * Match the given request to a route object.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return \Illuminate\Routing\Route
     */
    protected function findRoute(Request $request)
    {
        // We will catch any exceptions thrown during routing and convert it to a
        // HTTP Kernel equivalent exception, since that is a more generic type
        // that's used by the Illuminate foundation framework for responses.
        try
        {
            $path = $request->getPathInfo();

            $parameters = $this->getUrlMatcher($request)->match($path);
        }

            // The Symfony routing component's exceptions implement this interface we
            // can type-hint it to make sure we're only providing special handling
            // for those exceptions, and not other random exceptions that occur.
        catch (ExceptionInterface $e)
        {
            $this->handleRoutingException($e);
        }

        if ($parameters) {
            $route = $this->routes->get($parameters['_route']);

            // If we found a route, we will grab the actual route objects out of this
            // route collection and set the matching parameters on the instance so
            // we will easily access them later if the route action is executed.
            $route->setParameters($parameters);

            return $route;
        } else {
            // no matching Laravel route found. Try looking for a SilverStripe route instead
            return $this->findSilverStripeRoute($request);
        }
    }

    protected function findSilverStripeRoute(Request $request)
    {
        Silverstripe::start();

        // check whether there are any matching records in the database
        $model = Silverstripe::model();

        if ($model && isset($this->silverstripeRoutes[$model->getClassName()])) {
            $map = $this->silverstripeRoutes[$model->getClassName()];
            $method = strtoupper($request->getMethod());
            if ($map && isset($map[$method])) {
                return $map[$method];
            }
        }

        $this->handleRoutingException(new ResourceNotFoundException());
    }
}