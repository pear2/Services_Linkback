<?php
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../src/');
function __autoload($class) {
    include_once str_replace(array('_', '\\'), '/', $class) . '.php';
}

$c = new \PEAR2\Services\Pingback2\Client();
$c->setDebug(true);
$r = $c->send(
    //'http://p.cweiske.de/18?foob',
    'http://test.bogo/page-with-link.htm',
    //'http://test.bogo/remote-headeronly.php'
    'http://test.bogo/remote-headlinkonly.php'
    //'http://pingbacktest.wordpress.com/2008/01/15/hello-world/'
);

if ($r->isError()) {
    echo "Error:\n";
    echo " Error code: " . $r->getCode() . "\n";
    echo " Error message: " . $r->getMessage() . "\n";
} else {
    echo "All fine\n";
    echo " Debug message: " . $r->getMessage() . "\n";
}
//var_dump($r->getResponse()->getBody());
?>
