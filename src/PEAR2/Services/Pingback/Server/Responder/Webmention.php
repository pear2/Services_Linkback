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
namespace PEAR2\Services\Pingback\Server\Responder;
use PEAR2\Services\Pingback\States;

/**
 * Sends HTTP headers and a webmention result back to the client.
 *
 * @category Services
 * @package  PEAR2\Services\Pingback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Pingback
 */
class Webmention
{
    protected static $htmlTemplate = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
            header('HTTP/1.0 400 Bad Request');
        } else {
            header('HTTP/1.0 202 Accepted');
        }

        $http = new \HTTP2();
        $supportedTypes = array(
            'application/xhtml+xml', 'text/html',
            'application/json'
        );

        $type = $http->negotiateMimeType($supportedTypes, false);
        if ($type === false) {
            header('HTTP/1.1 406 Not Acceptable');
            echo "You don't want any of the content types I have to offer\n";
            exit();
        }
        header('Content-type: ' . $type . '; charset=utf-8');

        $json = $type == 'application/json';
        if (is_array($res)) {
            $this->sendError($json, $res['faultCode'], $res['faultString']);
        } else {
            $this->sendOk($json, $res);
        }
    }

    protected function sendError($json, $nCode, $message)
    {
        if ($json) {
            echo json_encode(
                (object) array(
                    'error' => $this->getCodeName($nCode),
                    'error_description' => $message
                )
            );
        } else {
            echo str_replace(
                array('%TITLE%', '%MESSAGE%'),
                array(
                    str_replace('_', ' ', $this->getCodeName($nCode)),
                    $message
                ),
                self::$htmlTemplate
            );
        }
    }

    protected function sendOk($json, $message)
    {
        if ($json) {
            echo json_encode(
                (object) array(
                    'result' => $message
                )
            );
        } else {
            echo str_replace(
                array('%TITLE%', '%MESSAGE%'),
                array('All fine', $message),
                self::$htmlTemplate
            );
        }
    }

    protected function getCodeName($nCode)
    {
        if (isset(self::$arCodeNames[$nCode])) {
            return self::$arCodeNames[$nCode];
        }
        return 'unknown_error (' . $nCode . ')';
    }
}

?>
