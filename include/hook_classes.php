<?php
/**
 * 05/06/2011
 * hook_classes.php
 * Panda
 */

//The purpose of this file is create generic classes that we can use to allow plugins to hook into the functionality of the forums.
//The idea is that they can do the things they need to, without requiring code changes to your base install.

class RulesHook {
	
	function first_load($characters) {
		return;
	} //End first_load().
	
	function authed_user($user) {
		return;
	} //End authed_row().
	
	function restrict_user($user) {
		return false;
	} //End restrict_user().
	
	function last_load($characters) {
		return;
	} //End last_load().
	
} //End RulesHook class.

class StartupHook {
	
	function load() {
		return;
	} //End load().
	
} //End StartupHook class.

class UsersHook {
	
	function register($id) {
		return;
	} //End register().
	
	function register_failed($errors) {
		return;
	} //End register_failed().
	
	function login($id) {
		return;
	} //End function_user_login.
	
	function login_failed($id) {
		return;
	} //End login_failed().
	
	function user_deleted($id) {
		return;
	} //End user_deleted().
	
} //End UsersHook class.

class ApiHook {
	
	//More to come...
	
} //End ApiHook class.

?>