<?php
/**
 * This file is part of the PEAR2\Services\Pingback2 package.
 *
 * PHP version 5
 *
 * @category Services
 * @package  PEAR2\Services\Pingback2
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Pingback2
 */
namespace PEAR2\Services\Pingback2;

/**
 * Response to a client ping() request.
 *
 * @category Services
 * @package  PEAR2\Services\Pingback2
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Pingback2
 */
class Response_Ping
{
    /**
     * (debug) message that we get on a successful pingback request,
     * or error message in case of an error.
     *
     * @var string
     */
    protected $message;

    /**
     * Fault code that gets set when pingback fails.
     * If the response is not an error, the code is null.
     *
     * @var integer
     */
    protected $code;

    /**
     * HTTP response object, if debugging is enabled in the client
     *
     * @var \HTTP_Request2_Response
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
     * Set a HTTP response object and sets the internal variables
     *
     * @param object  $res   Pingback HTTP response object
     * @param boolean $debug If debugging is enabled. If true, the response is
     *                       kept in this object
     *
     * @return void
     */
    public function setPingbackResponse(
        \HTTP_Request2_Response $res, $debug = false
    ) {
        if ($debug) {
            $this->response = $res;
        }

        if (intval($res->getStatus() / 100) != 2) {
            $this->code    = States::HTTP_STATUS;
            $this->message = 'HTTP status code is not 2xx';
            return;
        }

        $types = explode(';', $res->getHeader('content-type'));
        if (count($types) < 1 || trim($types[0]) != 'text/xml') {
            $this->code    = States::CONTENT_TYPE;
            $this->message = 'HTTP content type is not text/xml';
            return;
        }
        $params = xmlrpc_decode($res->getBody());
        if ($params === null) {
            $this->code    = States::MESSAGE_INVALID;
            $this->message = 'Pingback response is invalid';
            return;
        } else if (is_array($params) && xmlrpc_is_fault($params)) {
            $this->code    = $params['faultCode'];
            $this->message = $params['faultString'];
            return;
        }

        $this->code    = null;
        if (is_array($params)) {
            $this->message = $params[0];
        } else {
            //single string
            $this->message = $params;
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
     * @return \HTTP_Request2_Response Response object
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Sets the HTTP response object
     *
     * @param \HTTP_Request2_Response $res Response object
     *
     * @return void
     */
    public function setResponse(\HTTP_Request2_Response $res)
    {
        $this->response = $res;
    }
}

?>
