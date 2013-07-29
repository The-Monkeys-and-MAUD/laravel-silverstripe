<?php

namespace Themonkeys\Silverstripe;
use \Config as SilverstripeConfig;
use Illuminate\Support\Facades\App;

class PasswordCommand extends SilverstripeCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'silverstripe:password';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Change the password for Silverstripe\'s CMS admin user(s).';

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
        $this->bootSilverstripe();

        if (\Director::isLive()) {
            $env = App::environment();
            $this->error("This command is not allowed on the '$env' environment.");
            return;
        }

        $defaultMember = \Security::findAnAdministrator();
        if (!$defaultMember->Email) {
            // must be a new install, admin user has no username
            // ask the user for one
            $member = $defaultMember;
            $member->Email = $this->ask("What username/email do you want to give to the default CMS admin user? [admin]:", 'admin');
        } else {
            for (;;) {
                $username = $this->ask("What username do you want to edit? [{$defaultMember->Email}]: ", $defaultMember->Email);
                if ($username == $defaultMember->Email) {
                    $member = $defaultMember;
                    break;
                }

                $member = \Member::get()->filter('Email', $username)->First();
                if ($member && $member->Exists()) {
                    break;
                }
                $this->error("Username '$username' not found.");
            }
        }

        for (;;) {
            for (;;) {
                $password = $this->secret("Enter a new password: ");
                if (strlen($password) > 0) {
                    break;
                }
                $this->error("I can't let you set a blank password.");
            }

            $confirm = $this->secret("Enter again to confirm: ");
            if ($confirm == $password) {
                break;
            }
            $this->error("Those passwords don't match.");
        }

        $member->Password = $password;
        $member->PasswordEncryption = SilverstripeConfig::inst()->get('Security', 'password_encryption_algorithm');

        try {
            $this->info("Saving CMS account '{$member->Email}'...");
            $member->write();
            $this->info('Password changed successfully.');

        } catch (Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }

}