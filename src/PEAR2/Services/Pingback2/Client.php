<?php
namespace PEAR2\Services\Pingback2;

class Client
{
    /**
     * HTTP request object that's used to do the requests
     * @var \HTTP_Request2
     */
    protected $request;

    /**
     * (debug) message that we get on a successful pingback request
     * @var string
     */
    protected $message;

    /**
     * Fault code that gets set when pingback fails
     * @var integer
     */
    protected $faultCode;

    /**
     * Fault error message that gets set when pingback fails
     * @var string
     */
    protected $faultString;


    /**
     * Initializes the HTTP request object
     */
    public function __construct()
    {
        $this->setRequest(new \HTTP_Request2());
    }

    /**
     * Send a pingback, indicating a link from source to target.
     * The target's pingback server will be discovered automatically.
     *
     * @param string $sourceUri URL on this side, it links to $targetUri
     * @param string $targetUri Remote URL that shall be notified about source
     *
     * @return boolean True when all went well, false if there was an error
     *                 Use getFaultCode() and getFaultString() to find out about
     *                 the errors, getMessage() about the debug message in case
     *                 all went well.
     *
     * FIXME: How to indicate discovery failure? Response object?
     * FIXME: add reset() method to reset before each request
     *
     * @see getFaultString()
     * @see getFaultCode()
     * @see getMessage()
     */
    public function send($sourceUri, $targetUri)
    {
        //FIXME: validate $sourceUri, $targetUri

        $serverUri = $this->discoverServer($targetUri);
        if ($serverUri === false) {
            //target resource is not pingback endabled
            return false;
        }

        return $this->sendPingback($serverUri, $sourceUri, $targetUri);
    }

    /**
     * Autodiscover the pingback server for the given URI.
     *
     * @param string $targetUri Some URL to discover the pingback server of
     *
     * @return string|boolean False when it failed, server URI on success
     */
    protected function discoverServer($targetUri)
    {
        //at first, try a HEAD request that does not transfer so much data
        $req = $this->getRequest();
        $req->setUrl($targetUri);
        $req->setMethod(\HTTP_Request2::METHOD_HEAD);
        $res = $req->send();

        $headerUri = $res->getHeader('X-Pingback');
        //FIXME: validate URI
        if ($headerUri !== null) {
            return $headerUri;
        }

        //HEAD failed, do a normal GET
        $req->setMethod(\HTTP_Request2::METHOD_GET);
        $res = $req->send();

        //yes, maybe the server does return this header now
        $headerUri = $res->getHeader('X-Pingback');
        //FIXME: validate URI
        if ($headerUri !== null) {
            return $headerUri;
        }

        $body = $res->getBody();
        $regex = '#<link rel="pingback" href="([^"]+)" ?/?>#';
        if (preg_match($regex, $body, $matches) === false) {
            return false;
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
     * @return boolean True when all went well, false if there was an error
     *                 Use getFaultCode() and getFaultString() to find out about
     *                 the errors, getMessage() about the debug message in case
     *                 all went well.
     *
     * @see getFaultString()
     * @see getFaultCode()
     * @see getMessage()
     */
    protected function sendPingback($serverUri, $sourceUri, $targetUri)
    {
        $encSourceUri = htmlspecialchars($sourceUri);
        $encTargetUri = htmlspecialchars($targetUri);

        $req = $this->getRequest();
        $req->setUrl($serverUri)
            ->setMethod(\HTTP_Request2::METHOD_POST)
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
        return $this->handleResponse($res);
    }

    /**
     * Handles a XML-RPC response and sets internal variables.
     *
     * @param object $res HTTP response object
     *
     * @return boolean True if all went well, false if not.
     *
     * @uses $faultCode
     * @uses $faultString
     * @uses $message
     */
    protected function handleResponse(\HTTP_Request2_Response $res)
    {
        if (intval($res->getStatus() / 100) != 2) {
            //no 2xx status code
            return false;
        }
        $types = explode(';', $res->getHeader('content-type'));
        if (count($types) < 1 || trim($types[0]) != 'text/xml') {
            return false;
        }

        $rpc = xmlrpc_decode($res->getBody());
        if ($rpc && !xmlrpc_is_fault($rpc)) {
            $this->message = $rpc;
            return true;
        }

        $this->faultCode   = $rpc['faultCode'];
        $this->faultString = $rpc['faultString'];
        return false;
    }

    //FIXME: implement http://old.aquarionics.com/misc/archives/blogite/0198.html

    /**
     * Returns the HTTP request object that's used internally
     *
     * @return \HTTP_Request2
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Sets a custom HTTP request object that will be used to do HTTP requests
     *
     * @param \HTTP_Request2 $request Request object
     *
     * @return self
     */
    public function setRequest(\HTTP_Request2 $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Returns the XML-RPC fault code
     *
     * @return integer Error code
     */
    public function getFaultCode()
    {
        return $this->faultCode;
    }

    /**
     * Returns the XML-RPC fault message
     *
     * @return string Error message
     */
    public function getFaultString()
    {
        return $this->faultString;
    }

    /**
     * Returns the XML-RPC debug message for a successful pingback
     *
     * @return string Message
     */
    public function getMessage()
    {
        return $this->message;
    }

}

?>
