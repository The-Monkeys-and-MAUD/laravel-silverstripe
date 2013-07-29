<?php
namespace {
    require_once public_path() . '/silverstripe/framework/thirdparty/Zend/Log/Writer/Abstract.php';
}

namespace Themonkeys\Silverstripe {
    use Illuminate\Support\Facades\Log;

    class SilverstripeLaravelLogWriter extends \Zend_Log_Writer_Abstract {

        public static function factory($path, $messageType = 3, $extraHeaders = '') {
            return new SS_LaravelLogWriter();
        }

        /**
         * Write the log message to the file path set
         * in this writer.
         */
        public function _write($event) {
            if(!$this->_formatter) {
                $formatter = new \SS_LogErrorFileFormatter();
                $this->setFormatter($formatter);
            }
            $message = $this->_formatter->format($event);
            switch($event['priorityName']) {
                case 'ERR':
                    Log::error($message);
                    break;
                case 'WARN':
                    Log::warning($message);
                    break;
                case 'NOTICE':
                    Log::info($message);
                    break;
            }
        }

    }
}