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
 * URL validation helper class
 *
 * @category Services
 * @package  PEAR2\Services\Linkback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Linkback
 */
class Url
{
    /**
     * Validate that the given URL string is a HTTP(S) URL and absolute.
     *
     * @param string $url URL string to validate
     *
     * @return boolean True if the URL is valid, false if not
     */
    public function validate($url)
    {
        if ($url == '') {
            return false;
        }

        $urlObj = new \Net_URL2($url);
        if (!$urlObj->isAbsolute()
            || !in_array(strtolower($urlObj->getScheme()), array('https', 'http'))
            || $urlObj->getHost() == ''
        ) {
            return false;
        }
        return true;
    }

    /**
     * Check if an URL is relative
     *
     * @param string $url URL string to check
     *
     * @return boolean True if the URL is relative (no host)
     */
    public function relative($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        return $host === null;
    }
}
?>
