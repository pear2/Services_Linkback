<?php
/**
 * This file is part of the PEAR2\Services\Linkback package.
 *
 * PHP version 5
 *
 * @category Services
 * @package  PEAR2\Services\Linkback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Linkback
 */
namespace PEAR2\Services\Linkback\Server\Callback;
use PEAR2\Services\Linkback as SPb;

/**
 * Akismet anti spam callback for the linkback server.
 * Submits linkbacks to Akismet to determine if it is spam or not.
 *
 * @category Services
 * @package  PEAR2\Services\Linkback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Linkback
 */
class Akismet implements ILink
{
    /**
     *  URI of the blog that the linkback server is running on.
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
     * @param string $blogUri URI of the blog that the linkback server is running on
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
     * The Akismet support said:
     *
     *   The target URI does indeed go into "permalink".
     *   The source should go in "comment_author_URL".
     *   You should also have the remote IP address for user_ip, and the
     *   remote user agent, for comment_agent.
     *   WordPress also populates the comment_author and comment_content
     *   parameters with the source page title and excerpt, which it
     *   fetches when a pingback is received.
     *
     * @param string $target     Target URI that should be linked in $source
     * @param string $source     Linkback source URI that should link to target
     * @param string $sourceBody Content of $source URI
     * @param object $res        HTTP response from fetching $source
     *
     * @return boolean True if the linkback is no spam.
     *
     * @throws SPb\Exception When the linkback is spam.
     *                       An exception is thrown so that other link callbacks
     *                       are overriden.
     */
    public function verifyLinkExists(
        $target, $source, $sourceBody, \HTTP_Request2_Response $res
    ) {
        $comment = new Services_Akismet2_Comment(
            array(
                'type'               => 'pingback',
                'permalink'          => $target,
                'comment_author_url' => $source
                //FIXME: fill comment_content and comment_author
                // by fetching remote content
            )
        );

        $akismet = new Services_Akismet2($this->blogUrl, $this->apiKey);
        if (!$akismet->isSpam($comment)) {
            return true;
        }

        throw new SPb\Exception('The pingback is probably spam', States::SPAM);
    }
}

?>
