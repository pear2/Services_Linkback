<?php
namespace PEAR2\Services\Pingback\Response;
use PEAR2\Services\Pingback\States;

class PingTest extends \PHPUnit_Framework_TestCase
{
    protected static $xmlOk = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<methodResponse>
 <params>
  <param><value><string>Pingback received and processed</string></value></param>
 </params>
</methodResponse>
XML;

    protected static $xmlOkMultiple = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<methodResponse>
 <params>
  <param><value><string>Pingback received and processed</string></value></param>
  <param><value><string>See you later</string></value></param>
 </params>
</methodResponse>
XML;

    protected static $xmlOkArray = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<methodResponse>
 <params>
  <array>
   <data>
    <param><value><string>Pingback received and processed</string></value></param>
    <param><value><string>Hey, you are cool</string></value></param>
   </data>
  </array>
 </params>
</methodResponse>
XML;

    protected static $xmlError = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<methodResponse>
 <fault>
  <value>
   <struct>
    <member>
     <name>faultCode</name>
     <value><int>32123</int></value>
    </member>
    <member>
     <name>faultString</name>
     <value><string>No, I do not want this</string></value>
    </member>
   </struct>
  </value>
 </fault>
</methodResponse>
XML;


    public function setUp()
    {
        $this->res = new Ping();
    }

    public function testSetPingbackResponseOk()
    {
        $httpres = \HTTP_Request2_Adapter_Mock::createResponseFromString(
            "HTTP/1.0 200 OK\r\n"
            . "Content-Type: text/xml\r\n"
            . "\r\n"
            . static::$xmlOk
        );
        $this->res->setPingbackResponse($httpres);
        $this->assertFalse($this->res->isError());
        $this->assertNull($this->res->getCode());
        $this->assertEquals(
            'Pingback received and processed', $this->res->getMessage()
        );
    }

    public function testSetPingbackResponseMultipleMessages()
    {
        $httpres = \HTTP_Request2_Adapter_Mock::createResponseFromString(
            "HTTP/1.0 200 OK\r\n"
            . "Content-Type: text/xml\r\n"
            . "\r\n"
            . static::$xmlOkMultiple
        );
        $this->res->setPingbackResponse($httpres);
        $this->assertFalse($this->res->isError());
        $this->assertNull($this->res->getCode());
        //this is unfortunately not something we can change.
        //I'd like to take the first element, but xmlrpc_decode
        //gives us the first string only
        $this->assertEquals(
            'See you later', $this->res->getMessage()
        );
    }

    public function testSetPingbackResponseArray()
    {
        $httpres = \HTTP_Request2_Adapter_Mock::createResponseFromString(
            "HTTP/1.0 200 OK\r\n"
            . "Content-Type: text/xml\r\n"
            . "\r\n"
            . static::$xmlOkArray
        );
        $this->res->setPingbackResponse($httpres);
        $this->assertFalse($this->res->isError());
        $this->assertNull($this->res->getCode());
        $this->assertEquals(
            'Pingback received and processed', $this->res->getMessage()
        );
        $this->assertNull($this->res->getResponse());
    }

    public function testSetPingbackResponseDebug()
    {
        $httpres = \HTTP_Request2_Adapter_Mock::createResponseFromString(
            "HTTP/1.0 200 OK\r\n"
            . "Content-Type: text/xml\r\n"
            . "\r\n"
            . static::$xmlOk
        );
        $this->res->setPingbackResponse($httpres, true);
        $this->assertFalse($this->res->isError());
        $this->assertNull($this->res->getCode());
        $this->assertEquals(
            'Pingback received and processed', $this->res->getMessage()
        );
        $this->assertSame($httpres, $this->res->getResponse());
    }

    public function testSetPingbackResponseNo200()
    {
        $httpres = \HTTP_Request2_Adapter_Mock::createResponseFromString(
            "HTTP/1.0 404 Not Found\r\n"
            . "Content-Type: text/xml\r\n"
            . "\r\n"
            . static::$xmlOk
        );
        $this->res->setPingbackResponse($httpres);
        $this->assertTrue($this->res->isError());
        $this->assertEquals(States::HTTP_STATUS, $this->res->getCode());
        $this->assertEquals(
            'Pingback answer HTTP status code is not 2xx',
            $this->res->getMessage()
        );
    }

    public function testSetPingbackResponseWrongContentType()
    {
        $httpres = \HTTP_Request2_Adapter_Mock::createResponseFromString(
            "HTTP/1.0 200 OK\r\n"
            . "Content-Type: text/plain\r\n"
            . "\r\n"
            . static::$xmlOk
        );
        $this->res->setPingbackResponse($httpres);
        $this->assertTrue($this->res->isError());
        $this->assertEquals(States::CONTENT_TYPE, $this->res->getCode());
        $this->assertEquals(
            'Pingback answer HTTP content type is not text/xml',
            $this->res->getMessage()
        );
    }

    public function testSetPingbackResponseInvalidXmlRpc()
    {
        $httpres = \HTTP_Request2_Adapter_Mock::createResponseFromString(
            "HTTP/1.0 200 OK\r\n"
            . "Content-Type: text/xml\r\n"
            . "\r\n"
            . "<?x"
        );
        $this->res->setPingbackResponse($httpres);
        $this->assertTrue($this->res->isError());
        $this->assertEquals(States::MESSAGE_INVALID, $this->res->getCode());
        $this->assertEquals(
            'Pingback answer is invalid',
            $this->res->getMessage()
        );
    }

    public function testSetPingbackResponseError()
    {
        $httpres = \HTTP_Request2_Adapter_Mock::createResponseFromString(
            "HTTP/1.0 200 OK\r\n"
            . "Content-Type: text/xml\r\n"
            . "\r\n"
            . static::$xmlError
        );
        $this->res->setPingbackResponse($httpres);
        $this->assertTrue($this->res->isError());
        $this->assertEquals(32123, $this->res->getCode());
        $this->assertEquals('No, I do not want this', $this->res->getMessage());
    }

}

?>
