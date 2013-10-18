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

/**
 * Sends HTTP headers and XML back to the client.
 *
 * @category Services
 * @package  PEAR2\Services\Pingback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Pingback
 */
class Responder
{
    /**
     * Send the given XML response back to the client.
     * Sends the correct headers.
     *
     * @param string $xml XML response to send
     *
     * @return void
     */
    public function sendXml($xml)
    {
        $this->sendHeader('Content-type: text/xml; charset=utf-8');
        $this->sendOutput($xml);
    }

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
