<?php
if ($argc < 3) {
    echo "Please pass source and target\n";
    exit(1);
}
$source = $argv[1];
$target = $argv[2];

echo "Sending pingback from $source to $target\n";

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../src/');
function __autoload($class) {
    $fileName = str_replace(array('_', '\\'), '/', $class) . '.php';
    if (stream_resolve_include_path($fileName)) {
        require_once $fileName;
    }
}

$c = new \PEAR2\Services\Linkback\Client();
//if you want secure SSL support, you have to configure your own CA list
$req = $c->getRequest();
$req->setConfig(
    array(
        'ssl_verify_peer' => false,
        'ssl_verify_host' => false
    )
);
$c->setDebug(true);
$r = $c->send($source, $target);

if ($r->isError()) {
    echo "Error:\n";
    echo " Error code: " . $r->getCode() . "\n";
    echo " Error message: " . $r->getMessage() . "\n";
} else {
    echo "All fine\n";
    echo " Debug message: " . $r->getMessage() . "\n";
}
//var_dump($r->getResponse()->getHeader(), $r->getResponse()->getBody());
?>
