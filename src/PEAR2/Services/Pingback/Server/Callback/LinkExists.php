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
namespace PEAR2\Services\Pingback\Server\Callback;
use PEAR2\Services\Pingback\DomLoader;

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
class LinkExists implements ILink
{
    /**
     * Verifies that a link from $source to $target exists.
     *
     * @param string $target     Target URI that should be linked in $source
     * @param string $source     Pingback source URI that should link to target
     * @param string $sourceBody Content of $source URI
     * @param object $res        HTTP response from fetching $source
     *
     * @return boolean True if $source links to $target
     */
    public function verifyLinkExists(
        $target, $source, $sourceBody, \HTTP_Request2_Response $res
    ) {
        $doc = DomLoader::load($sourceBody, $res);
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('h', 'http://www.w3.org/1999/xhtml');

        $targetNoQuotes = str_replace('"', '', $target);
        $nodeList = $xpath->query(
            '//*[(self::a or self::h:a)'
            . ' and (@href="' . $target . '"'
            . ' or starts-with(@href, "' . $targetNoQuotes . '#")'
            . ')'
            . ']'
        );

        if ($nodeList->length > 0) {
            return true;
        }

        //now check for relative links - needed when pages on the same server
        // link each other
        if (parse_url($source, PHP_URL_HOST) != parse_url($target, PHP_URL_HOST)) {
            //not on the same server
            return false;
        }

        $sourceUrl = new \Net_URL2($source);
        //FIXME: base URL in html code

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('h', 'http://www.w3.org/1999/xhtml');
        $links = $xpath->query('//*[self::a or self::h:a]');
        foreach ($links as $link) {
            $url = (string)$sourceUrl->resolve(
                $link->attributes->getNamedItem('href')->nodeValue
            );
            if ($url == $target) {
                return true;
            }
        }

        return false;
    }
}

?>
