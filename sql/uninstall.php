<?php
/**
 * Warehouse Location Manager powered by Modulebuddy™ (www.modulebuddy.com)
 *
 *  @author    Modulebuddy™ <info@modulebuddy.com>
 *  @copyright 2015-2016 Modulebuddy™
 *  @license   Check License.txt file in module root directory for End User License Agreement.
 */

/**
* In some cases you should not drop the tables.
* Maybe the merchant will just try to reset the module
* but does not want to loose all of the data associated to the module.
*/

$sql = array();

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
