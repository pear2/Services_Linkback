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
namespace PEAR2\Services\Linkback\Server\Responder;
use PEAR2\Services\Linkback\States;

/**
 * Sends HTTP headers and a webmention result back to the client.
 *
 * @category Services
 * @package  PEAR2\Services\Linkback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Linkback
 */
class Webmention extends Base
{
    protected static $htmlTemplate = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
 <head>
  <title>Webmention: %TITLE%</title>
 </head>
 <body>
  <h1>Webmention: %TITLE%</h1>
  <p>%MESSAGE%</p>
 </body>
</html>

XML;

    protected static $arCodeNames = array(
        States::ACCESS_DENIED        => 'access_denied',
        States::ALREADY_REGISTERED   => 'already_registered',
        States::INVALID_URI          => 'invalid_uri',
        States::NO_LINK_IN_SOURCE    => 'no_link_found',
        States::PINGBACK_UNSUPPORTED => 'target_not_supported',
        States::SOURCE_NOT_LOADED    => 'source_not_loaded',
        States::SOURCE_URI_NOT_FOUND => 'source_not_found',
        States::SPAM                 => 'spam',
        States::TARGET_URI_NOT_FOUND => 'target_not_found',
    );

    /**
     * Send the given response back to the client.
     * Sends the correct headers.
     *
     * @param mixed $res Array of return values ('faultCode' and 'faultString'),
     *                   or single string if all is fine
     *
     * @return void
     */
    public function send($res)
    {
        if (is_array($res)) {
            $this->sendHeader('HTTP/1.0 400 Bad Request');
        } else {
            $this->sendHeader('HTTP/1.0 202 Accepted');
        }

        $http = new \HTTP2();
        $supportedTypes = array(
            'application/xhtml+xml', 'text/html',
            'application/json',
            'text/plain'
        );

        $type = $http->negotiateMimeType($supportedTypes, 'text/plain');
        $this->sendHeader('Content-type: ' . $type . '; charset=utf-8');

        $outputType = $type;
        if ($outputType == 'application/xhtml+xml') {
            $outputType = 'text/html';
        }
        if (is_array($res)) {
            $this->sendError($outputType, $res['faultCode'], $res['faultString']);
        } else {
            $this->sendOk($outputType, $res);
        }
    }

    /**
     * Construct and output an error response
     *
     * @param string  $type    Output MIME type
     * @param integer $nCode   Error code (see States class)
     * @param string  $message Error message
     *
     * @return void
     */
    protected function sendError($type, $nCode, $message)
    {
        if ($type == 'application/json') {
            $this->sendOutput(
                json_encode(
                    (object) array(
                        'error' => $this->getCodeName($nCode),
                        'error_description' => $message
                    )
                )
            );
        } else if ($type == 'text/html') {
            $this->sendOutput(
                str_replace(
                    array('%TITLE%', '%MESSAGE%'),
                    array(
                        str_replace('_', ' ', $this->getCodeName($nCode)),
                        $message
                    ),
                    self::$htmlTemplate
                )
            );
        } else {
            $this->sendOutput(
                'Webmention error #' . $nCode . ': '
                . str_replace('_', ' ', $this->getCodeName($nCode)) . "\n"
                . $message . "\n"
            );
        }
    }

    /**
     * Send an success message
     *
     * @param string $type    Output MIME type
     * @param string $message Success message text
     *
     * @return void
     */
    protected function sendOk($type, $message)
    {
        if ($type == 'application/json') {
            $this->sendOutput(
                json_encode(
                    (object) array(
                        'result' => $message
                    )
                )
            );
        } else if ($type == 'text/html') {
            $this->sendOutput(
                str_replace(
                    array('%TITLE%', '%MESSAGE%'),
                    array('All fine', $message),
                    self::$htmlTemplate
                )
            );
        } else {
            $this->sendOutput(
                'OK. ' . $message . "\n"
            );
        }
    }

    /**
     * Convert a numneric error code into a name
     *
     * @param integer $nCode Error code (see States class)
     *
     * @return string Error key
     */
    protected function getCodeName($nCode)
    {
        if (isset(self::$arCodeNames[$nCode])) {
            return self::$arCodeNames[$nCode];
        }
        return 'unknown_error (' . $nCode . ')';
    }
}
?>
