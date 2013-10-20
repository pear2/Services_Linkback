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
namespace PEAR2\Services\Linkback\Server\Responder;

/**
 * Sends HTTP headers and body content back to the client.
 *
 * @category Services
 * @package  PEAR2\Services\Linkback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Linkback
 */
abstract class Base
{
    /**
     * Output the given response back to the client.
     * Does not send content-type header
     *
     * @param string $content Content to send
     *
     * @return void
     */
    public function sendOutput($content)
    {
        echo $content;
    }

    /**
     * Send a HTTP header line to the client.
     *
     * @param string $line Single header line
     *
     * @return void
     */
    public function sendHeader($line)
    {
        header($line);
    }
}
?>
