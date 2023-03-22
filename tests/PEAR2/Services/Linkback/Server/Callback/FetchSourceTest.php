<?php
namespace PEAR2\Services\Linkback\Server\Callback;

class FetchSourceTest extends \PHPUnit\Framework\TestCase
{
    public function testFetchSource()
    {
        $mock = new \HTTP_Request2_Adapter_Mock();
        $mock->addResponse(
            "HTTP/1.1 200 OK\r\n" .
            "Content-Type: text/plain; charset=iso-8859-1\r\n" .
            "\r\n" .
            "This is a string",
            'http://www.example.org/'
        );
        $req = new \HTTP_Request2();
        $req->setAdapter($mock);

        $fsc = new FetchSource();
        $fsc->setRequest($req);

        $res = $fsc->fetchSource('http://www.example.org/');
        $this->assertInstanceOf('\HTTP_Request2_Response', $res);
        $this->assertEquals('This is a string', $res->getBody());
    }
}
?>
