<?php

namespace Themonkeys\Silverstripe;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Support\Facades\App;

class SilverstripeCommand extends Command {

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

    protected function fireSilverstripeCommand($url)
    {
        $this->bootSilverstripe($url);
        $_SERVER['REQUEST_URI'] = BASE_URL . '/' . $url;

        // Direct away - this is the "main" function, that hands control to the appropriate controller
        \Director::direct($url, \DataModel::inst());
    }

    protected function bootSilverstripe($url = null)
    {
        $flush = $this->option('flush');

        // to allow the Silverstripe command line to know what the server URL should be ...
        // sadly, setting a global $_FILE_TO_URL_MAPPING variable is not enough because Silverstripe's Core.php (or
        // Constants.php in newer versions) doesn't declare it as global - it assumes it's declared in the
        // _ss_environment.php file which is _included_ not _required_. Boo.
//        global $_FILE_TO_URL_MAPPING;
//        $_FILE_TO_URL_MAPPING[base_path()] = Config::get('app.url');
        $base = base_path();
        $envPath = $base.'/_ss_environment.php';
        if (!file_exists($envPath)) {
            $appUrl = Config::get('app.url');
            file_put_contents($envPath, <<<EOT
<?php
global \$_FILE_TO_URL_MAPPING;
\$_FILE_TO_URL_MAPPING['$base'] = '$appUrl';
EOT
            );
            App::shutdown(function($app) use ($envPath) {
                unlink($envPath);
            });
        }

        // taken from silverstripe's framework/cli-script.php
        if ($flush) {
            $_REQUEST['flush'] = $flush === true ? 1 : $flush;
            $_GET['flush'] = $flush === true ? 1 : $flush;
        }

        if ($url) {
            $_REQUEST['url'] = $url;
            $_GET['url'] = $url;
        }

        Silverstripe::start();
    }

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('flush', null, InputOption::VALUE_NONE, 'Tell Silverstripe to flush its caches.', null),
		);
	}

}