<?php
namespace \PEAR2\Services\Pingback2;

class Server_Callback_LinkExistsDefault
    implements Server_Callback_ILinkExists
{
    /**
     * Verifies that a link from $source to $target exists.
     *
     * @param string $target     Target URI that should be linked in $source
     * @param string $source     Pingback source URI that should link to target
     * @param string $sourceBody Content of $source URI
     * @param object $res        HTTP response from fetching $source
     *
     * @return boolean True if $source links to $target
     */
    public function verifyLinkExists(
        $target, $source, $sourceBody, \HTTP_Request2_Response $res
    ) {
        $doc = new DOMDocument();
        $doc->loadHTML($sourceBody);
        $xpath = new DOMXPath($doc);

        $targetNoQuotes = str_replace('"', '', $target);
        $nodeList = $xpath->query(
            '//a[@href="' . $target . '"'
            . ' or contains(@href, "' . $targetNoQuotes . '#"]'
        );

        return $nodeList->length > 0;
    }
}

?>
