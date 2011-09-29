<?php
/**
 * 05/09/2011
 * characterTest.php
 * Panda
 */
if (!defined('EVE_ENABLED')) {
	define('EVE_ENABLED', 1);
	define('PUN_ROOT', 'G:/Files/Development/workspace/EvE-BB/');
	require_once(PUN_ROOT.'include/common.php');
} //End if.
		
$_LAST_ERROR = 0;

class ApiCharacterSimpleTest extends PHPUnit_Framework_TestCase {
	
	var $auth;
	var $char;
	
	function setUp() {
		$this->auth = new ApiAuth(981, 'AxEajtdOiM414uUdO6hdTDbyzfeKbd8kgxjrx4E2IQ7Doqvr5bVbnCKymin5LAi2', 1993143380);
		$this->auth->set_mask(126746632);
		$this->auth->set_url('http://apitest.eveonline.com/account/APIKeyInfo.xml.aspx');
		$this->char = new Character();
		$this->char->set_url('http://apitest.eveonline.com/char/CharacterSheet.xml.aspx');
	} //End setUp().
	
	function tearDown(){ }
	
	function testCharacterIsValid() {
		global $_LAST_ERROR;
		$this->char->load_character($this->auth->as_array());
		$this->assertEquals(0, $_LAST_ERROR);
		
	} //End testCharacterIsValid().
	
} //End ApiCharacterSimpleTest class.
?>