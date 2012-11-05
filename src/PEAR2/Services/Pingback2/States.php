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

    const PARAMETER_MISSING = -32000;

    /**
     * The source URI could not be retrieved.
     * Defined by the pingback specification.
     */
    const SOURCE_URI_NOT_FOUND = 16;

    /**
     * The source URI content does not contain a link to the target.
     * Defined by the pingback specification.
     */
    const NO_LINK_IN_SOURCE = 17;

    /**
     * The specified target URI does not exist.
     * Defined by the pingback specification.
     */
    const TARGET_URI_NOT_FOUND = 32;
}

?>
