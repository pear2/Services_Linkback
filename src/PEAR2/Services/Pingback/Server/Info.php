<?php
/**
 * This file is part of the PEAR2\Services\Pingback package.
 *
 * PHP version 5
 *
 * @category Services
 * @package  PEAR2\Services\Pingback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Pingback
 */
namespace PEAR2\Services\Pingback\Server;
use HTTP_Request2;

/**
 * Pingback server information. Contains URI and type of pingback server.
 *
 * @category Services
 * @package  PEAR2\Services\Pingback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Pingback
 */
class Info
{
    /**
     * Server URI to send pingbacks to.
     *
     * @var string
     */
    public $uri;

    /**
     * Server type. May be "pingback" or "webmention"
     *
     * @var string
     */
    public $type;

    /**
     * Set server info
     *
     * @param string $type Server type (pingback/webmention)
     * @param string $uri  URI to send pingbacks to
     */
    public function __construct($type, $uri)
    {
        $this->type = $type;
        $this->uri = $uri;
    }
}
?>
