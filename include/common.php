<?php

/**
 * Copyright (C) 2008-2010 FluxBB
 * based on code by Rickard Andersson copyright (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

session_start();

if (!defined('PUN_ROOT'))
	exit('The constant PUN_ROOT must be defined and point to a valid FluxBB installation root directory.');

// Define the version and database revision that this code was written for
define('FORUM_VERSION', '1.4.5');
define('EVE_BB_VERSION', '1.1.14');
//Functions for EvE-BB
define('EVE_ENABLED', 1);
require(PUN_ROOT.'include/eve_functions.php');

define('FORUM_DB_REVISION', 12);
define('FORUM_SI_REVISION', 2);
define('FORUM_PARSER_REVISION', 2);

// Block prefetch requests
if (isset($_SERVER['HTTP_X_MOZ']) && $_SERVER['HTTP_X_MOZ'] == 'prefetch')
{
	header('HTTP/1.1 403 Prefetching Forbidden');

	// Send no-cache headers
	header('Expires: Thu, 21 Jul 1977 07:30:00 GMT'); // When yours truly first set eyes on this world! :)
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: post-check=0, pre-check=0', false);
	header('Pragma: no-cache'); // For HTTP/1.0 compatibility

	exit;
} //End if.

// Attempt to load the configuration file config.php
if (file_exists(PUN_ROOT.'config.php'))
	require PUN_ROOT.'config.php';

// If we have the 1.3-legacy constant defined, define the proper 1.4 constant so we don't get an incorrect "need to install" message
if (defined('FORUM'))
	define('PUN', FORUM);

// Load the functions script
require PUN_ROOT.'include/functions.php';

// Load UTF-8 functions
require PUN_ROOT.'include/utf8/utf8.php';

// Strip out "bad" UTF-8 characters
forum_remove_bad_characters();

// Reverse the effect of register_globals
forum_unregister_globals();

// If PUN isn't defined, config.php is missing or corrupt
if (!defined('PUN'))
{
    header('Location: install.php');
    exit;
}

// Record the start time (will be used to calculate the generation time for the page)
$pun_start = get_microtime();

// Make sure PHP reports all errors except E_NOTICE. FluxBB supports E_ALL, but a lot of scripts it may interact with, do not
error_reporting(E_ALL ^ E_NOTICE);

// Force POSIX locale (to prevent functions such as strtolower() from messing up UTF-8 strings)
setlocale(LC_CTYPE, 'C');

// Turn off magic_quotes_runtime
if (get_magic_quotes_runtime())
	set_magic_quotes_runtime(0);

// Strip slashes from GET/POST/COOKIE (if magic_quotes_gpc is enabled)
if (get_magic_quotes_gpc())
{
	function stripslashes_array($array)
	{
		return is_array($array) ? array_map('stripslashes_array', $array) : stripslashes($array);
	}

	$_GET = stripslashes_array($_GET);
	$_POST = stripslashes_array($_POST);
	$_COOKIE = stripslashes_array($_COOKIE);
	$_REQUEST = stripslashes_array($_REQUEST);
}

// If a cookie name is not specified in config.php, we use the default (pun_cookie)
if (empty($cookie_name))
	$cookie_name = 'pun_cookie';

// If the cache directory is not specified, we use the default setting
if (!defined('FORUM_CACHE_DIR'))
	define('FORUM_CACHE_DIR', PUN_ROOT.'cache/');

// Define a few commonly used constants
define('PUN_UNVERIFIED', 0);
define('PUN_ADMIN', 1);
define('PUN_MOD', 2);
define('PUN_GUEST', 3);
define('PUN_MEMBER', 4);

// Load DB abstraction layer and connect
require PUN_ROOT.'include/dblayer/common_db.php';

// Start a transaction
$db->start_transaction();

// Load cached config
if (file_exists(FORUM_CACHE_DIR.'cache_config.php'))
	include FORUM_CACHE_DIR.'cache_config.php';

if (!defined('PUN_CONFIG_LOADED'))
{
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require PUN_ROOT.'include/cache.php';

	generate_config_cache();
	require FORUM_CACHE_DIR.'cache_config.php';
}

//Debugging is now based out of the admin options.
if ($pun_config['o_enable_debug'] == 1) {
	//The lock file is made by the options, if it doesn't exist, someone has deleted it.
	if (file_exists(FORUM_CACHE_DIR.'debug.lock')) {
		define('PUN_DEBUG', 1);
	} else {
		//Turn it off fully, just to be sure.
		$pun_config['o_enable_debug'] = 0;
		$db->query('UPDATE '.$db->prefix.'config SET conf_value=0 WHERE conf_name=\'o_enable_debug\'');
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require PUN_ROOT.'include/cache.php';
		generate_config_cache();
	} //End if - else.
} //End if.

//define('PUN_SHOW_QUERIES', 1);
//define('PUN_SHOW_REQUESTS', 1);

//Set the session length.
//This is not something I'd like anyone tampering with unless they are aware of the kind of issues it could bring.
//Anyone comfortable with editing the session length should be OK with editing this file.
$pun_config['session_length'] = 4*60*60;

// Verify that we are running the proper database schema revision
if (!isset($pun_config['o_database_revision']) || $pun_config['o_database_revision'] < FORUM_DB_REVISION ||
		!isset($pun_config['o_searchindex_revision']) || $pun_config['o_searchindex_revision'] < FORUM_SI_REVISION ||
		!isset($pun_config['o_parser_revision']) || $pun_config['o_parser_revision'] < FORUM_PARSER_REVISION ||
		version_compare($pun_config['o_cur_version'], FORUM_VERSION, '<'))
{
	header('Location: db_update.php');
	exit;
}

// Enable output buffering
if (!defined('PUN_DISABLE_BUFFERING'))
{
	// Should we use gzip output compression?
	if ($pun_config['o_gzip'] && extension_loaded('zlib'))
		ob_start('ob_gzhandler');
	else
		ob_start();
}

// Define standard date/time formats
$forum_time_formats = array($pun_config['o_time_format'], 'H:i:s', 'H:i', 'g:i:s a', 'g:i a');
$forum_date_formats = array($pun_config['o_date_format'], 'Y-m-d', 'Y-d-m', 'd-m-Y', 'm-d-Y', 'M j Y', 'jS M Y');

// Check/update/set cookie and fetch user info
$pun_user = array();
check_cookie($pun_user);

// Attempt to load the common language file
if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/common.php')) {
	include PUN_ROOT.'lang/'.$pun_user['language'].'/common.php';
	/*********** EVE-BB ***********/
	include PUN_ROOT.'lang/'.$pun_user['language'].'/eve_bb.php';
	/*********** EVE-BB ***********/
} else {
	error('There is no valid language pack \''.pun_htmlspecialchars($pun_user['language']).'\' installed. Please reinstall a language of that name');
} //End if - else.

// Check if we are to display a maintenance message
if ($pun_config['o_maintenance'] && $pun_user['g_id'] > PUN_ADMIN && !defined('PUN_TURN_OFF_MAINT'))
	maintenance_message();

// Load cached bans
if (file_exists(FORUM_CACHE_DIR.'cache_bans.php'))
	include FORUM_CACHE_DIR.'cache_bans.php';

if (!defined('PUN_BANS_LOADED'))
{
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require PUN_ROOT.'include/cache.php';

	generate_bans_cache();
	require FORUM_CACHE_DIR.'cache_bans.php';
}

// Check if current user is banned
check_bans();

// Check to see if we logged in without a cookie being set
if ($pun_user['is_guest'] && isset($_GET['login']))
	message($lang_common['No cookie']);

// The maximum size of a post, in bytes, since the field is now MEDIUMTEXT this allows ~16MB but lets cap at 1MB...
if (!defined('PUN_MAX_POSTSIZE'))
	define('PUN_MAX_POSTSIZE', 1048576);

if (!defined('PUN_SEARCH_MIN_WORD'))
	define('PUN_SEARCH_MIN_WORD', 3);
if (!defined('PUN_SEARCH_MAX_WORD'))
	define('PUN_SEARCH_MAX_WORD', 20);
	
 if (!defined('FORUM_MAX_COOKIE_SIZE'))
 	define('FORUM_MAX_COOKIE_SIZE', 4048);
	
/*********** EVE-BB ***********/
 	
//Define this ONLY if you want automated testing to be allowed on your server.
//define('EVEBB_AUTO_DEBUG', 1);

//Determine if we can/should use cURL where possible.
if (extension_loaded('curl') && $pun_config['o_use_fopen'] != '1') {
	define('EVEBB_CURL', 1);
} //End if.
require(PUN_ROOT.'include/request.php');
$pun_request = new Request();
 	
//See if they are allowed to have their own style.

if ($pun_config['o_allow_style'] != '1') {
	$pun_user['style'] = $pun_config['o_default_style'];
} //End if.

//We now make our hooks so we can run tasks via plugins.
$_HOOKS = array(
'rules' => array(),
'startup' => array(),
'users' => array(),
'api' => array(),
);

include(PUN_ROOT.'include/hook_classes.php');

//Now lets load the hook files...
$dir = scandir(PUN_ROOT.'plugins/hooks');
foreach ($dir as $d) {
	if (strlen($d) < 5) {
		continue;
	} //End if.
	
	if (substr($d, -3) != "php") {
		continue;
	} //End if.

	require(PUN_ROOT.'plugins/hooks/'.$d);
	
} //End foreach().
 	
task_runner(); //Run our updating tasks, skip it if possible to avoid load.

/*********** EVE-BB ***********/
