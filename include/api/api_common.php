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

/**
 * Now we define some bitmask values for roles.
 * We put this in an array since then we can key => value it for output.
 * Also on a side note: We keep them in string format so we're cross-machine OK.
 * (AKA: These values are 64-bit, not all servers that run EveBB are 64-bit, some are older home servers.)
 */
$api_roles = array(
'Any'	=> 									'0',
'Director' => 								'1',
'Personnel Manager' => 					'128',
'Accountant' => 							'256',
'Security Officer' => 						'512',
'Factory Manager' => 					'1024',
'Station Manager' => 					'2048',
'Auditor' => 								'4096',
'Hangar Can Take Division 1' => 		'8192',
'Hangar Can Take Division 2' => 		'16384',
'Hangar Can Take Division 3' => 		'32768',
'Hangar Can Take Division 4' => 		'65536',
'Hangar Can Take Division 5' => 		'131072',
'Hangar Can Take Division 6' => 		'262144',
'Hangar Can Take Division 7' => 		'524288',
'Hangar Can Query Division 1' => 	'1048576',
'Hangar Can Query Division 2' => 	'2097152',
'Hangar Can Query Division 3' => 	'4194304',
'Hangar Can Query Division 4' => 	'8388608',
'Hangar Can Query Division 5' => 	'16777216',
'Hangar Can Query Division 6' => 	'33554432',
'Hangar Can Query Division 7' => 	'67108864',
'Account Can Take Division 1' => 	'134217728',
'Account Can Take Division 2' => 	'268435456',
'Account Can Take Division 3' => 	'536870912',
'Account Can Take Division 4' => 	'1073741824',
'Account Can Take Division 5' => 	'2147483648',
'Account Can Take Division 6' => 	'4294967296',
'Account Can Take Division 7' => 	'8589934592',
'Account Can Query Division 1' => 	'17179869184',
'Account Can Query Division 2' => 	'34359738368',
'Account Can Query Division 3' => 	'68719476736',
'Account Can Query Division 4' => 	'137438953472',
'Account Can Query Division 5' => 	'274877906944',
'Account Can Query Division 6' => 	'549755813888',
'Account Can Query Division 7' => 	'1099511627776',
'Equipment Config' => 					'2199023255552',
'ContainerCan Take Division 1' => 	'4398046511104',
'ContainerCan Take Division 2'	 => 	'8796093022208',
'ContainerCan Take Division 3'	 => 	'17592186044416',
'ContainerCan Take Division 4'	 => 	'35184372088832',
'ContainerCan Take Division 5'	 => 	'70368744177664',
'ContainerCan Take Division 6'	 => 	'140737488355328',
'ContainerCan Take Division 7'	 => 	'281474976710656',
'Can Rent Office' => 						'562949953421312',
'Can Rent FactorySlot' => 				'1125899906842624',
'Can Rent ResearchSlot' => 			'2251799813685248',
'Junior Accountant' => 					'4503599627370496',
'Starbase Config' => 						'9007199254740992',
'Trader' => 									'18014398509481984',
'Chat Manager' => 						'36028797018963968',
'Infrastructure Tactical Officer' => 	'144115188075855872',
'Starbase Caretaker' => 				'288230376151711744',
'' => 											'576460752303423487',
'' => 											'1152921504606846975',
'' => 											'2305843009213693951',
'' => 											'4611686018427387903',
'' => 											'9223372036854775807',
);
?>