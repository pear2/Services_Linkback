<?php
namespace PEAR2\Services\Pingback;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \HTTP_Request2_Adapter_Mock
     */
    protected $mock;

    /**
     * @var Client
     */
    protected $client;

    protected static $xmlPingback = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<methodResponse>
 <params>
  <param><value><string>Pingback received and processed</string></value></param>
 </params>
</methodResponse>
XML;

    protected static $htmlWithPingback = <<<HTM
<html>
 <head>
  <title>article</title>
  <link rel="pingback" href="http://example.org/article-pingback-server" />
 </head>
 <body>
  <p>This is an article</p>
 </body>
</html>
HTM;

    protected static $htmlWithoutPingback = <<<HTM
<html>
 <head>
  <title>article</title>
 </head>
 <body>
  <p>This is an article without a pingback server</p>
 </body>
</html>
HTM;



    public function setUp()
    {
        $this->mock = new \HTTP_Request2_Adapter_Mock();
        $req = new \HTTP_Request2();
        $req->setAdapter($this->mock);

        $this->client = new Client();
        $this->client->setRequest($req);
    }

    public function testDiscoverServerGet()
    {
        //HEAD request
        $this->mock->addResponse(
            "HTTP/1.0 200 OK\r\n",
            'http://example.org/article'
        );
        //GET request
        $this->mock->addResponse(
            "HTTP/1.0 200 OK\r\n"
            . "Foo: bar\r\n"
            . "X-Pingback: http://example.org/pingback-server\r\n"
            . "Bar: foo\r\n",
            'http://example.org/article'
        );
        $this->mock->addResponse(
            "HTTP/1.0 200 OK\r\n"
            . "Content-Type: text/xml\r\n"
            . "\r\n"
            . static::$xmlPingback,
            'http://example.org/pingback-server'
        );

        $res = $this->client->send(
            'http://example.org/myblog',
            'http://example.org/article'
        );
        $this->assertFalse($res->isError());
        $this->assertNull($res->getCode());
        $this->assertEquals(
            'Pingback received and processed', $res->getMessage()
        );
    }

    public function testDiscoverServerHead()
    {
        //HEAD request
        $this->mock->addResponse(
            "HTTP/1.0 200 OK\r\n"
            . "Foo: bar\r\n"
            . "X-Pingback: http://example.org/pingback-server\r\n"
            . "Bar: foo\r\n",
            'http://example.org/article'
        );
        //pingback request
        $this->mock->addResponse(
            "HTTP/1.0 200 OK\r\n"
            . "Content-Type: text/xml\r\n"
            . "\r\n"
            . static::$xmlPingback,
            'http://example.org/pingback-server'
        );

        $res = $this->client->send(
            'http://example.org/myblog',
            'http://example.org/article'
        );
        $this->assertFalse($res->isError());
        $this->assertNull($res->getCode());
        $this->assertEquals(
            'Pingback received and processed', $res->getMessage()
        );
    }

    public function testDiscoverServerHeadMethodNotAllowed()
    {
        //HEAD request
        $this->mock->addResponse(
            "HTTP/1.0 405 Method not allowed\r\n",
            'http://example.org/article'
        );
        //GET request
        $this->mock->addResponse(
            "HTTP/1.0 200 OK\r\n"
            . "Foo: bar\r\n"
            . "X-Pingback: http://example.org/pingback-server\r\n"
            . "Bar: foo\r\n",
            'http://example.org/article'
        );
        $this->mock->addResponse(
            "HTTP/1.0 200 OK\r\n"
            . "Content-Type: text/xml\r\n"
            . "\r\n"
            . static::$xmlPingback,
            'http://example.org/pingback-server'
        );

        $res = $this->client->send(
            'http://example.org/myblog',
            'http://example.org/article'
        );
        $this->assertFalse($res->isError());
        $this->assertNull($res->getCode());
        $this->assertEquals(
            'Pingback received and processed', $res->getMessage()
        );
    }

    public function testDiscoverServerTargetUriNotFoundHead()
    {
        $this->mock->addResponse(
            "HTTP/1.0 404 Not Found\r\n",
            'http://example.org/article'
        );
        $res = $this->client->send(
            'http://example.org/myblog',
            'http://example.org/article'
        );
        $this->assertTrue($res->isError());
        $this->assertEquals(States::TARGET_URI_NOT_FOUND, $res->getCode());
        $this->assertEquals('Error fetching target URI', $res->getMessage());
        $this->assertNull($res->getResponse());
    }

    public function testDiscoverServerTargetUriNotFoundHeadDebug()
    {
        $this->mock->addResponse(
            "HTTP/1.0 404 Not Found\r\n",
            'http://example.org/article'
        );
        $this->client->setDebug(true);
        $res = $this->client->send(
            'http://example.org/myblog',
            'http://example.org/article'
        );
        $this->assertTrue($res->isError());
        $this->assertNotNull($res->getResponse());
        $this->assertInstanceOf('\HTTP_Request2_Response', $res->getResponse());
        $this->assertEquals(404, $res->getResponse()->getStatus());
    }

    public function testDiscoverServerTargetUriNotFoundGet()
    {
        $this->mock->addResponse(
            "HTTP/1.0 200 OK\r\n"
            . "\r\n",
            'http://example.org/article'
        );
        $this->mock->addResponse(
            "HTTP/1.0 404 Not Found\r\n",
            'http://example.org/article'
        );

        $res = $this->client->send(
            'http://example.org/myblog',
            'http://example.org/article'
        );
        $this->assertTrue($res->isError());
        $this->assertEquals(States::TARGET_URI_NOT_FOUND, $res->getCode());
        $this->assertEquals('Error fetching target URI', $res->getMessage());
    }


    public function testDiscoverServerTargetUriNotFoundGetDebug()
    {
        $this->mock->addResponse(
            "HTTP/1.0 200 OK\r\n"
            . "\r\n",
            'http://example.org/article'
        );
        $this->mock->addResponse(
            "HTTP/1.0 404 Not Found\r\n",
            'http://example.org/article'
        );

        $this->client->setDebug(true);
        $res = $this->client->send(
            'http://example.org/myblog',
            'http://example.org/article'
        );
        $this->assertTrue($res->isError());
        $this->assertNotNull($res->getResponse());
        $this->assertInstanceOf('\HTTP_Request2_Response', $res->getResponse());
        $this->assertEquals(404, $res->getResponse()->getStatus());
    }

    public function testDiscoverServerHtml()
    {
        $this->mock->addResponse(
            "HTTP/1.0 200 OK\r\n"
            . "\r\n",
            'http://example.org/article'
        );
        $this->mock->addResponse(
            "HTTP/1.0 200 OK\r\n"
            . "\r\n"
            . static::$htmlWithPingback,
            'http://example.org/article'
        );
        $this->mock->addResponse(
            "HTTP/1.0 200 OK\r\n"
            . "Content-Type: text/xml\r\n"
            . "\r\n"
            . static::$xmlPingback,
            'http://example.org/article-pingback-server'
        );

        $res = $this->client->send(
            'http://example.org/myblog',
            'http://example.org/article'
        );
        $this->assertFalse($res->isError());
        $this->assertNull($res->getCode());
        $this->assertEquals('Pingback received and processed', $res->getMessage());
    }

    public function testDiscoverServerHtmlNoServer()
    {
        $this->mock->addResponse(
            "HTTP/1.0 200 OK\r\n"
            . "\r\n",
            'http://example.org/article'
        );
        $this->mock->addResponse(
            "HTTP/1.0 200 OK\r\n"
            . "\r\n"
            . static::$htmlWithoutPingback,
            'http://example.org/article'
        );

        $res = $this->client->send(
            'http://example.org/myblog',
            'http://example.org/article'
        );
        $this->assertTrue($res->isError());
        $this->assertEquals(States::PINGBACK_UNSUPPORTED, $res->getCode());
        $this->assertEquals(
            'No pingback server found for URI', $res->getMessage()
        );
    }

    public function testGetRequestEmpty()
    {
        $cl = new Client();
        $req = $cl->getRequest();
        $this->assertInstanceOf('\HTTP_Request2', $req);
    }

    public function testGetRequestSet()
    {
        $cl = new Client();
        $req = $cl->getRequest();
        $this->assertInstanceOf('\HTTP_Request2', $req);

        $req2 = $cl->getRequest();
        $this->assertSame($req, $req2);
    }
}
?>
