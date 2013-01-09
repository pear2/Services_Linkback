<?php
/**
 * This file is part of the PEAR2\Services\Pingback package.
 *
 * PHP version 5
 *
 * @category Services
 * @package  PEAR2\Services\Pingback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Pingback
 */
namespace PEAR2\Services\Pingback\Server\Callback;

/**
 * Target URL verifier for files on the local file system.
 *
 * @category Services
 * @package  PEAR2\Services\Pingback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Pingback
 */
interface ITarget
{
    protected $localBasePath;
    protected $urlPrefix;

    public function __construct($localBasePath, $urlPrefix = null)
    {
        $this->localBasePath = $localBasePath;
        $this->urlPrefix = $urlPrefix;
    }

    /**
     * Verifies that the given target URI exists on the local file system.
     *
     * @param string $target Target URI that got linked to
     *
     * @return boolean True if the target URI exists, false if not
     *
     * @throws Exception When something fatally fails
     */
    public function verifyTargetExists($target)
    {
        $urlPath = parse_url($target, PHP_URL_PATH);
        $localPath = $this->localBasePath . $urlPath;
        //FIXME: what about index files?
        if (file_exists($localPath)) {
            return true;
        }
    }
}

?>
