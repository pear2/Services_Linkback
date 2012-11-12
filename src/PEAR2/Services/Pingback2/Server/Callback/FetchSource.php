<?php
namespace PEAR2\Services\Pingback2;

class Server_Callback_FetchSource implements Server_Callback_ISource
{
    /**
     * Fetch the source URL and return it
     *
     * @param string $url URL to fetch
     *
     * @return \HTTP_Request2_Response Response object
     */
    public function fetchSource($url)
    {

        $req = new \HTTP_Request2($url);
        $req->setHeader(
            'accept',
            'text/html;q=0.9'
            . ', application/xhtml+xml;q=0.9'
            . ', */*;q=0.1'
        );
        return $req->send();
    }
}
?>
