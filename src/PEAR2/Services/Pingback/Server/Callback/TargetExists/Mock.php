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
namespace PEAR2\Services\Pingback\Server\Callback\TargetExists;

/**
 * Pingback server callback interface: Verify that the target URI exists
 * in our system.
 *
 * @category Services
 * @package  PEAR2\Services\Pingback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Pingback
 */
class Mock implements \PEAR2\Services\Pingback\Server\Callback\ITarget
{
    /**
     * @var boolean
     */
    protected $targetExists = true;

    /**
     * Set the mock response to the "does the target exist" question.
     *
     * @param boolean $targetExists Return value for the verifyTargetExists() method
     *
     * @return void
     */
    public function setTargetExists($targetExists)
    {
        $this->targetExists = $targetExists;
    }

    /**
     * Verifies that the given target URI exists in our system.
     *
     * @param string $target Target URI that got linked to
     *
     * @return boolean True if the target URI exists, false if not
     *
     * @throws Exception When something fatally fails
     */
    public function verifyTargetExists($target)
    {
        return $this->targetExists;
    }
}


?>
