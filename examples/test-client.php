<?php
require 'HTTP/Request2.php';
require __DIR__ . '/../src/PEAR2/Services/Pingback2/Client.php';
$c = new \PEAR2\Services\Pingback2\Client();
$r = $c->send(
    'http://p.cweiske.de/18?foo',
    'http://pingbacktest.wordpress.com/2008/01/15/hello-world/'
);
var_dump($r);
if ($r === false) {
    var_dump($c->getFaultCode(), $c->getFaultString());
} else {
    var_dump($c->getMessage());
}
?>