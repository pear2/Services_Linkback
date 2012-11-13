<?php
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../src/');
function __autoload($class) {
    include_once str_replace(array('_', '\\'), '/', $class) . '.php';
}

class PingbackLogger
    implements \PEAR2\Services\Pingback2\Server_Callback_IStorage
{
    public function storePingback(
        $target, $source, $sourceBody, \HTTP_Request2_Response $res
    ) {
        file_put_contents(
            __DIR__ . '/test-server-pingback.log',
            'Pingback for ' . $target . ' from ' . $source . "\n",
            \FILE_APPEND
        );
    }
}

$s = new \PEAR2\Services\Pingback2\Server();
$s->addCallback(new PingbackLogger());
$s->run();

?>
