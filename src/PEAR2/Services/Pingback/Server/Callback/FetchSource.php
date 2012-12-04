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
            'text/html;q=0.9'
            . ', application/xhtml+xml;q=0.9'
            . ', */*;q=0.1'
        );
        /* FIXME: add content range to respect:
          In order to avoid susceptibility to denial of service attacks,
          pingback servers that fetch the specified source document
          (as described in section 3) are urged to impose limits on the
          size of the source document to be examined and the rate of data
          transfer.  */
        return $req->send();
    }
}
?>
