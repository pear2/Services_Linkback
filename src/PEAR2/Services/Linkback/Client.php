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
use HTTP_Request2;

/**
 * Linkback client, allowing you to send pingbacks and webmentions
 * to remote sites to tell them that you linked to them.
 *
 * @category Services
 * @package  PEAR2\Services\Linkback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Linkback
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
     * Full response of the linkback request, helpful for debugging
     * in some error conditions.
     * Gets set when $debug is enabled
     *
     * @var \HTTP_Request2_Response
     */
    protected $debugResponse;

    /**
     * URL validation helper
     *
     * @var Url
     */
    protected $urlValidator;


    /**
     * Initialize URL validator
     */
    public function __construct()
    {
        $this->urlValidator = new Url();
    }


    /**
     * Send a linkback (webmention or pingback), indicating a link from
     * source to target.
     * The target's linkback server will be discovered automatically.
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

        if (!$this->urlValidator->validate($sourceUri)) {
            return new Response\Ping(
                'Source URI invalid: ' . $sourceUri,
                States::INVALID_URI
            );
        }
        if (!$this->urlValidator->validate($targetUri)) {
            return new Response\Ping(
                'Target URI invalid: ' . $targetUri,
                States::INVALID_URI
            );
        }

        $serverInfo = $this->discoverServer($targetUri);
        if ($serverInfo instanceof Response\Ping) {
            if ($this->debug) {
                $serverInfo->setResponse($this->debugResponse);
            }
            return $serverInfo;
        }

        if ($serverInfo->type == 'pingback') {
            return $this->sendPingback($serverInfo, $sourceUri, $targetUri);
        }
        return $this->sendWebmention($serverInfo, $sourceUri, $targetUri);
    }

    /**
     * Autodiscover the linkback server for the given URI.
     *
     * @param string $targetUri Some URL to discover the linkback server of
     *
     * @return Server\Info|Response\Ping Server info on success, Ping response object
     *                                   on failure. Response object has debug
     *                                   response not set.
     */
    public function discoverServer($targetUri)
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
                'Error fetching target URI via HEAD', States::TARGET_URI_NOT_FOUND
            );
        }

        //webmention link header
        $http = new \HTTP2();
        $links = $http->parseLinks($res->getHeader('Link'));
        foreach ($links as $link) {
            if (isset($link['_uri']) && isset($link['rel'])
                && (array_search('webmention', $link['rel']) !== false
                || array_search('http://webmention.org/', $link['rel']) !== false)
            ) {
                if (!$this->urlValidator->validate($link['_uri'])) {
                    return new Response\Ping(
                        'HEAD Link webmention server URI invalid: '
                        . $link['_uri'],
                        States::INVALID_URI
                    );
                }
                return new Server\Info('webmention', $link['_uri']);
            }
        }

        //pingback url header
        $headerUri = $res->getHeader('X-Pingback');
        if ($headerUri !== null) {
            if (!$this->urlValidator->validate($headerUri)) {
                return new Response\Ping(
                    'HEAD X-Pingback server URI invalid: ' . $headerUri,
                    States::INVALID_URI
                );
            }
            return new Server\Info('pingback', $headerUri);
        }

        $type = $res->getHeader('Content-type');
        if ($type != 'text/html' && $type != 'text/xml'
            && $type != 'application/xhtml+xml'
            && $res->getStatus() != 405//method not allowed
        ) {
            return new Response\Ping(
                'No linkback server found for URI (HEAD only)',
                States::PINGBACK_UNSUPPORTED
            );
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
        if ($headerUri !== null) {
            if (!$this->urlValidator->validate($headerUri)) {
                return new Response\Ping(
                    'GET X-Pingback server URI invalid: ' . $headerUri,
                    States::INVALID_URI
                );
            }
            return new Server\Info('pingback', $headerUri);
        }

        $body = $res->getBody();
        $doc = DomLoader::load($body, $res);

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('h', 'http://www.w3.org/1999/xhtml');

        $nodeList = $xpath->query(
            '/*[self::html or self::h:html]'
            . '/*[self::head or self::h:head]'
            . '/*[(self::link or self::h:link)'
            . ' and ('
            . '@rel="webmention" or @rel="http://webmention.org/"'
            . ' or @rel="pingback"'
            . ')'
            . ']'
        );

        if ($nodeList->length == 0) {
            //target resource is not pingback/webmention enabled
            return new Response\Ping(
                'No linkback server found for URI',
                States::PINGBACK_UNSUPPORTED
            );
        }

        $arLinks = array();
        foreach ($nodeList as $link) {
            $uri  = $link->attributes->getNamedItem('href')->nodeValue;
            $type = $link->attributes->getNamedItem('rel')->nodeValue;
            if ($type == 'http://webmention.org/') {
                $type = 'webmention';
            }
            if ($this->urlValidator->validate($uri)) {
                $arLinks[$type] = $uri;
            }
        }

        if (count($arLinks) == 0) {
            return new Response\Ping(
                'HTML head link server URI invalid', States::INVALID_URI
            );
        }

        if (isset($arLinks['webmention'])) {
            return new Server\Info('webmention', $arLinks['webmention']);
        }

        return new Server\Info('pingback', $arLinks['pingback']);
    }

    /**
     * Contacts the given pingback server and tells him that source links to
     * target.
     *
     * @param object $serverInfo Information about the server that implements
     *                           pingback
     * @param string $sourceUri  URL on this side, it links to $targetUri
     * @param string $targetUri  Remote URL that shall be notified about source
     *
     * @return Response\Ping Pingback response object containing all error
     *                       and status information.
     */
    protected function sendPingback(Server\Info $serverInfo, $sourceUri, $targetUri)
    {
        $encSourceUri = htmlspecialchars($sourceUri);
        $encTargetUri = htmlspecialchars($targetUri);

        $req = $this->getRequest();
        $req->setUrl($serverInfo->uri)
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

    /**
     * Contacts the given webmention server and tells him that source links to
     * target.
     *
     * @param object $serverInfo Information about the server that implements
     *                           webmention
     * @param string $sourceUri  URL on this side, it links to $targetUri
     * @param string $targetUri  Remote URL that shall be notified about source
     *
     * @return Response\Ping Pingback response object containing all error
     *                       and status information.
     */
    protected function sendWebmention(
        Server\Info $serverInfo, $sourceUri, $targetUri
    ) {
        $req = $this->getRequest();
        $req->setUrl($serverInfo->uri)
            ->setMethod(HTTP_Request2::METHOD_POST)
            ->setHeader('Accept: application/json;q=0.9, */*;q=0.1')
            ->addPostParameter('source', $sourceUri)
            ->addPostParameter('target', $targetUri);
        $res = $req->send();

        $pres = new Response\Ping();
        $pres->loadFromWebmentionResponse($res, $this->debug);
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
