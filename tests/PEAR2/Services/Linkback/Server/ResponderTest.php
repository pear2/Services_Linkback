<?php
namespace PEAR2\Services\Linkback\Server;

class ResponderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * separate process is needed for headers
     *
     * @runInSeparateProcess
     * @outputBuffering enabled
     */
    public function testSendOutput()
    {
        $resp = new Responder\Pingback();
        $resp->sendOutput('foo');
        //TODO: check for headers
        $this->expectOutputString('foo');
    }

}

?>
