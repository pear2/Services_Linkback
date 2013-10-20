<?php
file_put_contents(
    '/tmp/phorkie-' . date('c'),
    var_export($_SERVER, true)
    . var_export(
        json_decode(file_get_contents('php://input')),
        true
    )
);
?>