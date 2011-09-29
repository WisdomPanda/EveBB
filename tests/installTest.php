<?php
/**
 * 05/09/2011
 * installTest.php
 * Panda
 */

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class InstallTest extends PHPUnit_Framework_TestCase {
	
	function setUp() {
		$_POST['req_db_type'] = 'pgsql';
		$_POST['req_db_host'] = 'localhost';
		$_POST['req_db_name'] = 'evebb';
		$_POST['db_username'] = 'evebb';
		$_POST['db_password'] = 'y4FT6DDX7JBe3Lmy';
		$_POST['db_prefix'] = 'evebb_';
		$_POST['cookie_name'] = 'pun_cookie_fc25f3';
		$_POST['cookie_seed'] = '99c3dc49cb3cd746';
		$_POST['form_sent'] = 1;
		$_POST['req_email'] = 'pandaofwisdom@gmail.com';
		$_POST['api_user_id'] = '981';
		$_POST['api_character_id'] = '1993143380';
		$_POST['api_key'] = 'AxEajtdOiM414uUdO6hdTDbyzfeKbd8kgxjrx4E2IQ7Doqvr5bVbnCKymin5LAi2';
		$_POST['req_password1'] = 'Bamboo';
		$_POST['req_password2'] = 'Bamboo';
		$_POST['req_title'] = 'EveBB Bamboo Test!';
		$_POST['desc'] = 'EveBB - all your biomass needs!';
		$_POST['req_base_url'] = '127.0.0.1/evebb/';
		$_POST['req_default_lang'] = 'English';
		$_POST['req_default_style'] = 'evebbgray';
		define('IGNORE_CONFIG', 1);
	} //End setUp().
	
	function tearDown() {
	} //End tearDown().
	
	function testInstallIsValid() {
		if (!defined('PUN_ROOT')) {
			define('PUN_ROOT', 'G:/Files/Development/workspace/EvE-BB/');
			define('EVE_ENABLED', 1);
			global $_SERVER;
			$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		} //End if.
		require_once(PUN_ROOT.'install.php');
		$this->assertTrue(defined('EVEBB_INSTALLED'));
		
	} //End testCharacterIsValid().
	
} //End InstallTest class.

?>