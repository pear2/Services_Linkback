<?php
namespace PEAR2\Services\Pingback;

class ServerTest extends \PHPUnit_Framework_TestCase
{
    protected $server;
    protected $streamVarRegistered = false;

    public function setUp()
    {
        $this->server = new Server();
        $this->server->setResponder(new Server\Responder\Mock());
    }

    public function tearDown()
    {
        if ($this->streamVarRegistered) {
            stream_wrapper_unregister('var');
            $this->streamVarRegistered = false;
        }
    }

    protected function setXml($xml)
    {
        stream_wrapper_register('var', 'Stream_Var');
        $GLOBALS['unittest-xml'] = $xml;
        $this->streamVarRegistered = true;
    }

    public function testRunNotEnoughParameters()
    {
        $this->setXml(
            <<<XML
<?xml version="1.0" encoding="utf-8"?>
<methodCall>
 <methodName>pingback.ping</methodName>
 <params>
  <param><value><string>http://127.0.0.1/source</string></value></param>
 </params>
</methodCall>
XML
        );
        $this->server->setInputFile('var://GLOBALS/unittest-xml');
        $this->server->run();
        $this->assertContains(
            'faultCode',
            $this->server->getResponder()->xml
        );
        $this->assertContains(
            '2 parameters required',
            $this->server->getResponder()->xml
        );
    }

    public function testRunWrongMethod()
    {
        $this->setXml(
            <<<XML
<?xml version="1.0" encoding="utf-8"?>
<methodCall>
 <methodName>pingback.foo</methodName>
 <params>
  <param><value><string>http://127.0.0.1/source</string></value></param>
  <param><value><string>http://127.0.0.1/target</string></value></param>
 </params>
</methodCall>
XML
        );
        $this->server->setInputFile('var://GLOBALS/unittest-xml');
        $this->server->run();
        $this->assertContains(
            'faultCode',
            $this->server->getResponder()->xml
        );
        $this->assertContains(
            'method not found',
            $this->server->getResponder()->xml
        );
    }

    public function testRunOk()
    {
        $this->setXml(
            <<<XML
<?xml version="1.0" encoding="utf-8"?>
<methodCall>
 <methodName>pingback.ping</methodName>
 <params>
  <param><value><string>http://127.0.0.1/source</string></value></param>
  <param><value><string>http://127.0.0.1/target</string></value></param>
 </params>
</methodCall>
XML
        );
        $mockSource = new Server\Callback\FetchSource\Mock();
        $mockSource->setResponse(new \HTTP_Request2_Response('HTTP/1.0 200 OK'));
        $mockLink = new Server\Callback\LinkExists\Mock();
        $mockLink->setLinkExists(true);

        $this->server->setCallbacks(array($mockSource, $mockLink));
        $this->server->setInputFile('var://GLOBALS/unittest-xml');
        $this->server->run();
        $this->assertXmlStringEqualsXmlString(
            <<<XML
<?xml version="1.0" encoding="utf-8"?>
<methodResponse>
 <params>
  <param><value><string>Pingback received and processed</string></value></param>
 </params>
</methodResponse>
XML
            ,
            $this->server->getResponder()->xml
        );
    }

    public function testHandlePingbackPingSourceInvalid()
    {
        $res = $this->server->handlePingbackPing(
            'pingback.ping',
            array('/path/to/source', 'http://127.0.0.1/target')
        );
        $this->assertInternalType('array', $res);
        $this->assertEquals(States::INVALID_URI, $res['faultCode']);
        $this->assertEquals(
            'Source URI invalid (not absolute, not http/https)',
            $res['faultString']
        );
    }

    public function testHandlePingbackPingTargetInvalid()
    {
        $res = $this->server->handlePingbackPing(
            'pingback.ping',
            array('http://127.0.0.1/source', '/path/to/target')
        );
        $this->assertInternalType('array', $res);
        $this->assertEquals(States::INVALID_URI, $res['faultCode']);
        $this->assertEquals(
            'Target URI invalid (not absolute, not http/https)',
            $res['faultString']
        );
    }

    public function testHandlePingbackPingTargetDoesNotExist()
    {
        $mockLink = new Server\Callback\TargetExists\Mock();
        $mockLink->setTargetExists(false);
        $this->server->addCallback($mockLink);

        $res = $this->server->handlePingbackPing(
            'pingback.ping',
            array('http://127.0.0.1/source', 'http://127.0.0.1/target')
        );
        $this->assertInternalType('array', $res);
        $this->assertEquals(States::TARGET_URI_NOT_FOUND, $res['faultCode']);
        $this->assertEquals('Target URI does not exist', $res['faultString']);
    }

    public function testHandlePingbackPingInvalidSourceObject()
    {
        $mockSource = new Server\Callback\FetchSource\Mock();
        $mockSource->setResponse(null);
        $this->server->setCallbacks(array($mockSource));

        $res = $this->server->handlePingbackPing(
            'pingback.ping',
            array('http://127.0.0.1/source', 'http://127.0.0.1/target')
        );
        $this->assertInternalType('array', $res);
        $this->assertEquals(States::SOURCE_NOT_LOADED, $res['faultCode']);
        $this->assertEquals('Source document not loaded', $res['faultString']);
    }

    public function testHandlePingbackPingSourceNotFound()
    {
        $mockSource = new Server\Callback\FetchSource\Mock();
        $mockSource->setResponse(
            new \HTTP_Request2_Response('HTTP/1.0 404 Not Found')
        );
        $this->server->setCallbacks(array($mockSource));

        $res = $this->server->handlePingbackPing(
            'pingback.ping',
            array('http://127.0.0.1/source', 'http://127.0.0.1/target')
        );
        $this->assertInternalType('array', $res);
        $this->assertEquals(States::SOURCE_URI_NOT_FOUND, $res['faultCode']);
        $this->assertEquals('Source URI does not exist', $res['faultString']);
    }

    public function testHandlePingbackPingLinkDoesNotExist()
    {
        $mockSource = new Server\Callback\FetchSource\Mock();
        $mockSource->setResponse(
            new \HTTP_Request2_Response('HTTP/1.0 200 OK')
        );
        $mockStorage = 
        $this->server->setCallbacks(array($mockSource));

        $res = $this->server->handlePingbackPing(
            'pingback.ping',
            array('http://127.0.0.1/source', 'http://127.0.0.1/target')
        );
        $this->assertInternalType('array', $res);
        $this->assertEquals(States::NO_LINK_IN_SOURCE, $res['faultCode']);
        $this->assertEquals(
            'Source URI does not contain a link to the target URI,'
            . ' and thus cannot be used as a source',
            $res['faultString']
        );
    }

    public function testHandlePingbackPingStorePing()
    {
        $mockSource = new Server\Callback\FetchSource\Mock();
        $httpRes = new \HTTP_Request2_Response('HTTP/1.0 200 OK');
        $mockSource->setResponse($httpRes);
        $mockLinkExists = new Server\Callback\LinkExists\Mock();
        $mockLinkExists->setLinkExists(true);
        $storage = new Server\Callback\StorePingback\Object();
        $this->server->setCallbacks(array($mockSource, $mockLinkExists, $storage));

        $res = $this->server->handlePingbackPing(
            'pingback.ping',
            array('http://127.0.0.1/source', 'http://127.0.0.1/target')
        );
        $this->assertInternalType('string', $res);
        $this->assertEquals(
            'Pingback received and processed',
            $res
        );
        $this->assertSame($httpRes, $storage->res);
    }

    public function testHandlePingbackPingCallbackThrowsException()
    {
        $mockLink = new Server\Callback\TargetExists\Mock();
        $mockLink->setException(
            new \Exception('This is an exception', 12345)
        );
        $this->server->addCallback($mockLink);

        $res = $this->server->handlePingbackPing(
            'pingback.ping',
            array('http://127.0.0.1/source', 'http://127.0.0.1/target')
        );
        $this->assertInternalType('array', $res);
        $this->assertEquals(12345, $res['faultCode']);
        $this->assertEquals('This is an exception', $res['faultString']);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Callback object needs to implement one of the pingback server callback interfaces
     * @expectedExceptionCode 150
     */
    public function testVerifyCallbacksThrowsException()
    {
        $this->server->verifyCallbacks(array(new \stdClass()));
    }

    public function testGetResponderNew()
    {
        $server = new Server();
        $resp = $server->getResponder();
        $this->assertInstanceOf(
            'PEAR2\Services\Pingback\Server\Responder', $resp
        );
    }
}
?>
