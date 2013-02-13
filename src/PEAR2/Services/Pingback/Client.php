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
use HTTP_Request2;

/**
 * Pingback client, allowing you to send pingbacks to remote sites
 * to tell them that you linked to them.
 *
 * @category Services
 * @package  PEAR2\Services\Pingback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Pingback
 */
class Client
{
    /**
     * HTTP request object that's used to do the requests
     * @var HTTP_Request2
     */
    protected $request;

    /**
     * Debug mode
     * If activated, the response object will contain the HTTP Response
     *
     * @var boolean
     */
    protected $debug = false;

    /**
     * Full response of the pingback request, helpful for debugging
     * in some error conditions.
     * Gets set when $debug is enabled
     *
     * @var \HTTP_Request2_Response
     */
    protected $debugResponse;

    /**
     * Send a pingback, indicating a link from source to target.
     * The target's pingback server will be discovered automatically.
     *
     * @param string $sourceUri URL on this side, it links to $targetUri
     * @param string $targetUri Remote URL that shall be notified about source
     *
     * @return Response\Ping Pingback response object containing all error
     *                       and status information.
     */
    public function send($sourceUri, $targetUri)
    {
        $this->debugResponse = null;

        //FIXME: validate $sourceUri, $targetUri

        $serverUri = $this->discoverServer($targetUri);
        if (is_object($serverUri) && $serverUri instanceof Response\Ping) {
            if ($this->debug) {
                $serverUri->setResponse($this->debugResponse);
            }
            return $serverUri;
        }

        return $this->sendPingback($serverUri, $sourceUri, $targetUri);
    }

    /**
     * Autodiscover the pingback server for the given URI.
     *
     * @param string $targetUri Some URL to discover the pingback server of
     *
     * @return string|Response\Ping Server URI on success, Ping response object
     *                              on failure. Response object has debug
     *                              response not set.
     */
    protected function discoverServer($targetUri)
    {
        //at first, try a HEAD request that does not transfer so much data
        $req = $this->getRequest();
        $req->setUrl($targetUri);
        $req->setMethod(HTTP_Request2::METHOD_HEAD);
        $res = $req->send();
        if ($this->debug) {
            $this->debugResponse = $res;
        }

        if (intval($res->getStatus() / 100) > 3
            && $res->getStatus() != 405 //method not supported/allowed
        ) {
            return new Response\Ping(
                'Error fetching target URI', States::TARGET_URI_NOT_FOUND
            );
        }

        $headerUri = $res->getHeader('X-Pingback');
        //FIXME: validate URI
        if ($headerUri !== null) {
            return $headerUri;
        }

        //HEAD failed, do a normal GET
        $req->setMethod(HTTP_Request2::METHOD_GET);
        $res = $req->send();
        if ($this->debug) {
            $this->debugResponse = $res;
        }
        if (intval($res->getStatus() / 100) > 3) {
            return new Response\Ping(
                'Error fetching target URI', States::TARGET_URI_NOT_FOUND
            );
        }

        //yes, maybe the server does return this header now
        $headerUri = $res->getHeader('X-Pingback');
        //FIXME: validate URI
        if ($headerUri !== null) {
            return $headerUri;
        }

        $body = $res->getBody();
        $regex = '#<link rel="pingback" href="([^"]+)" ?/?>#';
        if (preg_match($regex, $body, $matches) == 0) {
            //target resource is not pingback enabled
            return new Response\Ping(
                'No pingback server found for URI',
                States::PINGBACK_UNSUPPORTED
            );
        }

        $uri = $matches[1];
        $uri = str_replace(
            array('&amp;', '&lt;', '&gt;', '&quot;'),
            array('&', '<', '>', '"'),
            $uri
        );
        //FIXME: validate URI
        return $uri;
    }

    /**
     * Contacts the given pingback server and tells him that source links to
     * target.
     *
     * @param string $serverUri URL of XML-RPC server that implements pingback
     * @param string $sourceUri URL on this side, it links to $targetUri
     * @param string $targetUri Remote URL that shall be notified about source
     *
     * @return Response\Ping Pingback response object containing all error
     *                       and status information.
     */
    protected function sendPingback($serverUri, $sourceUri, $targetUri)
    {
        $encSourceUri = htmlspecialchars($sourceUri);
        $encTargetUri = htmlspecialchars($targetUri);

        $req = $this->getRequest();
        $req->setUrl($serverUri)
            ->setMethod(HTTP_Request2::METHOD_POST)
            ->setHeader('Content-type: text/xml')
            ->setBody(
<<<XML
<?xml version="1.0" encoding="utf-8"?>
<methodCall>
 <methodName>pingback.ping</methodName>
 <params>
  <param><value><string>$encSourceUri</string></value></param>
  <param><value><string>$encTargetUri</string></value></param>
 </params>
</methodCall>
XML
            );
        $res = $req->send();

        $pres = new Response\Ping();
        $pres->loadFromPingbackResponse($res, $this->debug);
        return $pres;
    }

    //FIXME: implement http://old.aquarionics.com/misc/archives/blogite/0198.html

    /**
     * Enable debugging by collecting HTTP response objects.
     *
     * @param boolean $debug True to enable debugging, false to deactivate it.
     *
     * @return void
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * Returns the HTTP request object that's used internally
     *
     * @return HTTP_Request2
     */
    public function getRequest()
    {
        if ($this->request === null) {
            $this->setRequest(new HTTP_Request2());
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
    public function setRequest(HTTP_Request2 $request)
    {
        $this->request = $request;
        return $this;
    }

}

?>
