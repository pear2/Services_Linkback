<?php
namespace PEAR2\Services\Linkback;

/**
 * @group online
 * @large
 */
class ClientOnlineTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        $this->client = new Client();
        $req = $this->client->getRequest();
        $req->setConfig(
            array(
                'ssl_verify_peer' => false,
                'ssl_verify_host' => false
            )
        );
        $this->client->setRequestTemplate($req);
    }

    /**
     * Check the client against https://webmention.rocks/
     *
     * @param string $url      URL to extract the webmention endpoint from
     * @param string $endpoint Expected endpoint URL
     *
     * @return void
     */
    public function checkDiscovery($url, $endpoint)
    {
        $res = $this->client->discoverServer($url);
        $this->assertInstanceOf('PEAR2\Services\Linkback\Server\Info', $res);
        $this->assertEquals($endpoint, $res->uri);
        $this->assertEquals('webmention', $res->type);
    }

    public function testWebmentionRocks1()
    {
        $this->checkDiscovery(
            'https://webmention.rocks/test/1',
            'https://webmention.rocks/test/1/webmention?head=true'
        );
    }

    public function testWebmentionRocks2()
    {
        $this->checkDiscovery(
            'https://webmention.rocks/test/2',
            'https://webmention.rocks/test/2/webmention?head=true'
        );
    }

    public function testWebmentionRocks3()
    {
        $this->checkDiscovery(
            'https://webmention.rocks/test/3',
            'https://webmention.rocks/test/3/webmention'
        );
    }

    public function testWebmentionRocks4()
    {
        $this->checkDiscovery(
            'https://webmention.rocks/test/4',
            'https://webmention.rocks/test/4/webmention'
        );
    }

    public function testWebmentionRocks5()
    {
        $this->checkDiscovery(
            'https://webmention.rocks/test/5',
            'https://webmention.rocks/test/5/webmention'
        );
    }

    public function testWebmentionRocks6()
    {
        $this->checkDiscovery(
            'https://webmention.rocks/test/6',
            'https://webmention.rocks/test/6/webmention'
        );
    }

    public function testWebmentionRocks7()
    {
        $this->checkDiscovery(
            'https://webmention.rocks/test/7',
            'https://webmention.rocks/test/7/webmention?head=true'
        );
    }

    public function testWebmentionRocks8()
    {
        $this->checkDiscovery(
            'https://webmention.rocks/test/8',
            'https://webmention.rocks/test/8/webmention?head=true'
        );
    }

    public function testWebmentionRocks9()
    {
        $this->checkDiscovery(
            'https://webmention.rocks/test/9',
            'https://webmention.rocks/test/9/webmention'
        );
    }

    public function testWebmentionRocks10()
    {
        $this->checkDiscovery(
            'https://webmention.rocks/test/10',
            'https://webmention.rocks/test/10/webmention?head=true'
        );
    }

    public function testWebmentionRocks11()
    {
        $this->checkDiscovery(
            'https://webmention.rocks/test/11',
            'https://webmention.rocks/test/11/webmention'
        );
    }

    public function testWebmentionRocks12()
    {
        $this->checkDiscovery(
            'https://webmention.rocks/test/12',
            'https://webmention.rocks/test/12/webmention'
        );
    }

    public function testWebmentionRocks13()
    {
        $this->checkDiscovery(
            'https://webmention.rocks/test/13',
            'https://webmention.rocks/test/13/webmention'
        );
    }

    public function testWebmentionRocks14()
    {
        $this->checkDiscovery(
            'https://webmention.rocks/test/14',
            'https://webmention.rocks/test/14/webmention'
        );
    }

    public function testWebmentionRocks15()
    {
        $this->checkDiscovery(
            'https://webmention.rocks/test/15',
            'https://webmention.rocks/test/15'
        );
    }

    public function testWebmentionRocks16()
    {
        $this->checkDiscovery(
            'https://webmention.rocks/test/16',
            'https://webmention.rocks/test/16/webmention'
        );
    }

    public function testWebmentionRocks17()
    {
        $this->checkDiscovery(
            'https://webmention.rocks/test/17',
            'https://webmention.rocks/test/17/webmention'
        );
    }

    public function testWebmentionRocks18()
    {
        $this->checkDiscovery(
            'https://webmention.rocks/test/18',
            'https://webmention.rocks/test/18/webmention?head=true'
        );
    }

    public function testWebmentionRocks19()
    {
        $this->checkDiscovery(
            'https://webmention.rocks/test/19',
            'https://webmention.rocks/test/19/webmention?head=true'
        );
    }

    public function testWebmentionRocks20()
    {
        $this->checkDiscovery(
            'https://webmention.rocks/test/20',
            'https://webmention.rocks/test/20/webmention'
        );
    }

    public function testWebmentionRocks21()
    {
        $this->checkDiscovery(
            'https://webmention.rocks/test/21',
            'https://webmention.rocks/test/21/webmention?query=yes'
        );
    }
}
?>
