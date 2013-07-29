<?php

namespace Themonkeys\Silverstripe;

/**
 * Silverstripe installs exception and error handlers in Core.php which override the Laravel ones. To be able to swoop
 * in after Silverstripe registers its ones and un-register them, the first reasonable hook available to us is to
 * inject a custom database driver and do our work before we connect to the database.
 *
 * Class MySQLDatabaseWrapper
 * @package Themonkeys\Silverstripe
 */
class MySQLDatabaseWrapper extends \MySQLDatabase {
    private static $firstTime = true;
    public function __construct($parameters) {
        if (static::$firstTime) {
            static::$firstTime = false;
            // deregister the Silverstripe exception and error handlers
            restore_exception_handler();
            restore_error_handler();
        }

        parent::__construct($parameters);
    }
}