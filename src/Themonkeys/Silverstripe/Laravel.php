<?php namespace Themonkeys\Silverstripe;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use \Config as SilverstripeConfig;

class Laravel {
    public static function configureSilverstripe() {
        global $databaseConfig;

        // bootstrap Laravel
        $app = require_once static::basePath().'/bootstrap/start.php';
        if ($app !== true) {
            $app->boot();
        }

        $config = Config::get('database.connections');
        $connection = Config::get('silverstripe::database.connection');
        if (is_null($connection)) {
            $connection = Config::get('database.default');
        }
        $config = $config[$connection];

        $databaseConfig = array(
            "type" => $config['driver'] == 'mysql' ? '\Themonkeys\Silverstripe\MySQLDatabaseWrapper' : "Don't know how to translate Laravel database driver {$config['driver']} to Silverstripe database type",
            "server" => $config['host'],
            "username" => $config['username'],
            "password" => $config['password'],
            "database" => $config['database'],
            "path" => $config['prefix'],
        );

        SilverstripeConfig::inst()->update('MySQLDatabase', 'connection_charset', $config['charset']);

        $env = App::environment();
        if ($env == 'local') {
            SilverstripeConfig::inst()->update('Director', 'environment_type', 'dev');
        } else if (preg_match('/(?:^stag|^test)/', $env)) {
            SilverstripeConfig::inst()->update('Director', 'environment_type', 'test');
        }

        \SS_Log::add_writer(new SilverstripeLaravelLogWriter(), Config::get('silverstripe::log.level', \SS_Log::WARN), '<=');
    }

    private static function basePath() {
        $path = __DIR__;
        for (;;) {
            $path = dirname($path);
            if ($path == '/') {
                return null;
            }
            if (static::isBasePath($path)) {
                return $path;
            }
        }
    }

    private static function isBasePath($path) {
        // we'll decide it's the base path if it contains app, bootstrap and public subfolders
        return file_exists($path . '/app') && file_exists($path . '/bootstrap') && file_exists($path . '/public');
    }
}