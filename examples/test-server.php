<?php
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../src/');
function __autoload($class) {
    include_once str_replace(array('_', '\\'), '/', $class) . '.php';
}

$s = new \PEAR2\Services\Pingback2\Server();
$s->run();

?>
