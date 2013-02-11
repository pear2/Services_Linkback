<?php
spl_autoload_register(
    function ($class) {
        $file = str_replace(array('_', '\\'), '/', $class) . '.php';
        if (stream_resolve_include_path($file)) {
            include_once $file;
        }
    }
);
?>
