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
namespace PEAR2\Services\Linkback\Server\Callback\FetchSource;

/**
 * Returns a previously set response. Used in unit tests.
 *
 * @category Services
 * @package  PEAR2\Services\Linkback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Linkback
 */
class Mock extends \PEAR2\Services\Linkback\Server\Callback\Base\HTTPRequest
    implements \PEAR2\Services\Linkback\Server\Callback\ISource
{
    /**
     * Mocked HTTP response
     *
     * @var \HTTP_Request2_Response
     */
    protected $res;

    /**
     * Set the mock response object.
     *
     * @param mixed $res HTTP response that will be returned by fetchSource()
     *
     * @return void
     */
    public function setResponse($res)
    {
        $this->res = $res;
    }

    /**
     * Returns the previoulsy set response.
     *
     * @param string $url URL to fetch
     *
     * @return \HTTP_Request2_Response Response object
     */
    public function fetchSource($url)
    {
        return $this->res;
    }
}
?>
