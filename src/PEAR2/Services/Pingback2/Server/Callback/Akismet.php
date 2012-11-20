<?php
/**
 * This file is part of the PEAR2\Services\Pingback2 package.
 *
 * PHP version 5
 *
 * @category Services
 * @package  PEAR2\Services\Pingback2
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Pingback2
 */
namespace PEAR2\Services\Pingback2;

/**
 * Akismet anti spam callback for the pingback server.
 * Submits pingbacks to Akismet to determine if it is spam or not.
 *
 * @category Services
 * @package  PEAR2\Services\Pingback2
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Pingback2
 */
class Server_Callback_Akismet implements Server_Callback_ILink
{
    /**
     *  URI of the blog that the pingback server is running on.
     *
     * @var string
     */
    protected $blogUri;

    /**
     * Akismet API key.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Set variables.
     *
     * @param string $blogUri URI of the blog that the pingback server is running on
     * @param string $apiKey  Akismet API key
     */
    public function __construct($blogUri, $apiKey)
    {
        $this->blogUri = $blogUri;
        $this->apiKey  = $apiKey;
    }

    /**
     * Do a spam check against Akismet.
     *
     * @param string $target     Target URI that should be linked in $source
     * @param string $source     Pingback source URI that should link to target
     * @param string $sourceBody Content of $source URI
     * @param object $res        HTTP response from fetching $source
     *
     * @return boolean True if the pingback is no spam.
     *
     * @throws Exception When the pingback is spam.
     *                   An exception is thrown so that other link callbacks
     *                   are overriden.
     */
    public function verifyLinkExists(
        $target, $source, $sourceBody, \HTTP_Request2_Response $res
    ) {
        $comment = new Services_Akismet2_Comment(
            array(
                'type'      => 'pingback',
                'authorUri' => $source
                //FIXME: more?
            )
        );

        $akismet = new Services_Akismet2($this->blogUrl, $this->apiKey);
        if (!$akismet->isSpam($comment)) {
            return true;
        }

        throw new Exception('The pingback is probably spam', States::SPAM);
    }
}

?>
