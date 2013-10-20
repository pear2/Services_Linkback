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
namespace PEAR2\Services\Linkback\Response;
use PEAR2\Services\Linkback\States as States;
use HTTP_Request2_Response;

/**
 * Response to a client ping() request.
 *
 * @category Services
 * @package  PEAR2\Services\Linkback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Linkback
 */
class Ping
{
    /**
     * (debug) message that we get on a successful linkback request,
     * or error message in case of an error.
     *
     * @var string
     */
    protected $message;

    /**
     * Fault code that gets set when linkback fails.
     * If the response is not an error, the code is null.
     *
     * @var integer
     */
    protected $code;

    /**
     * HTTP response object, if debugging is enabled in the client
     *
     * @var HTTP_Request2_Response
     */
    protected $response;


    /**
     * Create new instance and set class variables.
     *
     * @param string  $message Response message
     * @param integer $code    Error/status code. NULL for no error.
     */
    public function __construct($message = null, $code = null)
    {
        $this->message = $message;
        $this->code    = $code;
    }

    /**
     * Set a pingback HTTP response object and sets the internal variables.
     *
     * @param object  $res   Pingback HTTP response object
     * @param boolean $debug If debugging is enabled. If true, the response is
     *                       kept in this object
     *
     * @return void
     */
    public function loadFromPingbackResponse(
        HTTP_Request2_Response $res, $debug = false
    ) {
        if ($debug) {
            $this->setResponse($res);
        }

        if (intval($res->getStatus() / 100) != 2) {
            $this->code    = States::HTTP_STATUS;
            $this->message = 'Pingback answer HTTP status code is not 2xx';
            return;
        }

        $types = explode(';', $res->getHeader('content-type'));
        if (count($types) < 1 || trim($types[0]) != 'text/xml') {
            $this->code    = States::CONTENT_TYPE;
            $this->message = 'Pingback answer HTTP content type is not text/xml';
            return;
        }
        $params = xmlrpc_decode($res->getBody());
        if ($params === null) {
            $this->code    = States::MESSAGE_INVALID;
            $this->message = 'Pingback answer is invalid';
            return;
        } else if (is_array($params) && xmlrpc_is_fault($params)) {
            $this->code    = $params['faultCode'];
            $this->message = $params['faultString'];
            return;
        }

        $this->code = null;
        if (is_array($params)) {
            $this->message = $params[0];
        } else {
            //single string
            $this->message = $params;
        }
    }

    /**
     * Uses a webmention HTTP response object to set the internal variables.
     *
     * @param object  $res   Webmention HTTP response object
     * @param boolean $debug If debugging is enabled. If true, the response is
     *                       kept in this object
     *
     * @return void
     */
    public function loadFromWebmentionResponse(
        HTTP_Request2_Response $res, $debug = false
    ) {
        if ($debug) {
            $this->setResponse($res);
        }

        if (intval($res->getStatus() / 100) != 2) {
            $this->code    = States::HTTP_STATUS;
            $this->message = 'Webmention answer HTTP status code is not 2xx but '
                . $res->getStatus();
        } else {
            //no error, all fine
            $this->code = null;
            $this->message = null;
        }
        if (!$res->getHeader('content-type') == 'application/json') {
            return;
        }

        $json = json_decode($res->getBody());
        if ($json === false && $json === null) {
            //broken json
            return;
        }

        if (isset($json->error)) {
            switch ($json->error) {
            case 'source_not_found':
                $this->code = States::SOURCE_URI_NOT_FOUND;
                break;
            case 'target_not_found':
                $this->code = States::TARGET_URI_NOT_FOUND;
                break;
            case 'target_not_supported':
                $this->code = States::PINGBACK_UNSUPPORTED;
                break;
            case 'no_link_found':
                $this->code = States::NO_LINK_IN_SOURCE;
                break;
            case 'already_registered':
                $this->code = States::ALREADY_REGISTERED;
                break;
            }

            if (isset($json->error_description)) {
                $this->message = (string) $json->error_description;
            }
        } else {
            //no error
            if (isset($json->result)) {
                $this->message = (string) $json->result;
            }
        }
    }

    /**
     * Tells you if a response is an error or not
     *
     * @return boolean True if the request failed
     */
    public function isError()
    {
        return $this->code !== null;
    }

    /**
     * Returns the XML-RPC fault code
     *
     * @return integer Error code. NULL when the response is not an error.
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Returns the XML-RPC debug or error message.
     *
     * @return string Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Returns the HTTP response if set
     *
     * @return HTTP_Request2_Response Response object
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Sets the HTTP response object without any parsing.
     * Useful for debugging errors of non-pingback responses.
     *
     * @param HTTP_Request2_Response $res Response object
     *
     * @return void
     *
     * @see setPingbackResponse()
     */
    public function setResponse(HTTP_Request2_Response $res)
    {
        $this->response = $res;
    }
}

?>
