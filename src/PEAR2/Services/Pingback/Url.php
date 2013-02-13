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
 * URL validation helper class
 *
 * @category Services
 * @package  PEAR2\Services\Pingback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Pingback
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
        ) {
            return false;
        }
        return true;
    }
}
?>
