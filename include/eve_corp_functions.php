<?php

/**
 * This is a dummy file for eve_alliance_functions.php.
 * To enable the 'corp version', we simply remove eve_alliance_functions.php and admin_eve_alliance.php and huzzah! Corp only options.
 * Although technically, you can remove just eve_alliance_functions.php for the same effect.
 *
 * KISS.
 */

if (!defined('EVE_ENABLED')) {
	exit('Must be called locally.');
} //End if.

function task_update_alliance() {
	$log = array();
	return $log;
} //End task_update_alliance().

function refresh_alliance_list() {
	return true;
} //End refresh_alliance_list().

function purge_alliance($id, $skip_corp, $remove_group = true) {
	return false;
} //End purge_alliance().

function add_alliance($allianceID) {
	return false;
} //End add_alliance().

?>