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
namespace PEAR2\Services\Linkback\Server\Callback;

/**
 * ISource interface: Fetch the source URL contents.
 *
 * @category Services
 * @package  PEAR2\Services\Linkback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Linkback
 */
interface ISource
{
    /**
     * Fetch the source URL and return it.
     * The response object of the first callback providing one will be returned.
     *
     * @param string $url URL to fetch
     *
     * @return \HTTP_Request2_Response Response object
     *
     * @throws \SPb\Exception When something fatally fails
     */
    public function fetchSource($url);
}


?>
