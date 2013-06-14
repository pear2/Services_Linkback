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

/**
 * Default ISource implementation that fetches the $source URL contents.
 *
 * You may use it in your pingback server.
 *
 * @category Services
 * @package  PEAR2\Services\Pingback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Pingback
 */
class FetchSource extends Base\HTTPRequest implements ISource
{
    /**
     * Fetch the source URL and return it
     *
     * @param string $url URL to fetch
     *
     * @return \HTTP_Request2_Response Response object
     */
    public function fetchSource($url)
    {
        $req = $this->getRequest();
        $req->setUrl($url);
        $req->setHeader(
            'accept',
            'application/xhtml+xml; q=1'
            . ', application/xml; q=0.9'
            . ', text/xml; q=0.9'
            . ', text/html; q=0.5'
            . ', */*; q=0.1'
        );

        //only request 100k content to prevent denial of service attacks
        $req->setHeader('range', 'bytes=0-102400');
        return $req->send();
    }
}
?>
