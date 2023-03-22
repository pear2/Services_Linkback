<?php
namespace PEAR2\Services\Linkback\Server\Responder;

class WebmentionTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        unset($_SERVER['HTTP_ACCEPT']);
        $this->responder = new Webmention\Mock();
    }

    public function tearDown(): void
    {
        unset($_SERVER['HTTP_ACCEPT']);
    }

    public function testSendNoAccept()
    {
        $this->responder->send('All fine');
        $this->assertContains(
            'HTTP/1.0 202 Accepted',
            $this->responder->header
        );
        $this->assertContains(
            'Content-type: text/plain; charset=utf-8',
            $this->responder->header
        );
        $this->assertStringContainsString('All fine', $this->responder->content);
    }

    public function testSendHtml()
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/xhtml+xml';
        $this->responder->send('Pingback received and processed');

        $this->assertContains(
            'HTTP/1.0 202 Accepted',
            $this->responder->header
        );
        $this->assertContains(
            'Content-type: application/xhtml+xml; charset=utf-8',
            $this->responder->header
        );
        $this->assertStringContainsString(
            'Pingback received and processed',
            $this->responder->content
        );
    }

    public function testSendErrorHtml()
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        $this->responder->send(
            array('faultCode' => 32, 'faultString' => 'nope')
        );

        $this->assertContains(
            'HTTP/1.0 400 Bad Request',
            $this->responder->header
        );
        $this->assertContains(
            'Content-type: text/html; charset=utf-8',
            $this->responder->header
        );
        $this->assertStringContainsString(
            'Webmention: target not found',
            $this->responder->content
        );
        $this->assertStringContainsString(
            'nope',
            $this->responder->content
        );
    }

    public function testSendErrorText()
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/plain';
        $this->responder->send(
            array('faultCode' => 32, 'faultString' => 'nope')
        );

        $this->assertContains(
            'HTTP/1.0 400 Bad Request',
            $this->responder->header
        );
        $this->assertContains(
            'Content-type: text/plain; charset=utf-8',
            $this->responder->header
        );
        $this->assertEquals(
            "Webmention error #32: target not found\n"
            . "nope\n",
            $this->responder->content
        );
    }

    public function testSendErrorUnknownCode()
    {
        $this->responder->send(
            array('faultCode' => -123, 'faultString' => 'nope')
        );

        $this->assertContains(
            'HTTP/1.0 400 Bad Request',
            $this->responder->header
        );
        $this->assertContains(
            'Content-type: text/plain; charset=utf-8',
            $this->responder->header
        );
        $this->assertEquals(
            "Webmention error #-123: unknown error (-123)\n"
            . "nope\n",
            $this->responder->content
        );
    }
}
?>
