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

        $uTarget = new \Net_URL2($targetUri);
        $info = $this->extractHeader($res, 'HEAD', $uTarget);
        if ($info !== null) {
            return $info;
        }

        list($type) = explode(';', $res->getHeader('Content-type'));
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
        // e.g. PHP's Phar::webPhar() does not work with HEAD
        // https://bugs.php.net/bug.php?id=51918
        $info = $this->extractHeader($res, 'GET', $uTarget);
        if ($info !== null) {
            return $info;
        }

        $body = $res->getBody();
        $doc = DomLoader::load($body, $res);

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('h', 'http://www.w3.org/1999/xhtml');

        $nodeList = $xpath->query(
            // <link> with rel=pingback|webmention is "body-ok" in HTML5
            // https://html.spec.whatwg.org/multipage/semantics.html#body-ok
            '//*[(self::link or self::h:link)'
            . ' and @href'
            . ' and ('
            . ' contains(concat(" ", normalize-space(@rel), " "), " webmention ")'
            . ' or contains(concat(" ", normalize-space(@rel), " "), " http://webmention.org/ ")'
            . ' or @rel="pingback"'
            . ')'
            . ']'
            // <a>
            . ' | '
            . '/*[self::html or self::h:html]'
            . '/*[self::body or self::h:body]'
            . '//*[(self::a or self::h:a)'
            . ' and @href'
            . ' and ('
            . ' contains(concat(" ", normalize-space(@rel), " "), " webmention ")'
            . ' or contains(concat(" ", normalize-space(@rel), " "), " http://webmention.org/ ")'
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
            $types = explode(
                ' ', $link->attributes->getNamedItem('rel')->nodeValue
            );
            if (array_search('http://webmention.org/', $types) !== false) {
                $types[] = 'webmention';
            }
            if (array_search('webmention', $types) !== false
                && $this->urlValidator->relative($uri)
            ) {
                //webmention spec allows relative URLs, pingback not
                $uri = (string) $uTarget->resolve($uri);
            }
            if ($this->urlValidator->validate($uri)) {
                foreach ($types as $type) {
                    if ($type == 'webmention' || $type == 'pingback') {
                        if (!isset($arLinks[$type])) {
                            $arLinks[$type] = $uri;
                        }
                    }
                }
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
     * Extract webmention and pingback headers from the HTTP response.
     * Create server info object if found
     *
     * @param \HTTP_Request2_Response $res    HTTP response from the target
     * @param string                  $method HTTP method used to fetch $res
     * @param \Net_URL2               $url    Base URL to resolve relative links
     *
     * @return Server\Info|Response\Ping Information about linkback endpoint
     *                                   or error information
     *                                   or NULL if no link found
     */
    protected function extractHeader($res, $method, \Net_URL2 $url)
    {
        $http = new \HTTP2();

        //webmention link header
        $links = $http->parseLinks($res->getHeader('Link'));
        foreach ($links as $link) {
            if (isset($link['_uri']) && isset($link['rel'])
                && (array_search('webmention', $link['rel']) !== false
                || array_search('http://webmention.org/', $link['rel']) !== false)
            ) {
                if ($this->urlValidator->relative($link['_uri'])) {
                    //relative URL
                    $link['_uri'] = (string) $url->resolve($link['_uri']);
                }
                if (!$this->urlValidator->validate($link['_uri'])) {
                    return new Response\Ping(
                        $method . ' Link webmention server URI invalid: '
                        . $link['_uri'],
                        States::INVALID_URI
                    );
                }
                return new Server\Info('webmention', $link['_uri']);
            }
        }

        //pingback url header
        //pingback 1.0 spec does not allow relative links
        $headerUri = $res->getHeader('X-Pingback');
        if ($headerUri !== null) {
            if (!$this->urlValidator->validate($headerUri)) {
                return new Response\Ping(
                    $method . ' X-Pingback server URI invalid: ' . $headerUri,
                    States::INVALID_URI
                );
            }
            return new Server\Info('pingback', $headerUri);
        }

        return null;
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
     * Returns the HTTP request object clone that can be used
     * for one HTTP request.
     *
     * @return HTTP_Request2 Clone of the setRequest() object
     */
    public function getRequest()
    {
        if ($this->request === null) {
            $request = new HTTP_Request2();
            //yes, people redirect xmlrpc.php
            $request->setConfig('follow_redirects', true);
            //keep POST on redirect
            $request->setConfig('strict_redirects', true);
            $this->setRequestTemplate($request);
        }

        //we need to clone because previous requests could have
        //set internal variables like POST data that we don't want now
        return clone $this->request;
    }

    /**
     * Sets a custom HTTP request object that will be used to do HTTP requests
     *
     * @param HTTP_Request2 $request Request object
     *
     * @return self
     */
    public function setRequestTemplate(HTTP_Request2 $request)
    {
        $this->request = $request;
        return $this;
    }

}

?>
