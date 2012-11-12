<?php
namespace PEAR2\Services\Pingback2;

interface Server_Callback_ISource
{
    /**
     * Fetch the source URL and return it.
     * The response object of the first callback providing one will be returned.
     *
     * @param string $url URL to fetch
     *
     * @return \HTTP_Request2_Response Response object
     *
     * @throws Exception When something fatally fails
     */
    public function fetchSource($url);
}


?>
