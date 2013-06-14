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
use HTTP_Request2_Response;

/**
 * Pingback server implementation.
 * The important behavior is controlled by callbacks that may be registered
 * with addCallback() or setCallbacks():
 * - verify that the target exists
 * - fetch source
 * - verify that source links to target
 * - store pingback
 *
 * After adding your callbacks, simply call run().
 *
 * @fixme Add source context fetch code for easier integration
 *
 * @category Services
 * @package  PEAR2\Services\Pingback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Pingback
 */
class Server
{
    /**
     * The file the POST request date are read from
     *
     * @var string
     */
    protected $inputFile = 'php://input';

    /**
     * Responder used to send out header and XML data
     *
     * @var Server\Responder
     */
    protected $responder;

    /**
     * Registered callbacks of all types.
     *
     * @var array
     */
    protected $callbacks = array();

    /**
     * URL validation helper
     *
     * @var Url
     */
    protected $urlValidator;



    /**
     * Registers default callbacks
     */
    public function __construct()
    {
        $this->urlValidator = new Url();
        $this->addCallback(new Server\Callback\FetchSource());
        $this->addCallback(new Server\Callback\LinkExists());
    }

    /**
     * Run the pingback server and respond to the request.
     *
     * @return void
     */
    public function run()
    {
        $post = file_get_contents($this->getInputFile());

        $xs = xmlrpc_server_create();
        xmlrpc_server_register_method(
            $xs, 'pingback.ping', array($this, 'handlePingbackPing')
        );
        $out = xmlrpc_server_call_method($xs, $post, null);

        $resp = $this->getResponder();
        $resp->send($out);
    }

    /**
     * Handles the pingback.ping method request.
     * We intentionally use the same parameters as required by
     * xmlrpc_server_register_method().
     *
     * @param string $method Name of XML-RPC method (pingback.ping)
     * @param array  $params Array of method parameters
     *
     * @return array Array of return values ('faultCode' and 'faultString'),
     *               or single string if all is fine
     */
    public function handlePingbackPing($method, $params)
    {
        if (count($params) < 2) {
            return array(
                'faultCode'   => States::PARAMETER_MISSING,
                'faultString' => '2 parameters required'
            );
        }
        $sourceUri = $params[0];
        $targetUri = $params[1];

        if (!$this->urlValidator->validate($sourceUri)) {
            return array(
                'faultCode'   => States::INVALID_URI,
                'faultString' => 'Source URI invalid (not absolute, not http/https)'
            );
        }
        if (!$this->urlValidator->validate($targetUri)) {
            return array(
                'faultCode'   => States::INVALID_URI,
                'faultString' => 'Target URI invalid (not absolute, not http/https)'
            );
        }

        try {
            if (!$this->verifyTargetExists($targetUri)) {
                return array(
                    'faultCode'   => States::TARGET_URI_NOT_FOUND,
                    'faultString' => 'Target URI does not exist'
                );
            }

            $res = $this->fetchSource($sourceUri);
            if (!$res instanceof HTTP_Request2_Response) {
                //programming error: callback did not return response object
                return array(
                    'faultCode'   => States::SOURCE_NOT_LOADED,
                    'faultString' => 'Source document not loaded'
                );
            }
            if (intval($res->getStatus() / 100) != 2) {
                //some error fetching the url
                return array(
                    'faultCode'   => States::SOURCE_URI_NOT_FOUND,
                    'faultString' => 'Source URI does not exist'
                );
            }

            $exists = $this->verifyLinkExists(
                $targetUri, $sourceUri, $res->getBody(), $res
            );
            if (!$exists) {
                return array(
                    'faultCode'   => States::NO_LINK_IN_SOURCE,
                    'faultString' => 'Source URI does not contain a link to the'
                    . ' target URI, and thus cannot be used as a source'
                );
            }

            $this->storePingback($targetUri, $sourceUri, $res->getBody(), $res);
        } catch (\Exception $e) {
            return array(
                'faultCode'   => $e->getCode(),
                'faultString' => $e->getMessage()
            );
        }

        return 'Pingback received and processed';
    }

    /**
     * Set a responder object
     *
     * @param object $responder Server XML responder object
     *
     * @return self
     */
    public function setResponder(Server\Responder $responder)
    {
        $this->responder = $responder;
        return $this;
    }

    /**
     * Get (and perhaps create) responder object.
     *
     * @return Server\Responder Responder object
     */
    public function getResponder()
    {
        if ($this->responder === null) {
            $this->responder = new Server\Responder();
        }
        return $this->responder;
    }

    /**
     * Modify the path of the file the POST data are read from
     *
     * @param string $inputFile Full path to input file
     *
     * @return self
     */
    public function setInputFile($inputFile)
    {
        $this->inputFile = $inputFile;
        return $this;
    }

    /**
     * Return the path of the file the POST data are read from
     *
     * @return string Path of POST input data file
     */
    public function getInputFile()
    {
        return $this->inputFile;
    }

    /**
     * Set an array of callback objects
     *
     * @param array $callbacks Array of objects that implement at least one
     *                         of the interfaces
     *
     * @return self
     *
     * @throws Exception When one of the callback objects is invalid.
     */
    public function setCallbacks(array $callbacks)
    {
        $this->verifyCallbacks($callbacks);
        $this->callbacks = $callbacks;
        return $this;
    }

    /**
     * Add a single callback object
     *
     * @param object $callback Object that implements at least one
     *                         of the interfaces
     *
     * @return self
     *
     * @throws Exception When one of the callback objects is invalid.
     */
    public function addCallback($callback)
    {
        $this->verifyCallbacks(array($callback));
        $this->callbacks[] = $callback;
        return $this;
    }

    /**
     * Validate callback objects.
     * Checks that the callbacks implement at least one of the server callback
     * interfaces.
     *
     * @param array $callbacks Array of callback objects
     *
     * @return void
     *
     * @throws Exception When one of the callback objects is invalid.
     */
    public function verifyCallbacks(array $callbacks)
    {
        foreach ($callbacks as $callback) {
            if (!$callback instanceof Server\Callback\ILink
                && !$callback instanceof Server\Callback\ISource
                && !$callback instanceof Server\Callback\IStorage
                && !$callback instanceof Server\Callback\ITarget
            ) {
                throw new Exception(
                    'Callback object needs to implement one of the'
                    . ' pingback server callback interfaces',
                    States::CALLBACK_INVALID
                );
            }
        }
    }

    /**
     * Fetch the source URL and return it.
     * The response object of the first callback providing one will be returned.
     *
     * @param string $url URL to fetch
     *
     * @return HTTP_Request2_Response Response object
     *
     * @throws Exception When something fatally fails
     */
    protected function fetchSource($url)
    {
        $finalres = null;
        foreach ($this->callbacks as $callback) {
            if (!$callback instanceof Server\Callback\ISource) {
                continue;
            }

            $res = $callback->fetchSource($url);
            if ($res instanceof HTTP_Request2_Response) {
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
            if (!$callback instanceof Server\Callback\ITarget) {
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
        $target, $source, $sourceBody, HTTP_Request2_Response $res
    ) {
        $exists = false;
        foreach ($this->callbacks as $callback) {
            if (!$callback instanceof Server\Callback\ILink) {
                continue;
            }

            $exists |= $callback->verifyLinkExists(
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
        $target, $source, $sourceBody, HTTP_Request2_Response $res
    ) {
        foreach ($this->callbacks as $callback) {
            if (!$callback instanceof Server\Callback\IStorage) {
                continue;
            }
            $callback->storePingback(
                $target, $source, $sourceBody, $res
            );
        }
    }
}

?>
