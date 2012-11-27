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
    include_once str_replace(array('_', '\\'), '/', $class) . '.php';
}

$c = new \PEAR2\Services\Pingback\Client();
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
//var_dump($r->getResponse()->getBody());
?>
