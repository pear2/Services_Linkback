<?php
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../src/');
function __autoload($class) {
    include_once str_replace(array('_', '\\'), '/', $class) . '.php';
}

class PingbackLogger
    implements \PEAR2\Services\Pingback\Server\Callback\IStorage
{
    public function storePingback(
        $target, $source, $sourceBody, \HTTP_Request2_Response $res
    ) {
        $logfile = __DIR__ . '/test-server-pingback.log';
        if (!is_writable($logfile)) {
            throw new Exception(
                'Log file is not writable: ' . $logfile,
                12345
            );
        }
        file_put_contents(
            $logfile,
            '[' . date('c') . '] pingback for ' . $target . ' from ' . $source . "\n",
            \FILE_APPEND
        );
    }
}

$s = new \PEAR2\Services\Pingback\Server();
$s->addCallback(new PingbackLogger());
$s->run();

?>
