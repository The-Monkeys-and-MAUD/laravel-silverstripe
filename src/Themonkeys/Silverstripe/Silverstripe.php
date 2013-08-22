<?php

namespace Themonkeys\Silverstripe;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Input;

class Silverstripe {
    private static $started = false;
    private static $models = array();

    /**
    |--------------------------------------------------------------------------
    | Start up Silverstripe
    |--------------------------------------------------------------------------
    |
    | Include Silverstripe's core code and connect it to its database
    |
     */
    public static function start() {
        if (!static::$started) {
            static::$started = true;
            require_once('SilverstripeHelpers.php');
            if (true !== require_once(public_path().'/silverstripe/framework/core/Core.php')) {
                require_once(public_path().'/silverstripe/framework/model/DB.php');
                global $databaseConfig;
                if (!\DB::isActive()) {
                    \DB::connect($databaseConfig);
                }
            }
            \Versioned::choose_site_stage();
        }
    }

    public static function getRequestedStage() {
        return Input::get('stage', \Versioned::get_live_stage());
    }

    public static function model($url = null) {
        static::start();

        $stage = static::getRequestedStage();
        $key = $url . '|' . $stage;

        if (isset(static::$models[$key])) {
            return static::$models[$key];
        }
        $segments = !is_null($url) ? static::segments($url) : Request::segments();
        $segment = array_shift($segments);
        if ($segment) {
            $parentID = 0;
            do {
                $model = \SiteTree::get()->filter(array(
                    'URLSegment' => $segment,
                    'ParentID' => $parentID))->First();

                if ($model) {
                    $parentID = $model->ID;
                } else {
                    break;
                }
            } while ($segment = array_shift($segments));
        } else {
            // special case - home page
            $model = \SiteTree::get()->filter(array(
                'URLSegment' => 'home',
                'ParentID' => 0,
            ))->First();
        }
        return static::$models[$url] = $model;
    }

    /**
     * Get all of the segments for the request path.
     *
     * @return array
     */
    private static function segments($path)
    {
        return $path == '/' ? array() : explode('/', $path);
    }

}