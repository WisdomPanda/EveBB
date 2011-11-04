<?php
/**
 * 31/01/2011
 * api_common.php
 * Panda
 */

if (function_exists('simplexml_load_string')) {
	
	//Yay', PHP5. Let's use SimpleXML enabled classes.
	require(PUN_ROOT.'include/api/character_simple.php');
	require(PUN_ROOT.'include/api/corporation_simple.php');
	require(PUN_ROOT.'include/api/alliance_simple.php');
	
} else {
	
	//Bah', PHP4. Revert to Yee 'Old xml_parser classes.
	require(PUN_ROOT.'include/api/character_old.php');
	require(PUN_ROOT.'include/api/corporation_old.php');
	require(PUN_ROOT.'include/api/alliance_old.php');
	
} //End if - else.

require(PUN_ROOT.'include/api/cak.php');

define('CAK_UNKNOWN', 0); //Used for inital setup.
define('CAK_CHARACTER', 1);
define('CAK_ACCOUNT', 2);
define('CAK_CORPORATION', 3); //Can't be set as required, used for types.

if (!isset($pun_config['o_eve_cak_mask'])) {
	$pun_config['o_eve_cak_mask'] = CAK_MASK;
} //End if.

if (!isset($pun_config['o_eve_cak_type'])) {
	$pun_config['o_eve_cak_type'] = CAK_CHARACTER;
} //End if.
?>