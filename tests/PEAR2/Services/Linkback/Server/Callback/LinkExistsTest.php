<?php
namespace PEAR2\Services\Linkback\Server\Callback;

class LinkExistsTest extends \PHPUnit\Framework\TestCase
{
    public function testVerifyLinkExists()
    {
        $lec = new LinkExists();
        $html = <<<HTM
<html>
 <head>
  <title>Test</title>
 </head>
 <body>
  <p>
   This is a test with a <a href="http://example.org/foo">foo</a> link
 </body>
</html>
HTM;
        $this->assertTrue(
            $lec->verifyLinkExists(
                'http://example.org/foo',
                'http://source.example.org/',
                $html,
                new \HTTP_Request2_Response(
                    'HTTP/1.0 200 OK'
                )
            )
        );
    }

    public function testVerifyLinkExistsNot()
    {
        $lec = new LinkExists();
        $html = <<<HTM
<html>
 <head>
  <title>Test</title>
 </head>
 <body>
  <p>
   This is a test with a <a href="http://example.org/foo">foo</a> link
 </body>
</html>
HTM;
        $this->assertFalse(
            $lec->verifyLinkExists(
                'http://example.org/bar',
                'http://source.example.org/',
                $html,
                new \HTTP_Request2_Response(
                    'HTTP/1.0 200 OK'
                )
            )
        );
    }

    public function testVerifyLinkExistsNotPartial()
    {
        $lec = new LinkExists();
        $html = <<<HTM
<html>
 <head>
  <title>Test</title>
 </head>
 <body>
  <p>
   This is a test with a <a href="http://example.org/foo">foo</a> link
 </body>
</html>
HTM;
        $this->assertFalse(
            $lec->verifyLinkExists(
                'http://example.org/',
                'http://source.example.org/',
                $html,
                new \HTTP_Request2_Response(
                    'HTTP/1.0 200 OK'
                )
            )
        );
    }

    public function testVerifyLinkExistsNotContent()
    {
        $lec = new LinkExists();
        $html = <<<HTM
<html>
 <head>
  <title>Test</title>
 </head>
 <body>
  <p>
   This is a test with a
   <a href="http://example.org/bar">http://example.org/foo</a> link
 </body>
</html>
HTM;
        $this->assertFalse(
            $lec->verifyLinkExists(
                'http://example.org/foo',
                'http://source.example.org/',
                $html,
                new \HTTP_Request2_Response(
                    'HTTP/1.0 200 OK'
                )
            )
        );
    }
}
?>
