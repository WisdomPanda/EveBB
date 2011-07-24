<?php
/**
 * 24/07/2011
 * ts3_cron.php
 * Panda
 */

define('PUN_ROOT', '../../');

require PUN_ROOT.'include/common.php';

define('EVE_CRON_ACTIVE', true);

$log = '';
ts3_cron_task($log);

echo $log;

$db->end_transaction();

?>