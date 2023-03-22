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
namespace PEAR2\Services\Linkback\Server\Callback\StoreLinkback;
use PEAR2\Services\Linkback\Server\Callback as SPbC;

/**
 * Stores the linkback in a class variable that can be accessed later.
 *
 * @category Services
 * @package  PEAR2\Services\Linkback
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link     http://pear2.php.net/package/Services_Linkback
 */
class LbObject implements SPbC\IStorage
{
    public $target;
    public $source;
    public $sourceBody;
    public $res;

    /**
     * Stores the linkback in a class variable
     *
     * @param string $target     Target URI that should be linked in $source
     * @param string $source     Linkback source URI that should link to target
     * @param string $sourceBody Content of $source URI
     * @param object $res        HTTP response from fetching $source
     *
     * @return void
     *
     * @throws SPb\Exception When storing the linkback fatally failed
     */
    public function storeLinkback(
        $target, $source, $sourceBody, \HTTP_Request2_Response $res
    ) {
        $this->target = $target;
        $this->source = $source;
        $this->sourceBody = $sourceBody;
        $this->res = $res;
    }

}

?>
