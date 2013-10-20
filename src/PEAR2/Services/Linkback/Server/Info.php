<?php
/**
 * This file is part of the PEAR2\Services\Linkback package.
 *
 * PHP version 5
 *
 * @category Services
 * @package  PEAR2\Services\Linkback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Linkback
 */
namespace PEAR2\Services\Linkback\Server;
use HTTP_Request2;

/**
 * Linkback server information. Contains URI and type of linkback server.
 *
 * @category Services
 * @package  PEAR2\Services\Linkback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Linkback
 */
class Info
{
    /**
     * Server URI to send linkbacks to.
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
     * @param string $uri  URI to send linkbacks to
     */
    public function __construct($type, $uri)
    {
        $this->type = $type;
        $this->uri = $uri;
    }
}
?>
