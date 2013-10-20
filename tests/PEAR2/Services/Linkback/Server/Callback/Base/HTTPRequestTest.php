<?php
namespace PEAR2\Services\Linkback\Server\Callback\Base;

class BaseTest extends \PHPUnit_Framework_TestCase
{
    public function testGetRequestNull()
    {
        $base = new \PEAR2\Services\Linkback\Server\Callback\FetchSource();
        $req = $base->getRequest();
        $this->assertInstanceOf('\HTTP_Request2', $req);
    }

    public function testGetRequestSet()
    {
        $base = new \PEAR2\Services\Linkback\Server\Callback\FetchSource();
        $req = $base->getRequest();
        $this->assertInstanceOf('\HTTP_Request2', $req);

        $req2 = $base->getRequest();
        $this->assertSame($req, $req2);
    }
}

?>