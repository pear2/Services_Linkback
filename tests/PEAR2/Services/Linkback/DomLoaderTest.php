<?php
namespace PEAR2\Services\Linkback;

class DomLoaderTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->dl = new DomLoader();
    }

    public function testLoadHtml()
    {
        $res = \HTTP_Request2_Adapter_Mock::createResponseFromString(
            "HTTP/1.0 200 OK\n"
            . "Content-type: text/html\n"
            . "\n"
            . '<html><head><title></title></head><body></body></html>'
        );
        $doc = $this->dl->load($res->getBody(), $res);
        $this->assertInstanceOf('DomDocument', $doc);
    }

    public function testLoadHtmlBroken()
    {
        $res = \HTTP_Request2_Adapter_Mock::createResponseFromString(
            "HTTP/1.0 200 OK\n"
            . "Content-type: text/html\n"
            . "\n"
            . '<html><head><title>a&b</title></head><body></body></html>'
        );
        $doc = $this->dl->load($res->getBody(), $res);
        $this->assertInstanceOf('DomDocument', $doc);
        //we should not get any PHP warnings here
    }
}
?>
