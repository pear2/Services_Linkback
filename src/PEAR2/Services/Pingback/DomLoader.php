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
namespace PEAR2\Services\Pingback;

/**
 * Default implementation for the ILinkExists interface:
 * Verifies that the source body contains a link to the target URL.
 *
 * You may use it in your own pingback server.
 *
 * @category Services
 * @package  PEAR2\Services\Pingback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Pingback
 */
class DomLoader
{
    /**
     * Load a DOMDocument from the given HTML or XML
     *
     * @param string $sourceBody Content of $source URI
     * @param object $res        HTTP response from fetching $source
     *
     * @return \DOMDocument DOM document object with HTML/XML loaded
     */
    public static function load($sourceBody, \HTTP_Request2_Response $res)
    {
        $doc = new \DOMDocument();

        $typeParts = explode(';', $res->getHeader('content-type'));
        $type = $typeParts[0];
        if ($type == 'application/xhtml+xml'
            || $type == 'application/xml'
            || $type == 'text/xml'
        ) {
            $doc->loadXML($sourceBody);
        } else {
            $doc->loadHTML($sourceBody);
        }

        return $doc;
    }
}
?>