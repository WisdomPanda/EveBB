<?php
/**
 * 31/01/2011
 * api_common.php
 * Panda
 */



if (function_exists('simplexml_load_string')) {
	
	//Yay, PHP5. Let's use SimpleXML enabled classes.
	require(PUN_ROOT.'include/api/character_simple.php');
	require(PUN_ROOT.'include/api/corporation_simple.php');
	require(PUN_ROOT.'include/api/alliance_simple.php');
	
} else {
	
	//Bah, PHP4. Revert to Yee 'Old xml_parser classes.
	require(PUN_ROOT.'include/api/character_old.php');
	require(PUN_ROOT.'include/api/corporation_old.php');
	require(PUN_ROOT.'include/api/alliance_old.php');
	
} //End if - else.

?>