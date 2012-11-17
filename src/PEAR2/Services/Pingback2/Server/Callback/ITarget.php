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
 * Pingback server callback interface: Verify that the target URI exists
 * in our system.
 *
 * @category Services
 * @package  PEAR2\Services\Pingback2
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Pingback2
 */
interface Server_Callback_ITarget
{

    /**
     * Verifies that the given target URI exists in our system.
     *
     * @param string $target Target URI that got linked to
     *
     * @return boolean True if the target URI exists, false if not
     *
     * @throws Exception When something fatally fails
     */
    public function verifyTargetExists($target);
}


?>
