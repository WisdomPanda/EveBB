<?php
define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';

if ($pun_config['o_eve_cron_token'] == '1') {
	if (!isset($_GET['token'])) {
		exit;
	} //End if.
	
	$token = read_id_file('cron');
	
	if ($_GET['token'] != $token) {
		exit;
	} //End if.
} //End if.

define('EVE_CRON_ACTIVE', true);

$log = task_runner();

foreach ($log as $l) {
	echo $l."<br/>";
} //End foreach.

$db->end_transaction();

?>