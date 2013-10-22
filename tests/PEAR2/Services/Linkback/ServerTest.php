<?php
namespace PEAR2\Services\Linkback;

class ServerTest extends \PHPUnit_Framework_TestCase
{
    protected $server;
    protected $streamVarRegistered = false;

    public function setUp()
    {
        $this->server = new Server();
        $this->server->setPingbackResponder(new Server\Responder\Mock());
        $this->server->setWebmentionResponder(new Server\Responder\Webmention\Mock());
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

    /**
     * @runInSeparateProcess
     */
    public function testRunPingbackNotEnoughParameters()
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
            $this->server->getPingbackResponder()->content
        );
        $this->assertContains(
            '2 parameters required',
            $this->server->getPingbackResponder()->content
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testRunPingbackWrongMethod()
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
            $this->server->getPingbackResponder()->content
        );
        $this->assertContains(
            'method not found',
            $this->server->getPingbackResponder()->content
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testRunPingbackOk()
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
            $this->server->getPingbackResponder()->content
        );
    }

    public function testRunUnknownFormat()
    {
        $this->server->run();
        $res = $this->server->getPingbackResponder();
        $this->assertContains(
            'HTTP/1.0 400 Bad Request', $res->header
        );
    }

    public function testRunWebmentionOkJson()
    {
        $_POST['source'] = 'http://127.0.0.1/source';
        $_POST['target'] = 'http://127.0.0.1/target';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        $mockSource = new Server\Callback\FetchSource\Mock();
        $mockSource->setResponse(new \HTTP_Request2_Response('HTTP/1.0 200 OK'));
        $mockLink = new Server\Callback\LinkExists\Mock();
        $mockLink->setLinkExists(true);

        $this->server->setCallbacks(array($mockSource, $mockLink));
        $this->server->run();

        $resp = $this->server->getWebmentionResponder();
        $this->assertContains(
            'Content-type: application/json; charset=utf-8',
            $resp->header
        );
        $this->assertEquals(
            (object) array(
                'result' => 'Pingback received and processed'
            ),
            json_decode($resp->content)
        );
    }

    public function testRunWebmentionErrorNoSourceJson()
    {
        $_POST['source'] = 'http://127.0.0.1/source';
        $_POST['target'] = 'http://127.0.0.1/target';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        $mockSource = new Server\Callback\FetchSource\Mock();
        $mockSource->setResponse(new \HTTP_Request2_Response('HTTP/1.0 404 Not Found'));

        $this->server->setCallbacks(array($mockSource));
        $this->server->run();

        $resp = $this->server->getWebmentionResponder();
        $this->assertContains(
            'HTTP/1.0 400 Bad Request',
            $resp->header
        );
        $this->assertContains(
            'Content-type: application/json; charset=utf-8',
            $resp->header
        );
        $this->assertEquals(
            (object) array(
                'error' => 'source_not_found',
                'error_description' => 'Source URI does not exist'
            ),
            json_decode($resp->content)
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
        $storage = new Server\Callback\StoreLinkback\Object();
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
     * @expectedExceptionMessage Callback object needs to implement one of the linkback server callback interfaces
     * @expectedExceptionCode 150
     */
    public function testVerifyCallbacksThrowsException()
    {
        $this->server->verifyCallbacks(array(new \stdClass()));
    }

    public function testGetPingbackResponderNew()
    {
        $server = new Server();
        $resp = $server->getPingbackResponder();
        $this->assertInstanceOf(
            'PEAR2\Services\Linkback\Server\Responder\Pingback', $resp
        );
    }

    public function testGetWebmentionResponderNew()
    {
        $server = new Server();
        $resp = $server->getWebmentionResponder();
        $this->assertInstanceOf(
            'PEAR2\Services\Linkback\Server\Responder\Webmention', $resp
        );
    }
}
?>
