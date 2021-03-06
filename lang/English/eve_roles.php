<?php
/**
 * 04/11/2011
 * eve_roles.php
 * Panda
 */

define('EVE_ROLES', 1);

$lang_api_sections = array(
	'general' 					=> 'General',
	'station_service' 		=> 'Station Service',
	'accounting' 			=> 'Accounting (Divisional)',
	'hanger_access' 		=> 'Hanger Access (Other)',
	'container_access' 	=> 'Container Access (Other)',
);

/**
 * Eve Corp Roles.
 * These are used to display the roles you want.
 */
$lang_api_roles = array(
	'0' => 'Any',																	//0
	'1' => 'Director',															//1
	'2'	=>	'',																		//10
	'4'	=>	'',																		//100
	'8'	=>	'',																		//1000
	'16' => '',																	//10000
	'32' => '',																	//100000
	'64' => '',																	//1000000
	'128' => 'Personnel Manager',											//10000000
	'256' => 'Accountant',													//100000000
	'512' => 'Security Officer',												//1000000000
	'1024' => 'Factory Manager',											//10000000000
	'2048' => 'Station Manager',											//100000000000
	'4096' => 'Auditor',														//1000000000000
	'8192' => 'Hangar Take 1 (Other)',									//10000000000000
	'16384' => 'Hangar Take 2 (Other)',									//100000000000000
	'32768' => 'Hangar Take 3 (Other)',									//1000000000000000
	'65536' => 'Hangar Take 4 (Other)',									//10000000000000000
	'131072' => 'Hangar Take 5 (Other)',								//100000000000000000
	'262144' => 'Hangar Take 6 (Other)',								//1000000000000000000
	'524288' => 'Hangar Take 7 (Other)',								//10000000000000000000
	'1048576' => 'Hangar Query 1 (Other)',								//100000000000000000000
	'2097152' => 'Hangar Query 2 (Other)',								//1000000000000000000000
	'4194304' => 'Hangar Query 3 (Other)',								//10000000000000000000000
	'8388608' => 'Hangar Query 4 (Other)',								//100000000000000000000000
	'16777216' => 'Hangar Query 5 (Other)',							//1000000000000000000000000
	'33554432' => 'Hangar Query 6 (Other)',							//10000000000000000000000000
	'67108864' => 'Hangar Query 7 (Other)',							//100000000000000000000000000
	'134217728' => 'Wallet Divison 1',									//1000000000000000000000000000
	'268435456' => 'Wallet Divison 2',									//10000000000000000000000000000
	'536870912' => 'Wallet Divison 3',									//100000000000000000000000000000
	'1073741824' => 'Wallet Divison 4',									//1000000000000000000000000000000
	'2147483648' => 'Wallet Divison 5',									//10000000000000000000000000000000
	'4294967296' => 'Wallet Divison 6',									//100000000000000000000000000000000
	'8589934592' => 'Wallet Divison 7',									//1000000000000000000000000000000000
	'17179869184' => 'Diplomat',											//10000000000000000000000000000000000
	'34359738368' => '',														//100000000000000000000000000000000000
	'68719476736' => '',														//1000000000000000000000000000000000000
	'137438953472' => '',													//10000000000000000000000000000000000000
	'274877906944' => '',													//100000000000000000000000000000000000000
	'549755813888' => '',													//1000000000000000000000000000000000000000
	'1099511627776' => '',													//10000000000000000000000000000000000000000
	'2199023255552' => 'Config Equipment',							//100000000000000000000000000000000000000000
	'4398046511104' => 'Container Take 1 (Other)',					//1000000000000000000000000000000000000000000
	'8796093022208' => 'Container Take 2 (Other)',					//10000000000000000000000000000000000000000000
	'17592186044416' => 'Container Take 3 (Other)',				//100000000000000000000000000000000000000000000
	'35184372088832' => 'Container Take 4 (Other)',				//1000000000000000000000000000000000000000000000
	'70368744177664' => 'Container Take 5 (Other)',				//10000000000000000000000000000000000000000000000
	'140737488355328' => 'Container Take 6 (Other)',				//100000000000000000000000000000000000000000000000
	'281474976710656' => 'Container Take 7 (Other)',				//1000000000000000000000000000000000000000000000000
	'562949953421312' => 'Rent Office',									//10000000000000000000000000000000000000000000000000
	'1125899906842624' => 'Rent Factory Slot',						//100000000000000000000000000000000000000000000000000
	'2251799813685248' => 'Rent Research Slot',						//1000000000000000000000000000000000000000000000000000
	'4503599627370496' => 'Junior Accountant',						//10000000000000000000000000000000000000000000000000000
	'9007199254740992' => 'Config Starbase Equipment',			//100000000000000000000000000000000000000000000000000000
	'18014398509481984' => 'Trader',									//1000000000000000000000000000000000000000000000000000000
	'36028797018963968' => 'Communications Officer',				//10000000000000000000000000000000000000000000000000000000
	'72057594037927936' => 'Contract Manager',					//100000000000000000000000000000000000000000000000000000000
	'144115188075855872' => 'Starbase Defense Operator',		//1000000000000000000000000000000000000000000000000000000000
	'288230376151711744' => 'Starbase Fuel Technician',			//10000000000000000000000000000000000000000000000000000000000
	'576460752303423488' => 'Fitting Manager',						//100000000000000000000000000000000000000000000000000000000000
	'1152921504606846976' => '',											//1000000000000000000000000000000000000000000000000000000000000
	'2305843009213693952' => '',											//10000000000000000000000000000000000000000000000000000000000000
	'4611686018427387904' => '',											//100000000000000000000000000000000000000000000000000000000000000
	'9223372036854775808' => '',											//1000000000000000000000000000000000000000000000000000000000000000
	'18446744073709551616' => '',										//10000000000000000000000000000000000000000000000000000000000000000
);

//Easy way to group roles based on what you want to display.
$api_roles_general = array(
	'0',
	'256',
	'4096',
	'36028797018963968',
	'2199023255552',
	'9007199254740992',
	'72057594037927936',
	'17179869184',
	'1',
	'576460752303423488',
	'4503599627370496',
	'128',
	'144115188075855872',
	'288230376151711744',
);

$api_roles_station_service = array(
	'1024',
	'1125899906842624',
	'562949953421312',
	'2251799813685248',
	'512',
	'2048',
	'18014398509481984',
);

$api_roles_accounting = array(
	'134217728',
	'268435456',
	'536870912',
	'1073741824',
	'2147483648',
	'4294967296',
	'8589934592',
);

$api_roles_hanger = array(
	'8192',
	'16384',
	'32768',
	'65536',
	'131072',
	'262144',
	'524288',
	'1048576',
	'2097152',
	'4194304',
	'8388608',
	'16777216',
	'33554432',
	'67108864',
);

$api_roles_container = array(
	'4398046511104',
	'8796093022208',
	'17592186044416',
	'35184372088832',
	'70368744177664',
	'140737488355328',
	'281474976710656',
);
?>