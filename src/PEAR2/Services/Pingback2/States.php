<?php
namespace PEAR2\Services\Pingback2;

class States
{
    /**
     * Pingback server did not return the correct HTTP status code
     */
    const HTTP_STATUS = 100;

    /**
     * Pingback server did not return the correct HTTP content type
     */
    const CONTENT_TYPE = 101;

    /**
     * Remote URL does not have a pingback server
     */
    const PINGBACK_UNSUPPORTED = 200;
}

?>
