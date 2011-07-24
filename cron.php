<?php
define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';

define('EVE_CRON_ACTIVE', true);

$log = task_runner();

foreach ($log as $l) {
	echo $l."<br/>";
} //End foreach.

$db->end_transaction();

?>