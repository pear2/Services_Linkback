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
 * Sends HTTP headers and XML back to the client.
 *
 * @category Services
 * @package  PEAR2\Services\Linkback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Linkback
 */
class Mock extends Pingback
{
    /**
     * XML that should be send out
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
     * Stores the xml response
     *
     * @param string $content XML response to send
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
