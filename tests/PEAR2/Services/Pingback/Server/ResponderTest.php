<?php
namespace PEAR2\Services\Pingback\Server;

class ResponderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * separate process is needed for headers
     *
     * @runInSeparateProcess
     * @outputBuffering enabled
     */
    public function testSend()
    {
        $resp = new Responder();
        $resp->send('foo');
        //TODO: check for headers
        $this->expectOutputString('foo');
    }

}

?>