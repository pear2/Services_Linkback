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
namespace PEAR2\Services\Linkback;

/**
 * Load a DOMDocument object from a given HTTP response object.
 *
 * Respects the content type header and loads the response accoringly
 * as XML or HTML.
 *
 * @category Services
 * @package  PEAR2\Services\Linkback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Linkback
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