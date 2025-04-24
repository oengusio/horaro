<?php

use App\Kernel;

// No clue how this file gets loaded more than once per request
if (!defined('HORARO_ROOT')) {
    define('HORARO_ROOT', dirname(__DIR__));
}

$file = HORARO_ROOT.'/maintenance';

if (file_exists($file)) {
    $allowedIPs = array_map('trim', file($file));

    if (isset($_SERVER['REMOTE_ADDR']) && !in_array($_SERVER['REMOTE_ADDR'], $allowedIPs)) {
        header('HTTP/1.1 503 Service Unavailable');
        header('Cache-Control: private, no-cache');
        header('Expires: Tue, 09 Apr 1975 12:00:00 GMT');
        print file_get_contents(HORARO_ROOT.'/resources/maintenance.html');
        exit(1);
    }
}

mb_internal_encoding('UTF-8');

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
