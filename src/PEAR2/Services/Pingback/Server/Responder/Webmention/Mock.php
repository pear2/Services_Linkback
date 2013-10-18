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
namespace PEAR2\Services\Pingback\Server\Responder\Webmention;
use PEAR2\Services\Pingback\Server\Responder\Webmention;

/**
 * Sends HTTP headers and a webmention result back to the client.
 *
 * @category Services
 * @package  PEAR2\Services\Pingback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Pingback
 */
class Mock extends Webmention
{
    /**
     * Stored content from output()
     *
     * @var string
     */
    public $content;

    /**
     * Array with header lines
     *
     * @var array
     */
    public $header = array();

    /**
     * Store the content to be outputted
     *
     * @param string $content Content to send
     *
     * @return void
     */
    public function sendOutput($content)
    {
        $this->content = $content;
    }

    /**
     * Store the header line
     *
     * @param string $line Single header line
     *
     * @return void
     */
    public function sendHeader($line)
    {
        $this->header[] = $line;
    }
}
?>
