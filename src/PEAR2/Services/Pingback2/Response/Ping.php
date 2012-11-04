<?php
namespace PEAR2\Services\Pingback2;

/**
 * Response to a client ping() request
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

    public function __construct($message = null, $code = null)
    {
        $this->message = $message;
        $this->code    = $code;
    }

    /**
     * Set a HTTP response object and sets the internal variables
     *
     * @param object $res Pingback HTTP response object
     *
     * @return void
     */
    public function setResponse(\HTTP_Request2_Response $res)
    {
        if (intval($res->getStatus() / 100) != 2) {
            $this->code    = self::HTTP_STATUS;
            $this->message = 'HTTP status code is not 2xx';
            return;
        }

        $types = explode(';', $res->getHeader('content-type'));
        if (count($types) < 1 || trim($types[0]) != 'text/xml') {
            $this->code    = self::CONTENT_TYPE;
            $this->message = 'HTTP content type is not text/xml';
            return;
        }

        $rpc = xmlrpc_decode($res->getBody());
        if ($rpc && !xmlrpc_is_fault($rpc)) {
            $this->code    = null;
            $this->message = $rpc;
            return;
        }

        $this->code    = $rpc['faultCode'];
        $this->message = $rpc['faultString'];
        return;

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
}

?>
