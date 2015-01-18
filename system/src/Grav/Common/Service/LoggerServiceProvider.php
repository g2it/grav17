<?php
namespace Grav\Common\Service;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use \Monolog\Handler\RotatingFileHandler;

class LoggerServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $log = new Logger('grav');
        $log_file = LOG_DIR.'grav.log'; // @todo: locator not defined at this point.
        $log_days = 14; // @todo: why defined and not used?

        // $log->pushHandler(new RotatingFileHandler($log_file, $log_days, Logger::WARNING));
        $log->pushHandler(new StreamHandler($log_file, Logger::WARNING));

        $container['log'] = $log;
    }
}
