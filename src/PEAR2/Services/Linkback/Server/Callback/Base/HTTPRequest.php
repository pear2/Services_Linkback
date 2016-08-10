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
namespace PEAR2\Services\Linkback\Server\Callback\Base;

/**
 * Base class for callbacks that send HTTP requests.
 *
 * @category Services
 * @package  PEAR2\Services\Linkback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Linkback
 */
abstract class HTTPRequest
{
    /**
     * HTTP request object that's used to do the requests
     *
     * @var HTTP_Request2
     */
    protected $request;

    /**
     * Returns the HTTP request object that's used internally
     *
     * @return HTTP_Request2
     */
    public function getRequest()
    {
        if ($this->request === null) {
            $this->setRequest(new \HTTP_Request2());
        }
        return $this->request;
    }

    /**
     * Sets a custom HTTP request object that will be used to do HTTP requests
     *
     * @param HTTP_Request2 $request Request object
     *
     * @return self
     */
    public function setRequest(\HTTP_Request2 $request)
    {
        $this->request = $request;
        return $this;
    }
}
?>
