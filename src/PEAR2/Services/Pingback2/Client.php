<?php
namespace PEAR2\Services\Pingback2;

class Client
{
    protected $request;

    public function __construct()
    {
        $this->setRequest(new \HTTP_Request2());
    }

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


    protected function getRequest()
    {
        return $this->request;
    }

    protected function setRequest(\HTTP_Request2 $request)
    {
        $this->request = $request;
    }
}

?>
