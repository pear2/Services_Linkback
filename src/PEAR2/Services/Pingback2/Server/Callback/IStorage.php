<?php
namespace PEAR2\Services\Pingback2;

interface Server_Callback_IStorage
{
    /**
     * Stores the pingback somewhere.
     *
     * @param string $target     Target URI that should be linked in $source
     * @param string $source     Pingback source URI that should link to target
     * @param string $sourceBody Content of $source URI
     * @param object $res        HTTP response from fetching $source
     *
     * @return void
     *
     * @throws Exception When storing a pingback fatally failed
     */
    public function storePingback(
        $target, $source, $sourceBody, \HTTP_Request2_Response $res
    );

}


?>
