<?php

namespace Themonkeys\Silverstripe;

class BuildCommand extends SilverstripeCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'silverstripe:build';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Build/rebuild the Silverstripe environment. Call this whenever you have updated your Silverstripe sources.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
        $this->fireSilverstripeCommand('dev/build');

        $defaultMember = \Security::findAnAdministrator();
        if (!$defaultMember->Password) {
            $this->info("Looks like the Silverstripe user '{$defaultMember->Email}' doesn't have a password set. You can set one with the command:\n" .
                "\tphp artisan silverstripe:password");
        }
    }
}