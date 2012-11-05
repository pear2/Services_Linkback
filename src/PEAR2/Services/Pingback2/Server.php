<?php
namespace PEAR2\Services\Pingback2;

class Server
{
    public function run()
    {
        $post = file_get_contents('php://input');
        $method = null;
        $params = xmlrpc_decode_request($post, $method);

        if ($method == 'pingback.ping') {
            $res = $this->handlePingbackPing($method, $params);
        }
        var_dump($res);
        //FIXME
        $this->sendResponse();
    }


    /**
     * Handles the pingback.ping method request.
     * We intentionally use the same parameters as required by
     * xmlrpc_server_register_method().
     *
     * @param string $method Name of XML-RPC method (pingback.ping)
     * @param array  $params Array of method parameters
     *
     * @return array Array of return values
     */
    public function handlePingbackPing($method, $params)
    {
        if (count($params) < 2) {
            return array(
                'faultCode'   => States::PARAMETER_MISSING,
                'faultString' => '2 parameters required'
            );
        }
        $source = $params[0];
        $target = $params[1];
        //FIXME: validate URIs

        if (!$this->verifyTargetExists($target)) {
            return array(
                'faultCode'   => States::TARGET_URI_NOT_FOUND,
                'faultString' => 'The targer URI does not exist.'
            );
        }

        $res = $this->fetchURL($source);
        if ($res->getStatus() / 100 != 2) {
            //some error fetching the url
            return array(
                'faultCode'   => States::SOURCE_URI_NOT_FOUND,
                'faultString' => 'The source URI does not exist.'
            );
        }

        if (!$this->verifyLinkExists($target, $res->getBody(), $res)) {
            return array(
                'faultCode'   => States::NO_LINK_IN_SOURCE,
                'faultString' => 'The source URI does not contain a link to the'
                    . 'target URI, and so cannot be used as a source.'
            );
        }

        //FIXME: store pingback
    }

    protected function sendResponse()
    {
        header('Content-type: text/xml; charset=utf-8');
    }

    /**
     * Fetch a URL and return it
     *
     * @param string $url URL to fetch
     *
     * @return \HTTP_Request2_Response Response object
     */
    protected function fetchURL($url)
    {
        $req = new \HTTP_Request2($url);
        $req->addHeader(
            'accept',
            'text/html;q=0.9'
            . ', application/xhtml+xml;q=0.9'
            . ', */*;q=0.1'
        );
        return $req->send();
    }

    /**
     * Verifies that the given target URI exists in our system.
     *
     * @param string $target Target URI that got linked to
     *
     * @return boolean True if the target URI exists, false if not
     */
    public function verifyTargetExists($target)
    {
        //you may overwrite it
        return true;
    }

    /**
     * Verifies that a link from $source to $target exists.
     *
     * @param string $target     Target URI that should be linked in $source
     * @param string $source     Pingback source URI that should link to target
     * @param string $sourceBody Content of $source URI
     * @param object $res        HTTP response from fetching $source
     *
     * @return boolean True if $source links to $target
     */
    public function verifyLinkExists(
        $target, $source, $sourceBody, \HTTP_Request2_Response $res
    ) {
        $doc = new DOMDocument();
        $doc->loadHTML($sourceBody);
        $xpath = new DOMXPath($doc);

        $targetNoQuotes = str_replace('"', '', $target);
        $nodeList = $xpath->query(
            '//a[@href="' . $target . '"'
            . ' or contains(@href, "' . $targetNoQuotes . '#"]'
        );

        return $nodeList->length > 0;
    }
}

?>
