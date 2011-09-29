<?php
/**
 * 04/09/2011
 * auth_test.php
 * Panda
 */
if (!defined('EVE_ENABLED')) {
	define('EVE_ENABLED', 1);
	define('PUN_ROOT', 'G:/Files/Development/workspace/EvE-BB/');
	require_once(PUN_ROOT.'include/common.php');
} //End if.
		
$_LAST_ERROR = 0;

class ApiAuthSimpleTest extends PHPUnit_Framework_TestCase {
	
	function setUp() {
	} //End setUp().
	
	function tearDown(){ }
	function testAuthIsValid() {
		$auth = new ApiAuth(981, 'AxEajtdOiM414uUdO6hdTDbyzfeKbd8kgxjrx4E2IQ7Doqvr5bVbnCKymin5LAi2');
		$auth->set_mask(126746632);
		$auth->set_url('http://apitest.eveonline.com/account/APIKeyInfo.xml.aspx');
		$this->assertTrue($auth->check_mask());
	}
} //End ApiAuthSimpleTest class.

?>