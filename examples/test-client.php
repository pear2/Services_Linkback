<?php
require 'HTTP/Request2.php';
require __DIR__ . '/../src/PEAR2/Services/Pingback2/Client.php';
require __DIR__ . '/../src/PEAR2/Services/Pingback2/Response/Ping.php';
$c = new \PEAR2\Services\Pingback2\Client();
$r = $c->send(
    'http://p.cweiske.de/18?foob',
    'http://pingbacktest.wordpress.com/2008/01/15/hello-world/'
);
if ($r->isError()) {
    var_dump($r->getCode(), $r->getMessage());
} else {
    var_dump($r->getMessage());
}
?>