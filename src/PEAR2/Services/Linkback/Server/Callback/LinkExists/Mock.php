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
namespace PEAR2\Services\Linkback\Server\Callback\LinkExists;

/**
 * Returns the previously set response to a "does $source link to $target" question.
 * Used in unit tests.
 *
 * @category Services
 * @package  PEAR2\Services\Linkback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Linkback
 */
class Mock implements \PEAR2\Services\Linkback\Server\Callback\ILink
{
    /**
     * If the link shall exist
     *
     * @var boolean
     */
    protected $linkExists = false;

    /**
     * Set the mock response to the "does the link exist" question.
     *
     * @param boolean $linkExists Return value for the verifyLinkExists() method
     *
     * @return void
     */
    public function setLinkExists($linkExists)
    {
        $this->linkExists = $linkExists;
    }

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
        return $this->linkExists;
    }
}

?>
