<?php

namespace Themonkeys\Silverstripe;
use Illuminate\Routing\Router as L4_Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Router extends L4_Router {
    protected $silverstripeRoutes = array();

    public function get_silverstripe($pageType, $action)
    {
        return $this->addSilverStripeRoute(array('GET', 'HEAD'), $pageType, $action);
    }
    public function post_silverstripe($pageType, $action)
    {
        return $this->addSilverStripeRoute('POST', $pageType, $action);
    }

    /**
     * Add a route to the underlying route collection.
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     */
    protected function addSilverStripeRoute($methods, $pageType, $action)
    {
        $uri = 'silverstripe::' . $pageType;
        $route = $this->routes->add($this->createRoute($methods, $uri, $action));

        $map = isset($this->silverstripeRoutes[$pageType]) ? $this->silverstripeRoutes[$pageType] : array();
        foreach ($methods as $method) {
            $map[strtoupper($method)] = $route;
        }
        $this->silverstripeRoutes[$pageType] = $map;
    }


    /**
     * Find the route matching a given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Routing\Route
     */
    protected function findRoute($request)
    {
        $exception = null;
        $route = null;
        try {
            $route = $this->routes->match($request);

        } catch (MethodNotAllowedHttpException $e) {
            $exception = $e;

        } catch (NotFoundHttpException $e) {
            $exception = $e;
        }

        if (!$route) {
            // no matching Laravel route found. Try looking for a SilverStripe route instead
            $route = $this->findSilverStripeRoute($request);
        }

        if ($route) {
            $this->current = $route;
            return $this->substituteBindings($route);

        } else {
            throw $exception;
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
                return $map[$method]->bind($request);
            }
        }

        return null;
    }
}