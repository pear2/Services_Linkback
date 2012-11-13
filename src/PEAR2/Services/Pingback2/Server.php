<?php
namespace PEAR2\Services\Pingback2;

class Server
{
    protected $callbacks = array();

    public function __construct()
    {
        $this->addCallback(new Server_Callback_FetchSource());
        $this->addCallback(new Server_Callback_LinkExists());
    }

    public function run()
    {
        $post = file_get_contents('php://input');

        $xs = xmlrpc_server_create();
        xmlrpc_server_register_method(
            $xs, 'pingback.ping', array($this, 'handlePingbackPing')
        );
        $out = xmlrpc_server_call_method($xs, $post, null);

        $this->sendResponse($out);
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

        try {
            if (!$this->verifyTargetExists($target)) {
                return array(
                    'faultCode'   => States::TARGET_URI_NOT_FOUND,
                    'faultString' => 'The targer URI does not exist.'
                );
            }

            $res = $this->fetchSource($source);
            if ($res->getStatus() / 100 != 2) {
                //some error fetching the url
                return array(
                    'faultCode'   => States::SOURCE_URI_NOT_FOUND,
                    'faultString' => 'The source URI does not exist.'
                );
            }

            if (!$this->verifyLinkExists($target, $source, $res->getBody(), $res)) {
                return array(
                    'faultCode'   => States::NO_LINK_IN_SOURCE,
                    'faultString' => 'The source URI does not contain a link to the'
                    . ' target URI, and so cannot be used as a source.'
                );
            }

            $this->storePingback($target, $source, $res->getBody(), $res);
        } catch (\Exception $e) {
            return array(
                'faultCode'   => $e->getCode(),
                'faultString' => $e->getMessage()
            );
        }

        return array('Pingback received and processed');
    }

    protected function sendResponse($xml)
    {
        header('Content-type: text/xml; charset=utf-8');
        echo $xml;
    }

    /**
     * Set an array of callback objects
     *
     * @param array $callbacks Array of objects that implement at least one
     *                         of the interfaces
     *
     * @return self
     */
    public function setCallbacks($callbacks)
    {
        $this->callbacks = $callbacks;
    }

    /**
     * Add a single callback object
     *
     * @param object $callback Object that implements at least one
     *                         of the interfaces
     *
     * @return self
     */
    public function addCallback($callback)
    {
        $this->callbacks[] = $callback;
    }

    /**
     * Fetch the source URL and return it.
     * The response object of the first callback providing one will be returned.
     *
     * @param string $url URL to fetch
     *
     * @return \HTTP_Request2_Response Response object
     *
     * @throws Exception When something fatally fails
     */
    protected function fetchSource($url)
    {
        $finalres = null;
        foreach ($this->callbacks as $callback) {
            if (!$callback instanceof Server_Callback_ISource) {
                continue;
            }

            $res = $callback->fetchSource($url);
            if ($res instanceof \HTTP_Request2_Response) {
                $finalres = $res;;
            }
        }
        return $finalres;
    }

    /**
     * Verifies that the given target URI exists in our system.
     *
     * @param string $target Target URI that got linked to
     *
     * @return boolean True if the target URI exists, false if not
     *
     * @throws Exception When something fatally fails
     */
    public function verifyTargetExists($target)
    {
        $exists = true;
        foreach ($this->callbacks as $callback) {
            if (!$callback instanceof Server_Callback_ITarget) {
                continue;
            }

            $exists &= $callback->verifyTargetExists($target);
        }

        return $exists;
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
     *
     * @throws Exception When something fatally fails
     */
    public function verifyLinkExists(
        $target, $source, $sourceBody, \HTTP_Request2_Response $res
    ) {
        $exists = true;
        foreach ($this->callbacks as $callback) {
            if (!$callback instanceof Server_Callback_ILink) {
                continue;
            }

            //FIXME: only count true?
            $exists &= $callback->verifyLinkExists(
                $target, $source, $sourceBody, $res
            );
        }

        return $exists;
    }

    /**
     * Stores the pingback somewhere
     *
     * @param string $target     Target URI that should be linked in $source
     * @param string $source     Pingback source URI that should link to target
     * @param string $sourceBody Content of $source URI
     * @param object $res        HTTP response from fetching $source
     *
     * @return void
     *
     * @throws Exception When storing a pingback fatally failed
     */
    protected function storePingback(
        $target, $source, $sourceBody, \HTTP_Request2_Response $res
    ) {
        foreach ($this->callbacks as $callback) {
            if (!$callback instanceof Server_Callback_IStorage) {
                continue;
            }
            $callback->storePingback(
                $target, $source, $sourceBody, $res
            );
        }
    }
}

?>
