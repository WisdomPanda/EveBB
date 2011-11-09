<?php

/**
 * Copyright (C) 2008-2010 FluxBB
 * based on code by Rickard Andersson copyright (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// The FluxBB version this script installs
define('FORUM_VERSION', '1.4.5');
define('EVE_BB_VERSION', '1.1.14');

define('FORUM_DB_REVISION', 12);
define('FORUM_SI_REVISION', 2);
define('FORUM_PARSER_REVISION', 2);

define('MIN_PHP_VERSION', '4.4.0');
define('MIN_MYSQL_VERSION', '4.1.2');
define('MIN_PGSQL_VERSION', '7.0.0');
define('PUN_SEARCH_MIN_WORD', 3);
define('PUN_SEARCH_MAX_WORD', 20);

// Define a few commonly used constants
define('PUN_UNVERIFIED', 0);
define('PUN_ADMIN', 1);
define('PUN_MOD', 2);
define('PUN_GUEST', 3);
define('PUN_MEMBER', 4);

//Functions for EvE-BB
define('EVE_ENABLED', 1);

define('PUN_ROOT', dirname(__FILE__).'/');

//Determine if we can/should use cURL where possible.
if (extension_loaded('curl') && $pun_config['o_use_fopen'] != '1') {
	define('EVEBB_CURL', 1);
} //End if.
require(PUN_ROOT.'include/request.php');
$pun_request = new Request();

// If we've been passed a default language, use it
$install_lang = isset($_REQUEST['install_lang']) ? trim($_REQUEST['install_lang']) : 'English';

// If such a language pack doesn't exist, or isn't up-to-date enough to translate this page, default to English
if (!file_exists(PUN_ROOT.'lang/'.$install_lang.'/install.php'))
    $install_lang = 'English';
    
require PUN_ROOT.'lang/'.$install_lang.'/install.php';

if (file_exists(PUN_ROOT.'config.php'))
{
	// Check to see whether FluxBB is already installed
	include PUN_ROOT.'config.php';

	// If we have the 1.3-legacy constant defined, define the proper 1.4 constant so we don't get an incorrect "need to install" message
	if (defined('FORUM'))
		define('PUN', FORUM);

	// If PUN is defined, config.php is probably valid and thus the software is installed
	if (defined('PUN'))
		exit($lang_install['Already installed']);
}

// Define PUN because email.php requires it
define('PUN', 1);

// If the cache directory is not specified, we use the default setting
if (!defined('FORUM_CACHE_DIR'))
	define('FORUM_CACHE_DIR', PUN_ROOT.'cache/');

// Make sure we are running at least MIN_PHP_VERSION
if (!function_exists('version_compare') || version_compare(PHP_VERSION, MIN_PHP_VERSION, '<'))
	exit(sprintf($lang_install['You are running error'], 'PHP', PHP_VERSION, FORUM_VERSION, MIN_PHP_VERSION));

// Load the functions script
require PUN_ROOT.'include/functions.php';

// Load the eve functions script
require PUN_ROOT.'include/eve_functions.php';

// Load UTF-8 functions
require PUN_ROOT.'include/utf8/utf8.php';

// Strip out "bad" UTF-8 characters
forum_remove_bad_characters();

// Reverse the effect of register_globals
forum_unregister_globals();

// Disable error reporting for uninitialized variables
error_reporting(E_ALL);

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

// Turn off PHP time limit
@set_time_limit(0);

//Make sure we have full scope for character data.
$char_sheet;

//
// Generate output to be used for config.php
//
function generate_config_file()
{
	global $db_type, $db_host, $db_name, $db_username, $db_password, $db_prefix, $cookie_name, $cookie_seed;
	
	return '<?php'."\n\n".'$db_type = \''.$db_type."';\n".'$db_host = \''.$db_host."';\n".'$db_name = \''.addslashes($db_name)."';\n".'$db_username = \''.addslashes($db_username)."';\n".'$db_password = \''.addslashes($db_password)."';\n".'$db_prefix = \''.addslashes($db_prefix)."';\n".'$p_connect = false;'."\n\n".'$cookie_name = '."'".$cookie_name."';\n".'$cookie_domain = '."'';\n".'$cookie_path = '."'/';\n".'$cookie_secure = 0;'."\n".'$cookie_seed = \''.random_key(16, false, true)."';\n\ndefine('PUN', 1);\n";
}


if (isset($_POST['generate_config']))
{
	header('Content-Type: text/x-delimtext; name="config.php"');
	header('Content-disposition: attachment; filename=config.php');

	$db_type = $_POST['db_type'];
	$db_host = $_POST['db_host'];
	$db_name = $_POST['db_name'];
	$db_username = $_POST['db_username'];
	$db_password = $_POST['db_password'];
	$db_prefix = $_POST['db_prefix'];
	$cookie_name = $_POST['cookie_name'];
	$cookie_seed = $_POST['cookie_seed'];

	echo generate_config_file();
	exit;
}


if (!isset($_POST['form_sent']))
{
	// Make an educated guess regarding base_url
	$base_url  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';	// protocol
	$base_url .= preg_replace('/:(80|443)$/', '', $_SERVER['HTTP_HOST']);							// host[:port]
	$base_url .= str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));							// path

	if (substr($base_url, -1) == '/')
		$base_url = substr($base_url, 0, -1);

	$db_type = $db_name = $db_username = $db_prefix = $username = $email = '';
	$db_prefix = 'evebb_';
	$api_user_id = $api_character_id = $api_key = '';
	$db_host = 'localhost';
	$title = $lang_install['My FluxBB Forum'];
	$description = '<p><span>'.$lang_install['Description'].'</span></p>';
	$default_lang = $install_lang;
	$default_style = 'evebbgray';
}
else
{
	$db_type = $_POST['req_db_type'];
	$db_host = pun_trim($_POST['req_db_host']);
	$db_name = pun_trim($_POST['req_db_name']);
	$db_username = pun_trim($_POST['db_username']);
	$db_password = pun_trim($_POST['db_password']);
	$db_prefix = pun_trim($_POST['db_prefix']);
	//$username = pun_trim($_POST['req_username']);
	$email = strtolower(pun_trim($_POST['req_email']));
	$api_user_id = $_POST['api_user_id'];
	$api_character_id = $_POST['api_character_id'];
	$api_key = $_POST['api_key'];
	$password1 = pun_trim($_POST['req_password1']);
	$password2 = pun_trim($_POST['req_password2']);
	$title = pun_trim($_POST['req_title']);
	$description = pun_trim($_POST['desc']);
	$base_url = pun_trim($_POST['req_base_url']);
	$default_lang = pun_trim($_POST['req_default_lang']);
	$default_style = pun_trim($_POST['req_default_style']);
	$alerts = array();

	// Make sure base_url doesn't end with a slash
	if (substr($base_url, -1) == '/')
		$base_url = substr($base_url, 0, -1);

		
	/*---------- EVE-BB DATA CHECKS ---------*/
	//return;
	if (strlen($api_key) > 64 && strlen($api_key) < 20) {
		$alerts[] = 'Incorrect API Verification Code Lengt. Found '.strlen($api_key).', but expected between 20 and 64 inclusive.';
	} //End if.
	
	if (!is_numeric($api_user_id)) {
		$alerts[] = 'Incorrect API Key ID format.';
	} //End if.
	
	if (!is_numeric($api_character_id)) {
		$alerts[] = 'Incorrect API Character ID format.<br/> Please make sure you fetch your characters and select an active character from the list.';
	} //End if.
	/*---------- EVE-BB DATA CHECKS ---------*/

	if (pun_strlen($password1) < 4)
		$alerts[] = $lang_install['Short password'];
	else if ($password1 != $password2)
		$alerts[] = $lang_install['Passwords not match'];

	// Validate email
	require PUN_ROOT.'include/email.php';

	if (!is_valid_email($email))
		$alerts[] = $lang_install['Wrong email'];

	if ($title == '')
		$alerts[] = $lang_install['No board title'];

	$languages = forum_list_langs();
	if (!in_array($default_lang, $languages))
		$alerts[] = $lang_install['Error default language'];

	$styles = forum_list_styles();
	if (!in_array($default_style, $styles))
		$alerts[] = $lang_install['Error default style'];
		
	// Check if the cache directory is writable
	if (!@is_writable(FORUM_CACHE_DIR))
	    $alerts[] = sprintf($lang_install['Alert cache'], FORUM_CACHE_DIR);
	    
	// Check if default avatar directory is writable
	if (!@is_writable(PUN_ROOT.'img/avatars/'))
	    $alerts[] = sprintf($lang_install['Alert avatar'], PUN_ROOT.'img/avatars/');
	
	
	/*---------- EVE-BB DATA CHECKS ---------*/
	//Now we get the character data.
	if (empty($alerts)) {
		//We need the escape feature of the DB, so it's been promoted!
		// Load the appropriate DB layer class
		// Validate prefix
		if (strlen($db_prefix) > 0 && (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $db_prefix) || strlen($db_prefix) > 40))
			error(sprintf($lang_install['Table prefix error'], $db->prefix));
			
		// Load the appropriate DB layer class
		switch ($db_type)
		{
			case 'mysql':
				require PUN_ROOT.'include/dblayer/mysql.php';
				break;
	
			case 'mysql_innodb':
				require PUN_ROOT.'include/dblayer/mysql_innodb.php';
				break;
	
			case 'mysqli':
				require PUN_ROOT.'include/dblayer/mysqli.php';
				break;
	
			case 'mysqli_innodb':
				require PUN_ROOT.'include/dblayer/mysqli_innodb.php';
				break;
	
			case 'pgsql':
				require PUN_ROOT.'include/dblayer/pgsql.php';
				break;
	
			case 'sqlite':
				require PUN_ROOT.'include/dblayer/sqlite.php';
				break;
	
			default:
				error(sprintf($lang_install['DB type not valid'], pun_htmlspecialchars($db_type)));
		}
	
		// Create the database object (and connect/select db)
		$db = new DBLayer($db_host, $db_username, $db_password, $db_name, $db_prefix, false);
		$username = 'EveBB-User';
		// Do some DB type specific checks
		switch ($db_type)
		{
			case 'mysql':
			case 'mysqli':
			case 'mysql_innodb':
			case 'mysqli_innodb':
				$mysql_info = $db->get_version();
				if (version_compare($mysql_info['version'], MIN_MYSQL_VERSION, '<'))
					error(sprintf($lang_install['You are running error'], 'MySQL', $mysql_info['version'], FORUM_VERSION, MIN_MYSQL_VERSION));
				break;
	
			case 'pgsql':
				$pgsql_info = $db->get_version();
				if (version_compare($pgsql_info['version'], MIN_PGSQL_VERSION, '<'))
					error(sprintf($lang_install['You are running error'], 'PostgreSQL', $pgsql_info['version'], FORUM_VERSION, MIN_PGSQL_VERSION));
				break;
	
			case 'sqlite':
				if (strtolower($db_prefix) == 'sqlite_')
					error($lang_install['Prefix reserved']);
				break;
				
			default:
				error(sprintf($lang_install['DB type not valid'], pun_htmlspecialchars($db_type)));
		}
		//Lets validate the API info first.
		$cak = new CAK($api_user_id, $api_key, $api_character_id);
		
		if (($cak_err = $cak->validate(true)) != CAK_OK) {
			switch ($cak_err) {
				case(CAK_NOT_INIT):
					$alerts[] = "[$cak_err]: An internal error has occured while dealing with the API information. Well damn.";
					break;
				case(CAK_VCODE_LEN):
					$alerts[] = "[$cak_err]: Your API Verification Code does not meet security requirements.<br/> Please generate a vcode between 20 and 64 characters in length.";
					break;
				case(CAK_ID_NOT_NUM):
					$alerts[] = "[$cak_err]: Your API Key ID is not a valid ID.";
					break;
				case(CAK_BAD_VCODE):
					$alerts[] = "[$cak_err]: Your API Verification Code is not valid.";
					break;
			} //End switch().
		} else if (($cak_err = $cak->validate_mask()) != CAK_OK) {
			switch ($cak_err) {
				case(CAK_BAD_FETCH):
					$alerts[] = "[$cak_err]: Unable to fetch information from the API server. Please ensure the API server is currently operational.";
					break;
				case(CAK_BAD_KEY):
					$alerts[] = "[$cak_err]: Your API Detials are not correct, please ensure they are correct and try again.";
					break;
				case(CAK_BAD_MASK):
					$alerts[] = "[$cak_err]: Unable to locate a non-zero access mask for your CAK.";
					break;
				case(CAK_EXPIRE_SET):
					$alerts[] = "[$cak_err]: Your CAK is set to expire; EveBB does not support this option. (By choice)";
					break;
				case(CAK_BAD_TYPE):
					$alerts[] = "[$cak_err]: Your CAK type is not allowed by the administrators of this forum. If you are using character based CAK's, please try account based instead.";
					break;
			} //End switch().
		} //End if - else if.
		
		// Create the database object (and connect/select db)
		$db = new DBLayer($db_host, $db_username, $db_password, $db_name, $db_prefix, false);
		if (empty($alerts)){
			if (!$char_sheet = fetch_character_api($cak)) {
				$alerts[] = "Unable to fetch character API information. Please insure that the API server is functioning.";
			} else {
				$username = substr(strip_special($char_sheet->name), 0, 25);
			} //End if - else.
		} //End if.
	} //End if.
	/*---------- EVE-BB DATA CHECKS ---------*/
	// Validate username and passwords
	if (pun_strlen($username) < 2)
		$alerts[] = $lang_install['Username 1'];
	else if (pun_strlen($username) > 25) // This usually doesn't happen since the form element only accepts 25 characters
		$alerts[] = $lang_install['Username 2'];
	else if (!strcasecmp($username, 'Guest'))
		$alerts[] = $lang_install['Username 3'];
	else if (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $username) || preg_match('/((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))/', $username))
		$alerts[] = $lang_install['Username 4'];
	else if ((strpos($username, '[') !== false || strpos($username, ']') !== false) && strpos($username, '\'') !== false && strpos($username, '"') !== false)
		$alerts[] = $lang_install['Username 5'];
	else if (preg_match('/(?:\[\/?(?:b|u|i|h|colou?r|quote|code|img|url|email|list)\]|\[(?:code|quote|list)=)/i', $username))
		$alerts[] = $lang_install['Username 6'];
}

if (!isset($_POST['form_sent']) || !empty($alerts))
{
	// Determine available database extensions
	$dual_mysql = false;
	$db_extensions = array();
	$mysql_innodb = false;
	if (function_exists('mysqli_connect'))
	{
		$db_extensions[] = array('mysqli', 'MySQL Improved');
		$db_extensions[] = array('mysqli_innodb', 'MySQL Improved (InnoDB)');
		$mysql_innodb = true;
	}
	if (function_exists('mysql_connect'))
	{
		$db_extensions[] = array('mysql', 'MySQL Standard');
		$db_extensions[] = array('mysql_innodb', 'MySQL Standard (InnoDB)');
		$mysql_innodb = true;

		if (count($db_extensions) > 2)
			$dual_mysql = true;
	}
	if (function_exists('sqlite_open'))
		$db_extensions[] = array('sqlite', 'SQLite');
	if (function_exists('pg_connect'))
		$db_extensions[] = array('pgsql', 'PostgreSQL');

	if (empty($db_extensions))
		error($lang_install['No DB extensions']);
		
	// Fetch a list of installed languages
	$languages = forum_list_langs();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $lang_install['FluxBB Installation'] ?></title>
<link rel="stylesheet" type="text/css" href="style/<?php echo $default_style ?>.css" />
<script type="text/javascript" src="apiFetching.js"></script>
<script type="text/javascript">
/* <![CDATA[ */
function process_form(the_form)
{
    var element_names = {
            "req_db_type": "<?php echo $lang_install['Database type'] ?>",
            "req_db_host": "<?php echo $lang_install['Database server hostname'] ?>",
            "req_db_name": "<?php echo $lang_install['Database name'] ?>",
            "db_prefix": "<?php echo $lang_install['Table prefix'] ?>",
            "req_password1": "<?php echo $lang_install['Administrator password 1'] ?>",
            "req_password2": "<?php echo $lang_install['Administrator password 2'] ?>",
            "req_email": "<?php echo $lang_install['Administrator email'] ?>",
            "req_title": "<?php echo $lang_install['Board title'] ?>",
            "req_base_url": "<?php echo $lang_install['Base URL'] ?>"
        };

	if (document.all || document.getElementById)
	{
		for (var i = 0; i < the_form.length; ++i)
		{
			var elem = the_form.elements[i];
            if (elem.name && (/^req_/.test(elem.name)))
            {
                if (!elem.value && elem.type && (/^(?:text(?:area)?|password|file)$/i.test(elem.type)))
                {
                    alert('"' + element_names[elem.name] + '" <?php echo $lang_install['Required field'] ?>');
                    elem.focus();
                    return false;
				}
			}
		}
	}

	return true;
}
/* ]]> */
</script>
</head>
<body onload="document.getElementById('install').req_db_type.focus();document.getElementById('install').start.disabled=false;">

<div id="puninstall" class="pun">
<div class="top-box"><div><!-- Top Corners --></div></div>
<div class="punwrap">

<div id="brdheader" class="block">
	<div class="box">
		<div id="brdtitle" class="inbox">
			<h1><span><?php echo $lang_install['FluxBB Installation'] ?></span></h1>
			<div id="brddesc"><p><?php echo $lang_install['Install message'] ?></p><p><?php echo $lang_install['Welcome'] ?></p></div>
		</div>
	</div>
</div>

<div id="brdmain">
<?php if (count($languages) > 1): ?><div class="blockform">
    <h2><span><?php echo $lang_install['Choose install language'] ?></span></h2>
    <div class="box">
        <form id="install" method="post" action="install.php">
            <div class="inform">
                <fieldset>
                    <legend><?php echo $lang_install['Install language'] ?></legend>
                    <div class="infldset">
                        <p><?php echo $lang_install['Choose install language info'] ?></p>
                        <label><strong><?php echo $lang_install['Install language'] ?></strong>
                        <br /><select name="install_lang">
<?php

        foreach ($languages as $temp)
        {
            if ($temp == $install_lang)
                echo "\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.$temp.'</option>'."\n";
            else
                echo "\t\t\t\t\t".'<option value="'.$temp.'">'.$temp.'</option>'."\n";
        }

?>
                        </select>
                        <br /></label>
                    </div>
                </fieldset>
            </div>
            <p class="buttons"><input type="submit" name="start" value="<?php echo $lang_install['Change language'] ?>" /></p>
        </form>
    </div>
</div>
<?php endif; ?>
<div class="blockform">
	<h2><span><?php echo sprintf($lang_install['Install'], EVE_BB_VERSION) ?></span></h2>
	<div class="box">
		<form id="install" method="post" action="install.php" onsubmit="this.start.disabled=true;if(process_form(this)){return true;}else{this.start.disabled=false;return false;}">
		<div><input type="hidden" name="form_sent" value="1" /><input type="hidden" name="install_lang" value="<?php echo pun_htmlspecialchars($install_lang) ?>" /></div>
			<div class="inform">
<?php if (!empty($alerts)): ?>				<div class="forminfo error-info">
					<h3><?php echo $lang_install['Errors'] ?></h3>
					<ul class="error-list">
<?php

foreach ($alerts as $cur_alert)
	echo "\t\t\t\t\t\t".'<li><strong>'.$cur_alert.'</strong></li>'."\n";
?>
					</ul>
				</div>
<?php endif; ?>			</div>
			<div class="inform">
				<div class="forminfo">
					<h3><?php echo $lang_install['Database setup'] ?></h3>
					<p><?php echo $lang_install['Info 1'] ?></p>
				</div>
				<fieldset>
				<legend><?php echo $lang_install['Select database'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_install['Info 2'] ?></p>
<?php if ($dual_mysql): ?>						 <p><?php echo $lang_install['Dual MySQL'] ?></p>
<?php endif; ?><?php if ($mysql_innodb): ?>						<p><?php echo $lang_install['InnoDB'] ?></p>
<?php endif; ?>						<label class="required"><strong><?php echo $lang_install['Database type'] ?> <span><?php echo $lang_install['Required'] ?></span></strong>
						<br /><select name="req_db_type">
<?php

	foreach ($db_extensions as $temp)
	{
		if ($temp[0] == $db_type)
			echo "\t\t\t\t\t\t\t".'<option value="'.$temp[0].'" selected="selected">'.$temp[1].'</option>'."\n";
		else
			echo "\t\t\t\t\t\t\t".'<option value="'.$temp[0].'">'.$temp[1].'</option>'."\n";
	}

?>
						</select>
						<br /></label>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_install['Database hostname'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_install['Info 3'] ?></p>
						<label class="required"><strong><?php echo $lang_install['Database server hostname'] ?> <span><?php echo $lang_install['Required'] ?></span></strong><br /><input type="text" name="req_db_host" value="<?php echo pun_htmlspecialchars($db_host) ?>" size="50" /><br /></label>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_install['Database enter name'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_install['Info 4'] ?></p>
						<label class="required"><strong><?php echo $lang_install['Database name'] ?> <span><?php echo $lang_install['Required'] ?></span></strong><br /><input id="req_db_name" type="text" name="req_db_name" value="<?php echo pun_htmlspecialchars($db_name) ?>" size="30" /><br /></label>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_install['Database enter informations'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_install['Info 5'] ?></p>
						<label class="conl"><?php echo $lang_install['Database username'] ?><br /><input type="text" name="db_username" value="<?php echo pun_htmlspecialchars($db_username) ?>" size="30" /><br /></label>
						<label class="conl"><?php echo $lang_install['Database password'] ?><br /><input type="password" name="db_password" size="30" /><br /></label>
						<div class="clearer"></div>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_install['Database enter prefix'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_install['Info 6'] ?></p>
						<label><?php echo $lang_install['Table prefix'] ?><br /><input id="db_prefix" type="text" name="db_prefix" value="<?php echo pun_htmlspecialchars($db_prefix) ?>" size="20" maxlength="30" /><br /></label>
					</div>
				</fieldset>
			</div>
<?php
/* We're keeping this here for record. It is being replaced by the characters name.
			<div class="inform">
				<div class="forminfo">
					<h3><?php echo $lang_install['Administration setup'] ?></h3>
					<p><?php echo $lang_install['Info 7'] ?></p>
				</div>
				<fieldset>
					<legend><?php echo $lang_install['Admin enter username'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_install['Info 8'] ?></p>
						<label class="required"><strong><?php echo $lang_install['Administrator username'] ?> <span><?php echo $lang_install['Required'] ?></span></strong><br /><input type="text" name="req_username" value="<?php echo pun_htmlspecialchars($username) ?>" size="25" maxlength="25" /><br /></label>
					</div>
				</fieldset>
			</div>
	*/
?>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_install['Admin enter password'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_install['Info 9'] ?></p>
						<label class="conl required"><strong><?php echo $lang_install['Password'] ?> <span><?php echo $lang_install['Required'] ?></span></strong><br /><input id="req_password1" type="password" name="req_password1" size="16" /><br /></label>
						<label class="conl required"><strong><?php echo $lang_install['Confirm password'] ?> <span><?php echo $lang_install['Required'] ?></span></strong><br /><input type="password" name="req_password2" size="16" /><br /></label>
						<div class="clearer"></div>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_install['Admin enter email'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_install['Info 10'] ?></p>
						<label class="required"><strong><?php echo $lang_install['Administrator email'] ?> <span><?php echo $lang_install['Required'] ?></span></strong><br /><input id="req_email" type="text" name="req_email" value="<?php echo pun_htmlspecialchars($email) ?>" size="50" maxlength="80" /><br /></label>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_install['Evebb_admin_api_legend'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_install['Evebb_admin_api_info1'] ?></p>
						<label class="required"><strong><?php echo $lang_install['Evebb_admin_api_id'] ?> <span><?php echo $lang_install['Required'] ?></span></strong><br /><input id="api_user_id" type="text" name="api_user_id" value="<?php echo pun_htmlspecialchars($api_user_id) ?>" size="50" maxlength="80" /><br /></label>
						<label class="required"><strong><?php echo $lang_install['Evebb_admin_api_key'] ?> <span><?php echo $lang_install['Required'] ?></span></strong><br /><input id="api_key" type="text" name="api_key" value="<?php echo pun_htmlspecialchars($api_key) ?>" size="50" maxlength="80" /><br /></label><br/>
						<span id="api_holder"><a class="fetch_chars" href="index.php" onclick="fetchCharacters(); return false;"><span id="char_fetch_text"><?php echo $lang_install['Evebb_admin_api_fetch'] ?></span></a></span>
						<p><?php echo $lang_install['Evebb_admin_api_info2'] ?></p>
						<div class="clearer"></div>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<div class="forminfo">
					<h3><?php echo $lang_install['Board setup'] ?></h3>
					<p><?php echo $lang_install['Info 11'] ?></p>
				</div>
				<fieldset>
					<legend><?php echo $lang_install['Enter board title'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_install['Info 12'] ?></p>
						<label class="required"><strong><?php echo $lang_install['Board title'] ?> <span><?php echo $lang_install['Required'] ?></span></strong><br /><input id="req_title" type="text" name="req_title" value="<?php echo pun_htmlspecialchars($title) ?>" size="60" maxlength="255" /><br /></label>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_install['Enter board description'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_install['Info 13'] ?></p>
						<label><?php echo $lang_install['Board description'] ?><br /><input id="desc" type="text" name="desc" value="<?php echo pun_htmlspecialchars($description) ?>" size="60" maxlength="255" /><br /></label>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_install['Enter base URL'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_install['Info 14'] ?></p>
						<label class="required"><strong><?php echo $lang_install['Base URL'] ?> <span><?php echo $lang_install['Required'] ?></span></strong><br /><input id="req_base_url" type="text" name="req_base_url" value="<?php echo pun_htmlspecialchars($base_url) ?>" size="60" maxlength="100" /><br /></label>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_install['Choose the default language'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_install['Info 15'] ?></p>
						<label class="required"><strong><?php echo $lang_install['Default language'] ?> <span><?php echo $lang_install['Required'] ?></span></strong><br /><select id="req_default_lang" name="req_default_lang">
<?php

		$languages = forum_list_langs();

		foreach ($languages as $temp)
		{
			if ($temp == $default_lang)
				echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.$temp.'</option>'."\n";
			else
				echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.$temp.'</option>'."\n";
		}

?>
						</select><br /></label>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_install['Choose the default style'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_install['Info 16'] ?></p>
						<label class="required"><strong><?php echo $lang_install['Default style'] ?> <span><?php echo $lang_install['Required'] ?></span></strong><br /><select id="req_default_style" name="req_default_style">
<?php

		$styles = forum_list_styles();

		foreach ($styles as $temp)
		{
			if ($temp == $default_style)
				echo "\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.str_replace('_', ' ', $temp).'</option>'."\n";
			else
				echo "\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.str_replace('_', ' ', $temp).'</option>'."\n";
		}

?>
						</select><br /></label>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="start" value="<?php echo $lang_install['Start install'] ?>" /></p>
		</form>
	</div>
</div>
</div>

</div>
<div class="end-box"><div><!-- Bottom Corners --></div></div>
</div>

</body>
</html>
<?php

}
else
{

	//We created the DB earlier, so the checks move to there.


	// Make sure FluxBB isn't already installed
	$result = $db->query('SELECT 1 FROM '.$db_prefix.'users WHERE id=1');
	if ($db->num_rows($result))
		error(sprintf($lang_install['Existing table error'], $db_prefix, $db_name));
	
	// Check if InnoDB is available
	if ($db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb')
	{
		$result = $db->query('SHOW VARIABLES LIKE \'have_innodb\'');
		list (, $result) = $db->fetch_row($result);
		if ((strtoupper($result) != 'YES'))
			error($lang_install['InnoDB off']);
	}


	// Start a transaction
	$db->start_transaction();


	// Create all tables
	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'username'		=> array(
				'datatype'		=> 'VARCHAR(200)',
				'allow_null'	=> true
			),
			'ip'			=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> true
			),
			'email'			=> array(
				'datatype'		=> 'VARCHAR(80)',
				'allow_null'	=> true
			),
			'message'		=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> true
			),
			'expire'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'ban_creator'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			)
		),
		'PRIMARY KEY'	=> array('id'),
		'INDEXES'		=> array(
			'username_idx'	=> array('username')
		)
	);

	if ($db_type == 'mysql' || $db_type == 'mysqli' || $db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb')
		$schema['INDEXES']['username_idx'] = array('username(25)');

	$db->create_table('bans', $schema) or error('Unable to create bans table', __FILE__, __LINE__, $db->error());


	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'cat_name'		=> array(
				'datatype'		=> 'VARCHAR(80)',
				'allow_null'	=> false,
				'default'		=> '\'New Category\''
			),
			'disp_position'	=> array(
				'datatype'		=> 'INT(10)',
				'allow_null'	=> false,
				'default'		=> '0'
			)
		),
		'PRIMARY KEY'	=> array('id')
	);

	$db->create_table('categories', $schema) or error('Unable to create categories table', __FILE__, __LINE__, $db->error());


	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'search_for'	=> array(
				'datatype'		=> 'VARCHAR(60)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'replace_with'	=> array(
				'datatype'		=> 'VARCHAR(60)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			)
		),
		'PRIMARY KEY'	=> array('id')
	);

	$db->create_table('censoring', $schema) or error('Unable to create censoring table', __FILE__, __LINE__, $db->error());


	$schema = array(
		'FIELDS'		=> array(
			'conf_name'		=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'conf_value'	=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> true
			)
		),
		'PRIMARY KEY'	=> array('conf_name')
	);

	$db->create_table('config', $schema) or error('Unable to create config table', __FILE__, __LINE__, $db->error());


	$schema = array(
		'FIELDS'		=> array(
			'group_id'		=> array(
				'datatype'		=> 'INT(10)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'forum_id'		=> array(
				'datatype'		=> 'INT(10)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'read_forum'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'post_replies'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'post_topics'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			)
		),
		'PRIMARY KEY'	=> array('group_id', 'forum_id')
	);

	$db->create_table('forum_perms', $schema) or error('Unable to create forum_perms table', __FILE__, __LINE__, $db->error());


	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'forum_name'	=> array(
				'datatype'		=> 'VARCHAR(80)',
				'allow_null'	=> false,
				'default'		=> '\'New forum\''
			),
			'forum_desc'	=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> true
			),
			'redirect_url'	=> array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> true
			),
			'moderators'	=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> true
			),
			'num_topics'	=> array(
				'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'num_posts'		=> array(
				'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'last_post'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'last_post_id'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'last_poster'	=> array(
				'datatype'		=> 'VARCHAR(200)',
				'allow_null'	=> true
			),
			'sort_by'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'disp_position'	=> array(
				'datatype'		=> 'INT(10)',
				'allow_null'	=> false,
				'default'		=>	'0'
			),
			'cat_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=>	'0'
			)
		),
		'PRIMARY KEY'	=> array('id')
	);

	$db->create_table('forums', $schema) or error('Unable to create forums table', __FILE__, __LINE__, $db->error());


	$schema = array(
		'FIELDS'		=> array(
			'g_id'						=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'g_title'					=> array(
				'datatype'		=> 'VARCHAR(50)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'g_user_title'				=> array(
				'datatype'		=> 'VARCHAR(50)',
				'allow_null'	=> true
			),
			'g_moderator'				=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'g_mod_edit_users'			=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'g_mod_rename_users'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'g_mod_change_passwords'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'g_mod_ban_users'			=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'g_read_board'				=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'g_view_users'				=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'g_post_replies'			=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'g_post_topics'				=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'g_edit_posts'				=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'g_delete_posts'			=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'g_delete_topics'			=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'g_set_title'				=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'g_search'					=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'g_search_users'			=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'g_send_email'				=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'g_post_flood'				=> array(
				'datatype'		=> 'SMALLINT(6)',
				'allow_null'	=> false,
				'default'		=> '30'
			),
			'g_search_flood'			=> array(
				'datatype'		=> 'SMALLINT(6)',
				'allow_null'	=> false,
				'default'		=> '30'
			),
			'g_email_flood'				=> array(
				'datatype'		=> 'SMALLINT(6)',
				'allow_null'	=> false,
				'default'		=> '60'
			),
			'g_locked'				=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			)
		),
		'PRIMARY KEY'	=> array('g_id')
	);

	$db->create_table('groups', $schema) or error('Unable to create groups table', __FILE__, __LINE__, $db->error());


	$schema = array(
		'FIELDS'		=> array(
			'user_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'ident'			=> array(
				'datatype'		=> 'VARCHAR(200)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'logged'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'idle'			=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'last_post'			=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'last_search'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
		),
		'UNIQUE KEYS'	=> array(
			'user_id_ident_idx'	=> array('user_id', 'ident')
		),
		'INDEXES'		=> array(
			'ident_idx'		=> array('ident'),
			'logged_idx'	=> array('logged')
		),
		'ENGINE'		=> 'HEAP'
	);

	if ($db_type == 'mysql' || $db_type == 'mysqli' || $db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb')
	{
		$schema['UNIQUE KEYS']['user_id_ident_idx'] = array('user_id', 'ident(25)');
		$schema['INDEXES']['ident_idx'] = array('ident(25)');
	}

	if ($db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb')
		$schema['ENGINE'] = 'InnoDB';

	$db->create_table('online', $schema) or error('Unable to create online table', __FILE__, __LINE__, $db->error());


	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'poster'		=> array(
				'datatype'		=> 'VARCHAR(200)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'poster_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'poster_ip'		=> array(
				'datatype'		=> 'VARCHAR(39)',
				'allow_null'	=> true
			),
			'poster_email'	=> array(
				'datatype'		=> 'VARCHAR(80)',
				'allow_null'	=> true
			),
			'message'		=> array(
				'datatype'		=> 'MEDIUMTEXT',
				'allow_null'	=> true
			),
			'hide_smilies'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'posted'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'edited'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'edited_by'		=> array(
				'datatype'		=> 'VARCHAR(200)',
				'allow_null'	=> true
			),
			'topic_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			)
		),
		'PRIMARY KEY'	=> array('id'),
		'INDEXES'		=> array(
			'topic_id_idx'	=> array('topic_id'),
			'multi_idx'		=> array('poster_id', 'topic_id')
		)
	);

	$db->create_table('posts', $schema) or error('Unable to create posts table', __FILE__, __LINE__, $db->error());


	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'rank'			=> array(
				'datatype'		=> 'VARCHAR(50)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'min_posts'		=> array(
				'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			)
		),
		'PRIMARY KEY'	=> array('id')
	);

	$db->create_table('ranks', $schema) or error('Unable to create ranks table', __FILE__, __LINE__, $db->error());


	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'post_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'topic_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'forum_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'reported_by'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'created'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'message'		=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> true
			),
			'zapped'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'zapped_by'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			)
		),
		'PRIMARY KEY'	=> array('id'),
		'INDEXES'		=> array(
			'zapped_idx'	=> array('zapped')
		)
	);

	$db->create_table('reports', $schema) or error('Unable to create reports table', __FILE__, __LINE__, $db->error());


	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'ident'			=> array(
				'datatype'		=> 'VARCHAR(200)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'search_data'	=> array(
				'datatype'		=> 'MEDIUMTEXT',
				'allow_null'	=> true
			)
		),
		'PRIMARY KEY'	=> array('id'),
		'INDEXES'		=> array(
			'ident_idx'	=> array('ident')
		)
	);

	if ($db_type == 'mysql' || $db_type == 'mysqli' || $db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb')
		$schema['INDEXES']['ident_idx'] = array('ident(8)');

	$db->create_table('search_cache', $schema) or error('Unable to create search_cache table', __FILE__, __LINE__, $db->error());


	$schema = array(
		'FIELDS'		=> array(
			'post_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'word_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'subject_match'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			)
		),
		'INDEXES'		=> array(
			'word_id_idx'	=> array('word_id'),
			'post_id_idx'	=> array('post_id')
		)
	);

	$db->create_table('search_matches', $schema) or error('Unable to create search_matches table', __FILE__, __LINE__, $db->error());


	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'word'			=> array(
				'datatype'		=> 'VARCHAR(20)',
				'allow_null'	=> false,
				'default'		=> '\'\'',
				'collation'		=> 'bin'
			)
		),
		'PRIMARY KEY'	=> array('word'),
		'INDEXES'		=> array(
			'id_idx'	=> array('id')
		)
	);

	if ($db_type == 'sqlite')
	{
		$schema['PRIMARY KEY'] = array('id');
		$schema['UNIQUE KEYS'] = array('word_idx'	=> array('word'));
	}

	$db->create_table('search_words', $schema) or error('Unable to create search_words table', __FILE__, __LINE__, $db->error());


	$schema = array(
		'FIELDS'		=> array(
			'user_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'topic_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			)
		),
		'PRIMARY KEY'	=> array('user_id', 'topic_id')
	);

    $db->create_table('topic_subscriptions', $schema) or error('Unable to create topic subscriptions table', __FILE__, __LINE__, $db->error());


    $schema = array(
        'FIELDS'        => array(
            'user_id'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            ),
            'forum_id'        => array(
                'datatype'        => 'INT(10) UNSIGNED',
                'allow_null'    => false,
                'default'        => '0'
            )
        ),
        'PRIMARY KEY'    => array('user_id', 'forum_id')
    );
    
    $db->create_table('forum_subscriptions', $schema) or error('Unable to create forum subscriptions table', __FILE__, __LINE__, $db->error());


	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'poster'		=> array(
				'datatype'		=> 'VARCHAR(200)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'subject'		=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'posted'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'first_post_id'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'last_post'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'last_post_id'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'last_poster'	=> array(
				'datatype'		=> 'VARCHAR(200)',
				'allow_null'	=> true
			),
			'num_views'		=> array(
				'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'num_replies'	=> array(
				'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'closed'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'sticky'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'moved_to'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'forum_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			)
		),
		'PRIMARY KEY'	=> array('id'),
		'INDEXES'		=> array(
			'forum_id_idx'		=> array('forum_id'),
			'moved_to_idx'		=> array('moved_to'),
			'last_post_idx'		=> array('last_post'),
			'first_post_id_idx'	=> array('first_post_id')
		)
	);

	$db->create_table('topics', $schema) or error('Unable to create topics table', __FILE__, __LINE__, $db->error());


	$schema = array(
		'FIELDS'		=> array(
			'id'				=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'group_id'			=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '3'
			),
			'username'			=> array(
				'datatype'		=> 'VARCHAR(200)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'password'			=> array(
				'datatype'		=> 'VARCHAR(40)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'email'				=> array(
				'datatype'		=> 'VARCHAR(80)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'title'				=> array(
				'datatype'		=> 'VARCHAR(50)',
				'allow_null'	=> true
			),
			'realname'			=> array(
				'datatype'		=> 'VARCHAR(40)',
				'allow_null'	=> true
			),
			'url'				=> array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> true
			),
			'jabber'			=> array(
				'datatype'		=> 'VARCHAR(80)',
				'allow_null'	=> true
			),
			'icq'				=> array(
				'datatype'		=> 'VARCHAR(12)',
				'allow_null'	=> true
			),
			'msn'				=> array(
				'datatype'		=> 'VARCHAR(80)',
				'allow_null'	=> true
			),
			'aim'				=> array(
				'datatype'		=> 'VARCHAR(30)',
				'allow_null'	=> true
			),
			'yahoo'				=> array(
				'datatype'		=> 'VARCHAR(30)',
				'allow_null'	=> true
			),
			'location'			=> array(
				'datatype'		=> 'VARCHAR(30)',
				'allow_null'	=> true
			),
			'signature'			=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> true
			),
			'disp_topics'		=> array(
				'datatype'		=> 'TINYINT(3) UNSIGNED',
				'allow_null'	=> true
			),
			'disp_posts'		=> array(
				'datatype'		=> 'TINYINT(3) UNSIGNED',
				'allow_null'	=> true
			),
			'email_setting'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'notify_with_post'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'auto_notify'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'show_smilies'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'show_img'			=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'show_img_sig'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'show_avatars'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'show_sig'			=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'timezone'			=> array(
				'datatype'		=> 'FLOAT',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'dst'				=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'time_format'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'date_format'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'language'			=> array(
				'datatype'		=> 'VARCHAR(25)',
				'allow_null'	=> false,
				'default'		=> '\'English\''
			),
			'style'				=> array(
				'datatype'		=> 'VARCHAR(25)',
				'allow_null'	=> false,
				'default'		=> '\''.$db->escape($default_style).'\''
			),
			'num_posts'			=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'last_post'			=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'last_search'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'last_email_sent'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'registered'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'registration_ip'	=> array(
				'datatype'		=> 'VARCHAR(39)',
				'allow_null'	=> false,
				'default'		=> '\'0.0.0.0\''
			),
			'last_visit'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'admin_note'		=> array(
				'datatype'		=> 'VARCHAR(30)',
				'allow_null'	=> true
			),
			'activate_string'	=> array(
				'datatype'		=> 'VARCHAR(80)',
				'allow_null'	=> true
			),
			'activate_key'		=> array(
				'datatype'		=> 'VARCHAR(8)',
				'allow_null'	=> true
			),
		),
		'PRIMARY KEY'	=> array('id'),
		'UNIQUE KEYS'	=> array(
			'username_idx'		=> array('username')
		),
		'INDEXES'		=> array(
			'registered_idx'	=> array('registered')
		)
	);

	if ($db_type == 'mysql' || $db_type == 'mysqli' || $db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb')
		$schema['UNIQUE KEYS']['username_idx'] = array('username(25)');

	$db->create_table('users', $schema) or error('Unable to create users table', __FILE__, __LINE__, $db->error());
	
	
	/*---------- EvE-BB INSTALL TABLE CONSTRUCTION ---------*/
	
	//API Auth table, used to fetch/store API data so we can then get the more detailed info. It's also used for cross reference.
	$schema = array(
		'FIELDS'		=> array(
			'user_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'api_character_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'api_user_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'api_key'		=> array(
				'datatype'		=> 'VARCHAR(64)',
				'allow_null'	=> false
			),
			'cak_type' => array(
				'datatype' => 'INT(10) UNSIGNED',
				'default' => '0'
			)
		),
		'PRIMARY KEY'	=> array('api_character_id')
	);

	$db->create_table('api_auth', $schema) or error('Unable to create api table', __FILE__, __LINE__, $db->error());
	
	//API Character Details, used to store the "non array" data of a character - skills, attributes, titles, etc, will be stored on another table, but not atm.
	$schema = array(
		'FIELDS'		=> array(
			'user_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'character_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'character_name'		=> array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> true
			),
			'corp_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'corp_name'		=> array(
				'datatype'		=> 'VARCHAR(50)',
				'allow_null'	=> true
			),
			'ally_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'ally_name'		=> array(
				'datatype'		=> 'VARCHAR(50)',
				'allow_null'	=> true
			),
			'dob'		=> array(
				'datatype'		=> 'VARCHAR(20)',
				'allow_null'	=> true
			),
			'race'		=> array(
				'datatype'		=> 'VARCHAR(12)',
				'allow_null'	=> true
			),
			'blood_line'		=> array(
				'datatype'		=> 'VARCHAR(24)',
				'allow_null'	=> true
			),
			'ancestry'		=> array(
				'datatype'		=> 'VARCHAR(24)',
				'allow_null'	=> true
			),
			'gender'		=> array(
				'datatype'	=> 'VARCHAR(6)',
				'allow_null'	=> true
			),
			'clone_name'		=> array(
				'datatype'		=> 'VARCHAR(24)',
				'allow_null'	=> true
			),
			'clone_sp'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true,
				'default' => '0'
			),
			'balance'		=> array(
				'datatype'		=> 'FLOAT',
				'allow_null'	=> true,
				'default' => '0.0'
			),
			'last_update'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default' => '0'
			),
			'roles'		=> array(
				'datatype'		=> 'VARCHAR(64)',
				'allow_null'	=> false,
				'default' => '0'
			),
			'active'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default' => '1'
			)
		),
		'PRIMARY KEY'	=> array('character_id'),
		'INDEXES'		=> array(
			'api_characters_user_id_idx'	=> array('user_id'),
			'api_characters_corp_id_idx'	=> array('corp_id'),
			'api_characters_ally_id_idx'	=> array('ally_id'),
			'api_characters_last_update_idx'	=> array('last_update')
		)
	);
	

	$db->create_table('api_characters', $schema) or error('Unable to create api table', __FILE__, __LINE__, $db->error());
	
	//API Selected Character - support for multiple characters being allowed.
	$schema = array(
		'FIELDS'		=> array(
			'user_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'character_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			)
		),
		'PRIMARY KEY'	=> array('user_id'),
		'INDEXES'		=> array(
			'api_selected_char_character_id_idx'	=> array('character_id')
		)
	);

	$db->create_table('api_selected_char', $schema) or error('Unable to create api table', __FILE__, __LINE__, $db->error());
	
	//API Groups -reference table to assign certain groupID's to corpID's.
	$schema = array(
		'FIELDS'		=> array(
			'id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'group_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'type'		=> array(
				'datatype'		=> 'TINYINT(4) UNSIGNED',
				'allow_null'	=> false,
				'default' => '0'
			),
			'priority'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default' => '1'
			),
			'role'		=> array(
				'datatype'		=> 'VARCHAR(64)',
				'allow_null'	=> false,
				'default' => '0'
			)
		),
		'PRIMARY KEY'	=> array('id', 'group_id', 'role')
	);

	$db->create_table('api_groups', $schema) or error('Unable to create api table', __FILE__, __LINE__, $db->error());
	
	//Allowed corps.
	$schema = array(
		'FIELDS'		=> array(
			'corporationID'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'corporationName'		=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> false
			),
			'ticker'		=> array(
				'datatype'		=> 'VARCHAR(10)',
				'allow_null'	=> false
			),
			'ceoID'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'ceoName'		=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> false
			),
			'description'		=> array(
				'datatype'		=> 'VARCHAR(3000)',
				'allow_null'	=> true
			),
			'url'				=> array(
				'datatype'		=> 'VARCHAR(150)',
				'allow_null'	=> true
			),
			'allianceID'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default' => '0'
			),
			'taxRate'		=> array(
				'datatype'		=> 'FLOAT',
				'allow_null'	=> false
			),
			'allowed'		=> array(
				'datatype'		=> 'TINYINT(1) UNSIGNED',
				'allow_null'	=> false,
				'default' => '0'
			)
		),
		'PRIMARY KEY'	=> array('corporationID'),
		'INDEXES'		=> array(
			'api_allowed_corps_alliance_id_idx'	=> array('allianceID'),
			'api_allowed_corps_allowed_idx'	=> array('allowed')
		)
	);

	$db->create_table('api_allowed_corps', $schema) or error('Unable to create api table', __FILE__, __LINE__, $db->error());
	
	//Allowed corps.
	$schema = array(
		'FIELDS'		=> array(
			'allianceID'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'allianceName'		=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> false
			),
			'ticker'		=> array(
				'datatype'		=> 'VARCHAR(10)',
				'allow_null'	=> false
			),
			'executorCorpID'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'memberCount'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default' => '0'
			),
			'startDate'		=> array(
				'datatype'		=> 'VARCHAR(20)',
				'allow_null'	=> true
			),
			'allowed'		=> array(
				'datatype'		=> 'TINYINT(1) UNSIGNED',
				'allow_null'	=> false,
				'default' => '0'
			)
		),
		'PRIMARY KEY'	=> array('allianceID'),
		'INDEXES'		=> array(
			'api_allowed_alliance_allowed_idx'	=> array('allowed')
		)
	);

	$db->create_table('api_allowed_alliance', $schema) or error('Unable to create api table', __FILE__, __LINE__, $db->error());
	
	//Alliance member corps
	$schema = array(
		'FIELDS'		=> array(
			'allianceID'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'corporationID'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'startDate'		=> array(
				'datatype'		=> 'VARCHAR(20)',
				'allow_null'	=> false
			)
		),
		'PRIMARY KEY'	=> array('corporationID')
	);

	$db->create_table('api_alliance_corps', $schema) or error('Unable to create api table', __FILE__, __LINE__, $db->error());
	
	//Alliance list.
	$schema = array(
		'FIELDS'		=> array(
			'allianceID'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'name'		=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> false
			),
			'shortName'		=> array(
				'datatype'		=> 'VARCHAR(10)',
				'allow_null'	=> false
			),
			'executorCorpID'		=> array(
				'datatype'		=> 'INT (10) UNSIGNED',
				'allow_null'	=> false
			),
			'memberCount'		=> array(
				'datatype'		=> 'INT (10) UNSIGNED',
				'allow_null'	=> false
			),
			'startDate'		=> array(
				'datatype'		=> 'VARCHAR(20)',
				'allow_null'	=> false
			)
		),
		'PRIMARY KEY'	=> array('allianceID')
	);

	$db->create_table('api_alliance_list', $schema) or error('Unable to create api table', __FILE__, __LINE__, $db->error());
	
	//Skill Types
	$schema = array(
		'FIELDS'		=> array(
			'typeID'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'typeName'		=> array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> false
			),
			'description'		=> array(
				'datatype'		=> 'VARCHAR(3000)',
				'allow_null'	=> false
			),
			'groupName'		=> array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> false
			)
		),
		'PRIMARY KEY'		=> array('typeID')
	);

	$db->create_table('api_skill_types', $schema) or error('Unable to create skill types table', __FILE__, __LINE__, $db->error());
	
	//SkillQueue table.
	$schema = array(
		'FIELDS'		=> array(
			'character_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'queuePosition'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'typeID'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'level'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'startSP'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'endSP'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'startTime'		=> array(
				'datatype'		=> 'VARCHAR(25)',
				'allow_null'	=> false
			),
			'endTime'		=> array(
				'datatype'		=> 'VARCHAR(25)',
				'allow_null'	=> false
			),
			'last_update'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			)
		),
		'PRIMARY KEY'		=> array('character_id', 'typeID', 'queuePosition')
	);

	$db->create_table('api_skill_queue', $schema) or error('Unable to create skill queue table', __FILE__, __LINE__, $db->error());
	
	//Multiple Group Table.
	$schema = array(
		'FIELDS'		=> array(
			'user_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'group_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			)
		),
		'PRIMARY KEY'		=> array('user_id', 'group_id')
	);

	$db->create_table('groups_users', $schema) or error('Unable to create groups table', __FILE__, __LINE__, $db->error());
	
	//Teamspeak3 table
	$schema = array(
		'FIELDS'		=> array(
			'user_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'username'		=> array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> false
			),
			'token'		=> array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> false
			)
		),
		'PRIMARY KEY'	=> array('user_id')
	);

	$db->create_table('teamspeak3', $schema) or error('Unable to create teamspeak3 table', __FILE__, __LINE__, $db->error());
	
	//Session table
	$schema = array(
		'FIELDS'		=> array(
			'user_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'token'		=> array(
				'datatype'		=> 'VARCHAR(32)',
				'allow_null'	=> false
			),
			'stamp'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'length'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false
			),
			'ip'				=> array(
				'datatype'		=> 'VARCHAR(32)',
				'allow_null'	=> false
			)
		),
		'PRIMARY KEY'	=> array('user_id')
	);

	$db->create_table('session', $schema) or error('Unable to create session table', __FILE__, __LINE__, $db->error());
		
	/*---------- EvE-BB INSTALL TABLE CONSTRUCTION ---------*/

	$now = time();

	// Insert the four preset groups
	$db->query('INSERT INTO '.$db->prefix.'groups ('.($db_type != 'pgsql' ? 'g_id, ' : '').'g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood) VALUES('.($db_type != 'pgsql' ? '1, ' : '').'\''.$db->escape($lang_install['Administrators']).'\', \''.$db->escape($lang_install['Administrator']).'\', 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0)') or error('Unable to add group', __FILE__, __LINE__, $db->error());
//
    $db->query('INSERT INTO '.$db->prefix.'groups ('.($db_type != 'pgsql' ? 'g_id, ' : '').'g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood) VALUES('.($db_type != 'pgsql' ? '2, ' : '').'\''.$db->escape($lang_install['Moderators']).'\', \''.$db->escape($lang_install['Moderator']).'\', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0)') or error('Unable to add group', __FILE__, __LINE__, $db->error());
//
    $db->query('INSERT INTO '.$db->prefix.'groups ('.($db_type != 'pgsql' ? 'g_id, ' : '').'g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood) VALUES('.($db_type != 'pgsql' ? '3, ' : '').'\''.$db->escape($lang_install['Guests']).'\', NULL, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 1, 1, 0, 60, 30, 0)') or error('Unable to add group', __FILE__, __LINE__, $db->error());
//
    $db->query('INSERT INTO '.$db->prefix.'groups ('.($db_type != 'pgsql' ? 'g_id, ' : '').'g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood) VALUES('.($db_type != 'pgsql' ? '4, ' : '').'\''.$db->escape($lang_install['Members']).'\', NULL, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 60, 30, 60)') or error('Unable to add group', __FILE__, __LINE__, $db->error());
    
	// Insert guest and first admin user
	$db->query('INSERT INTO '.$db_prefix.'users (group_id, username, password, email) VALUES(3, \''.$db->escape($lang_install['Guest']).'\', \''.$db->escape($lang_install['Guest']).'\', \''.$db->escape($lang_install['Guest']).'\')')
		or error('Unable to add guest user. Please check your configuration and try again', __FILE__, __LINE__, $db->error());

	$db->query('INSERT INTO '.$db_prefix.'users (group_id, username, password, email, language, style, num_posts, last_post, registered, registration_ip, last_visit) VALUES(1, \''.$db->escape($username).'\', \''.pun_hash($password1).'\', \''.$email.'\', \''.$db->escape($default_lang).'\', \''.$db->escape($default_style).'\', 1, '.$now.', '.$now.', \''.get_remote_address().'\', '.$now.')')
		or error('Unable to add administrator user. Please check your configuration and try again', __FILE__, __LINE__, $db->error());
		
	/*---------- EvE-BB INSTALL TABLE DATA ---------*/
	//Our function to handle this relies on $db being fully formed, so we do this manually.
	$db->query('INSERT INTO '.$db_prefix."api_auth (user_id, api_character_id, api_user_id, api_key) VALUES(2, ".$api_character_id.", ".$api_user_id.", '".$db->escape($api_key)."')")
		or error('Unable to add administrator user api data. Please check your configuration and try again', __FILE__, __LINE__, $db->error());
	
	$char;
	if (!$char = update_character_sheet(2, null, $char_sheet)) {
		error("Unable to fetch character data from the API server. Please verify it is currently online and try again.", __FILE__, __LINE__, $db->error());
	} //End if.
	$corp;
	if (!$corp = add_corp_from_character($char)) {
		error("Unable to fetch corporation data from the API server. Please verify it is currently online and try again.", __FILE__, __LINE__, $db->error());
	} //End if.
	
	//We try, but don't care past this point.
	update_characters(2, $cak);
	
	select_character(2, $char);
	
	//Now we get the (massive) skill types SQL and insert it.
	if (!file_exists(PUN_ROOT.'install/api_skill_types_sql.php')) {
		error('Can not find the install directory and it\'s associated data.');
	} //End if.
	require(PUN_ROOT.'install/api_skill_types_sql.php');
	foreach ($api_skill_types as $sql) {
		$db->query($sql) or error('Unable to load the skills into the database.<br/>'.$sql, __FILE__, __LINE__, $db->error());
	} //End foreach().
		
	/*---------- EvE-BB INSTALL TABLE DATA ---------*/

	// Enable/disable avatars depending on file_uploads setting in PHP configuration
	$avatars = in_array(strtolower(@ini_get('file_uploads')), array('on', 'true', '1')) ? 1 : 0;

	// Insert config data
	$config = array(
		'o_cur_version'				=> "'".FORUM_VERSION."'",
		'o_database_revision'		=> "'".FORUM_DB_REVISION."'",
		'o_searchindex_revision'	=> "'".FORUM_SI_REVISION."'",
		'o_parser_revision'			=> "'".FORUM_PARSER_REVISION."'",
		'o_board_title'				=> "'".$db->escape($title)."'",
		'o_board_desc'				=> "'".$db->escape($description)."'",
		'o_default_timezone'		=> "'0'",
		'o_time_format'				=> "'H:i:s'",
		'o_date_format'				=> "'Y-m-d'",
		'o_timeout_visit'			=> "'1800'",
		'o_timeout_online'			=> "'300'",
		'o_redirect_delay'			=> "'1'",
		'o_show_version'			=> "'0'",
		'o_show_user_info'			=> "'1'",
		'o_show_post_count'			=> "'1'",
		'o_signatures'				=> "'1'",
		'o_smilies'					=> "'1'",
		'o_smilies_sig'				=> "'1'",
		'o_make_links'				=> "'1'",
		'o_default_lang'			=> "'".$db->escape($default_lang)."'",
		'o_default_style'			=> "'".$db->escape($default_style)."'",
		'o_allow_style' 			=> "'1'",
		'o_default_user_group'		=> "'4'",
		'o_topic_review'			=> "'15'",
		'o_disp_topics_default'		=> "'30'",
		'o_disp_posts_default'		=> "'25'",
		'o_indent_num_spaces'		=> "'4'",
		'o_quote_depth'				=> "'3'",
		'o_quickpost'				=> "'1'",
		'o_users_online'			=> "'1'",
		'o_censoring'				=> "'0'",
		'o_ranks'					=> "'1'",
		'o_show_dot'				=> "'0'",
		'o_topic_views'				=> "'1'",
		'o_quickjump'				=> "'1'",
		'o_gzip'					=> "'0'",
		'o_additional_navlinks'		=> "''",
		'o_report_method'			=> "'0'",
		'o_regs_report'				=> "'0'",
		'o_default_email_setting'	=> "'1'",
		'o_mailing_list'			=> "'".$email."'",
		'o_avatars'					=> "'".$avatars."'",
		'o_avatars_dir'				=> "'img/avatars'",
		'o_avatars_width'			=> "'60'",
		'o_avatars_height'			=> "'60'",
		'o_avatars_size'			=> "'10240'",
		'o_search_all_forums'		=> "'1'",
		'o_base_url'				=> "'".$db->escape($base_url)."'",
		'o_admin_email'				=> "'".$email."'",
		'o_webmaster_email'			=> "'".$email."'",
		'o_forum_subscriptions'        => "'1'",
		'o_topic_subscriptions'			=> "'1'",
		'o_smtp_host'				=> "NULL",
		'o_smtp_user'				=> "NULL",
		'o_smtp_pass'				=> "NULL",
		'o_smtp_ssl'				=> "'0'",
		'o_regs_allow'				=> "'1'",
		'o_regs_verify'				=> "'0'",
		'o_announcement'			=> "'0'",
		'o_announcement_message'    => "'".$db->escape($lang_install['Announcement'])."'",
		'o_rules'					=> "'0'",
		'o_rules_message'            => "'".$db->escape($lang_install['Rules'])."'",
		'o_maintenance'				=> "'0'",
		'o_maintenance_message'        => "'".$db->escape($lang_install['Maintenance message'])."'",
		'o_default_dst'				=> "'0'",
		'o_feed_type'				=> "'2'",
		'o_feed_ttl'                => "'0'",
		'p_message_bbcode'			=> "'1'",
		'p_message_img_tag'			=> "'1'",
		'p_message_all_caps'		=> "'1'",
		'p_subject_all_caps'		=> "'1'",
		'p_sig_all_caps'			=> "'1'",
		'p_sig_bbcode'				=> "'1'",
		'p_sig_img_tag'				=> "'0'",
		'p_sig_length'				=> "'400'",
		'p_sig_lines'				=> "'4'",
		'p_allow_banned_email'		=> "'1'",
		'p_allow_dupe_email'		=> "'0'",
		'p_force_guest_email'		=> "'1'",
		/*---------- EvE-BB INSTALL Options ---------*/
		'o_eve_enabled'		=> "'1'",
		'o_eve_use_iga'		=> "'1'",
		'o_eve_use_corp_name'		=> "'1'",
		'o_eve_use_corp_ticker'		=> "'0'",
		'o_eve_use_ally_name'		=> "'1'",
		'o_eve_use_ally_ticker'		=> "'0'",
		'o_eve_cache_char_sheet'		=> "'1'", //No idea why I put this in... Leaving it in for now until I remember it's purpose.
		'o_eve_cache_char_sheet_interval'		=> "'4'", //4 hours
		'o_eve_rules_interval'		=> "'4'", //4 hours
		'o_eve_auth_interval'		=> "'4'", //4 hours
		'o_eve_use_cron'		=> "'1'", //Default to yes as of 1.1.2
		'o_eve_use_banner'		=> "'1'",
		'o_eve_restrict_reg_corp'		=> "'1'",
		'o_eve_restricted_group'		=> "'4'",
		'o_eve_last_auth_check'		=> "'".time()."'",
		'o_eve_banner' => "'eve-bb-blank-1.jpg'",
		'o_eve_banner_dir'	=> "'img/banners'",
		'o_eve_banner_size'	=> "'819200'",
		'o_eve_banner_width'	=> "'1000'",
		'o_eve_banner_height'	=> "'150'",
		'o_eve_banner_text_enable' => "'1'",
		'o_eve_max_groups' => "'100'",
		'o_hide_stats' => "'0'",
		//New since 1.1.2+
		'o_eve_cur_version' => "'".EVE_BB_VERSION."'",
		'o_eve_cak_mask' => "'33947656'",
		'o_eve_cak_type' => "'1'",
		'o_eve_use_image_server' => "'0'",
		'o_eve_char_pic_size' => "'128'",
		'o_use_fopen' => (defined('EVEBB_CURL')) ? "'0'" : "'1'"
		/*---------- EvE-BB INSTALL Options ---------*/
	);

	foreach ($config as $conf_name => $conf_value)
	{
		$sql = 'INSERT INTO '.$db_prefix.'config (conf_name, conf_value) VALUES(\''.$conf_name.'\', '.$conf_value.')';
		$db->query($sql)
			or error('Unable to insert into table '.$db_prefix.'config. Please check your configuration and try again<br />'.$sql, __FILE__, __LINE__, $db->error());
	}

	// Insert some other default data
	$subject = $lang_install['Test post'];
	$message = $lang_install['Message'];

	$db->query('INSERT INTO '.$db_prefix.'ranks (rank, min_posts) VALUES(\''.$db->escape($lang_install['New member']).'\', 0)')
		or error('Unable to insert into table '.$db_prefix.'ranks. Please check your configuration and try again', __FILE__, __LINE__, $db->error());

	$db->query('INSERT INTO '.$db_prefix.'ranks (rank, min_posts) VALUES(\''.$db->escape($lang_install['Member']).'\', 10)')
		or error('Unable to insert into table '.$db_prefix.'ranks. Please check your configuration and try again', __FILE__, __LINE__, $db->error());

	$db->query('INSERT INTO '.$db_prefix.'categories (cat_name, disp_position) VALUES(\''.$db->escape($lang_install['Test category']).'\', 1)')
		or error('Unable to insert into table '.$db_prefix.'categories. Please check your configuration and try again', __FILE__, __LINE__, $db->error());

	$db->query('INSERT INTO '.$db_prefix.'forums (forum_name, forum_desc, num_topics, num_posts, last_post, last_post_id, last_poster, disp_position, cat_id) VALUES(\''.$db->escape($lang_install['Test forum']).'\', \''.$db->escape($lang_install['This is just a test forum']).'\', 1, 1, '.$now.', 1, \''.$db->escape($username).'\', 1, 1)')
		or error('Unable to insert into table '.$db_prefix.'forums. Please check your configuration and try again', __FILE__, __LINE__, $db->error());

	$db->query('INSERT INTO '.$db_prefix.'topics (poster, subject, posted, first_post_id, last_post, last_post_id, last_poster, forum_id) VALUES(\''.$db->escape($username).'\', \''.$db->escape($subject).'\', '.$now.', 1, '.$now.', 1, \''.$db->escape($username).'\', 1)')
		or error('Unable to insert into table '.$db_prefix.'topics. Please check your configuration and try again', __FILE__, __LINE__, $db->error());

	$db->query('INSERT INTO '.$db_prefix.'posts (poster, poster_id, poster_ip, message, posted, topic_id) VALUES(\''.$db->escape($username).'\', 2, \''.get_remote_address().'\', \''.$db->escape($message).'\', '.$now.', 1)')
		or error('Unable to insert into table '.$db_prefix.'posts. Please check your configuration and try again', __FILE__, __LINE__, $db->error());
		
	/*---------- EvE-BB INSTALL TABLE INPUT ---------*/
	$db->query('INSERT INTO '.$db->prefix.'forum_perms(	group_id, forum_id, read_forum, post_replies, post_topics) VALUES(2,1,1,1,1)')
		or error('Unable to insert into table '.$db_prefix.'forum_perms. Please check your configuration and try again', __FILE__, __LINE__, $db->error());
		
	$db->query('INSERT INTO '.$db->prefix.'forum_perms(	group_id, forum_id, read_forum, post_replies, post_topics) VALUES(3,1,1,0,0)')
		or error('Unable to insert into table '.$db_prefix.'forum_perms. Please check your configuration and try again', __FILE__, __LINE__, $db->error());
		
	$db->query('INSERT INTO '.$db->prefix.'forum_perms(	group_id, forum_id, read_forum, post_replies, post_topics) VALUES(4,1,1,1,1)')
		or error('Unable to insert into table '.$db_prefix.'forum_perms. Please check your configuration and try again', __FILE__, __LINE__, $db->error());
	/*---------- EvE-BB INSTALL TABLE INPUT ---------*/

	// Index the test post so searching for it works
	require PUN_ROOT.'include/search_idx.php';
	$pun_config['o_default_lang'] = $default_lang;
	update_search_index('post', 1, $message, $subject);

	$db->end_transaction();
	
	//Install our now-included mods - Private Messagin, Sub Forums, Attachments, RSS Feed and Poll Support.
	install_npms();
	install_subforum();
	install_attach(rtrim(dirname(__FILE__), '/\\') . DIRECTORY_SEPARATOR.'attachments/');
	install_feed();
	install_poll();


	$alerts = array();

	// Check if we disabled uploading avatars because file_uploads was disabled
	if ($avatars == '0')
		$alerts[] = $lang_install['Alert upload'];

	// Add some random bytes at the end of the cookie name to prevent collisions
	$cookie_name = 'pun_cookie_'.random_key(6, false, true);

	// Generate the config.php file data
	$config = generate_config_file();

	// Attempt to write config.php and serve it up for download if writing fails
	$written = false;
	if (is_writable(PUN_ROOT))
	{
		$fh = @fopen(PUN_ROOT.'config.php', 'wb');
		if ($fh)
		{
			fwrite($fh, $config);
			fclose($fh);

			$written = true;
		}
	}

	//All has gone well! Now lets send them their login details.
	if (!function_exists('pun_mail')) {
		include(PUN_ROOT.'include/email.php');
	} //End if.

	//Try and send it via localhost.
	$pun_config['o_smtp_host'] = "localhost";
	$pun_config['o_smtp_user'] = "";
	$pun_config['o_smtp_pass'] = "";
	
	pun_mail($email, 'Your new EveBB login details',
"Your new EveBB install has successfully completed!

Your Details

Username: ".$username."

Password: ".$password1."

We hope you enjoy your new EveBB install!");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $lang_install['FluxBB Installation'] ?></title>
<link rel="stylesheet" type="text/css" href="style/<?php echo $default_style ?>.css" />
</head>
<body>

<div id="puninstall" class="pun">
<div class="top-box"><div><!-- Top Corners --></div></div>
<div class="punwrap">

<div id="brdheader" class="block">
	<div class="box">
		<div id="brdtitle" class="inbox">
			<h1><span><?php echo sprintf($lang_install['FluxBB Installation'], EVE_BB_VERSION); ?></span></h1>
			<div id="brddesc"><p><?php echo $lang_install['FluxBB has been installed'] ?></p></div>
		</div>
	</div>
</div>

<div id="brdmain">

<div class="blockform">
	<h2><span><?php echo $lang_install['Final instructions'] ?></span></h2>
	<div class="box">
<?php

if (!$written)
{

?>
		<form method="post" action="install.php">
			<div class="inform">
				<div class="forminfo">
					<p><?php echo $lang_install['Info 17'] ?></p>
					<p><?php echo $lang_install['Info 18'] ?></p>
					<p><?php echo $lang_install['Evebb_details'] ?></p>
					<br/>
					<p>
					<?php echo $lang_install['Evebb_username'] ?> <?php echo $username; ?><br/>
					<?php echo $lang_install['Evebb_password'] ?> <?php echo $password1; ?>
					</p>
				</div>
				<input type="hidden" name="generate_config" value="1" />
				<input type="hidden" name="db_type" value="<?php echo $db_type; ?>" />
				<input type="hidden" name="db_host" value="<?php echo $db_host; ?>" />
				<input type="hidden" name="db_name" value="<?php echo pun_htmlspecialchars($db_name); ?>" />
				<input type="hidden" name="db_username" value="<?php echo pun_htmlspecialchars($db_username); ?>" />
				<input type="hidden" name="db_password1" value="<?php echo pun_htmlspecialchars($db_password); ?>" />
				<input type="hidden" name="db_prefix" value="<?php echo pun_htmlspecialchars($db_prefix); ?>" />
				<input type="hidden" name="cookie_name" value="<?php echo pun_htmlspecialchars($cookie_name); ?>" />
				<input type="hidden" name="cookie_seed" value="<?php echo pun_htmlspecialchars($cookie_seed); ?>" />

<?php if (!empty($alerts)): ?>				<div class="forminfo error-info">
					<ul class="error-list">
<?php

foreach ($alerts as $cur_alert)
	echo "\t\t\t\t\t".'<li>'.$cur_alert.'</li>'."\n";
?>
					</ul>
				</div>
<?php endif; ?>			</div>
			<p class="buttons"><input type="submit" value="<?php echo $lang_install['Download config.php file'] ?>" /></p>
		</form>

<?php

}
else
{

?>
		<div class="fakeform">
			<div class="inform">
				<div class="forminfo">
					<p><?php echo $lang_install['FluxBB fully installed'] ?></p>
					<p><?php echo $lang_install['Evebb_details'] ?></p>
					<p>
					<?php echo $lang_install['Evebb_username'] ?> <?php echo $username; ?><br/>
					<?php echo $lang_install['Evebb_password'] ?> <?php echo $password1; ?>
					</p>
				</div>
			</div>
		</div>
<?php

}

?>
	</div>
</div>

</div>

</div>
<div class="end-box"><div><!-- Bottom Corners --></div></div>
</div>

</body>
</html>
<?php

}

/**
 * This massively long function makes the DB ready for sub forums.
 */
function install_subforum() {
	global $db;

	$db->add_field('forums', 'parent_forum_id', 'INT', true, 0);
} //End install_subform().

/**
 * Installs the tables for the Private Messaging System.
 */
function install_npms() {
	global $db, $db_type, $pun_config;

	
	$schema = array(
		'FIELDS'		=> array(
			'bl_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'bl_user_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'bl_user'		=> array(
				'datatype'		=> 'VARCHAR(200)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
		),
		'INDEXES'		=> array(
			'bl_id_idx'	=> array('bl_id'),
			'bl_user_id_idx'	=> array('bl_user_id')
		)
	);

	$db->create_table('pms_new_block', $schema) or error('Unable to create pms_new_block table', __FILE__, __LINE__, $db->error());

	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'poster'		=> array(
				'datatype'		=> 'VARCHAR(200)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'poster_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'poster_ip'		=> array(
				'datatype'		=> 'VARCHAR(39)',
				'allow_null'	=> true
			),
			'message'		=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> true
			),
			'hide_smilies'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'posted'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'edited'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'edited_by'		=> array(
				'datatype'		=> 'VARCHAR(200)',
				'allow_null'	=> true
			),
			'post_seen'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'post_new'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'topic_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			)
		),
		'PRIMARY KEY'	=> array('id'),
		'INDEXES'		=> array(
			'topic_id_idx'	=> array('topic_id'),
			'multi_idx'		=> array('poster_id', 'topic_id')
		)
	);

	$db->create_table('pms_new_posts', $schema) or error('Unable to create pms_new_posts table', __FILE__, __LINE__, $db->error());

	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'topic'		=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'starter'		=> array(
				'datatype'		=> 'VARCHAR(200)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'starter_id'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'to_user'		=> array(
				'datatype'		=> 'VARCHAR(200)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'to_id'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'replies'	=> array(
				'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'last_posted'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'last_poster'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'see_st'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'see_to'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'topic_st'		=> array(
				'datatype'		=> 'TINYINT(4)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'topic_to'		=> array(
				'datatype'		=> 'TINYINT(4)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
		),
		'PRIMARY KEY'	=> array('id'),
		'INDEXES'		=> array(
			'multi_idx_st'		=> array('starter_id', 'topic_st'),
			'multi_idx_to'		=> array('to_id', 'topic_to')
		)
	);

	$db->create_table('pms_new_topics', $schema) or error('Unable to create pms_new_topics table', __FILE__, __LINE__, $db->error());

	$db->add_field('groups', 'g_pm', 'TINYINT(1)', false, 1) or error('Unable to add g_pm field', __FILE__, __LINE__, $db->error());
	$db->add_field('groups', 'g_pm_limit', 'INT(10) UNSIGNED', false, 100) or error('Unable to add g_pm_limit field', __FILE__, __LINE__, $db->error());

	$db->add_field('users', 'messages_enable', 'TINYINT(1)', false, 1) or error('Unable to add messages_enable field', __FILE__, __LINE__, $db->error());
	$db->add_field('users', 'messages_email', 'TINYINT(1)', false, 0) or error('Unable to add messages_email field', __FILE__, __LINE__, $db->error());
	$db->add_field('users', 'messages_flag', 'TINYINT(1)', false, 0) or error('Unable to add messages_flag field', __FILE__, __LINE__, $db->error());
	$db->add_field('users', 'messages_new', 'INT(10) UNSIGNED', false, 0) or error('Unable to add messages_new field', __FILE__, __LINE__, $db->error());
	$db->add_field('users', 'messages_all', 'INT(10) UNSIGNED', false, 0) or error('Unable to add messages_all field', __FILE__, __LINE__, $db->error());
	$db->add_field('users', 'pmsn_last_post', 'INT(10) UNSIGNED', true) or error('Unable to add pmsn_last_post field', __FILE__, __LINE__, $db->error());

	$db->query('UPDATE '.$db->prefix.'groups SET g_pm_limit=0 WHERE g_id='.PUN_ADMIN) or error('Unable to merge groups', __FILE__, __LINE__, $db->error());

	// Insert config data
	$config = array(
		'o_pms_enabled'		=> '1',
		'o_pms_min_kolvo'	=> '0',
		'o_pms_flasher'		=> '0',
	);
	
	while (list($conf_name, $conf_value) = @each($config))
	{
    if (!array_key_exists($conf_name, $pun_config))
			$db->query('INSERT INTO '.$db->prefix."config (conf_name, conf_value) VALUES('$conf_name', $conf_value)")
				or error('Unable to insert into table '.$db->prefix.'config. Please check your configuration and try again.');
	}

	// Delete all .php files in the cache (someone might have visited the forums while we were updating and thus, generated incorrect cache files)
	forum_clear_cache();
} //End install_npms().

/**
 * Installs the database for the attachements.
 */
function install_attach($basepath='')
{
	global $db, $db_type, $pun_config, $mod_version;
	//include PUN_ROOT.'include/attach/attach_incl.php';

	//first check so that the path seems reasonable
	if(!((substr($basepath,0,1) == '/' || substr($basepath,1,1) == ':') && substr($basepath,-1) == '/'))
		error('The pathname specified doesn\'t comply with the rules set. Go back and make sure that it\'s the complete path, and that it ends with a slash and that it either start with a slash (example: "/home/username/attachments/", on *nix servers (unix, linux, bsd, solaris etc.)) or a driveletter (example: "C:/webpages/attachments/" on windows servers)');

	// create the files table
	$schema_files = array(
			'FIELDS'			=> array(
					'id'				=> array(
							'datatype'			=> 'SERIAL',
							'allow_null'    	=> false
					),
					'owner'	=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> false,
							'default'		=> '0'
					),
					'post_id'	=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> false,
							'default'		=> '0'
					),
					'filename'	=> array(
							'datatype'			=> 'VARCHAR(255)',
							'allow_null'		=> false,
					),
					'extension'		=> array(
							'datatype'			=> 'VARCHAR(64)',
							'allow_null'		=> false,
					),
					'mime'	=> array(
							'datatype'			=> 'VARCHAR(64)',
							'allow_null'		=> false
					),
					'location'	=> array(
							'datatype'			=> 'TEXT',
							'allow_null'		=> false
					),
					'size'	=> array(
							'datatype'		=> 'INT(10)',
							'allow_null'	=> false,
							'default'		=> '0'
					),
					'downloads'	=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> false,
							'default'		=> '0'
					)
			),
			'PRIMARY KEY'		=> array('id'),
	);
	
	$db->create_table('attach_2_files', $schema_files) or error('Unable to create table "attach_2_files"', __FILE__, __LINE__, $db->error());
	
	
	// create the files table
	$schema_rules = array(
			'FIELDS'			=> array(
					'id'				=> array(
							'datatype'			=> 'SERIAL',
							'allow_null'    	=> false
					),
					'forum_id'	=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> false,
							'default'		=> '0'
					),
					'group_id'	=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> false,
							'default'		=> '0'
					),
					'rules'	=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> false,
							'default'		=> '0'
					),
					'size'		=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> false,
							'default'		=> '0'
					),
					'per_post'	=> array(
							'datatype'			=> 'TINYINT(4)',
							'allow_null'		=> false,
							'default'		=> '1'
					),
					'file_ext'	=> array(
							'datatype'			=> 'TEXT',
							'allow_null'		=> false
					),
			),
			'PRIMARY KEY'		=> array('id'),
	);
	
	$db->create_table('attach_2_rules', $schema_rules) or error('Unable to create table "attach_2_rules"', __FILE__, __LINE__, $db->error());
	
	//ok path could be correct, try to make a subfolder :D
	$newname = attach_generate_pathname($basepath);
	if(!attach_create_subfolder($newname,$basepath))
		error('Unable to create new subfolder with name "'.$newname.'", make sure php has write access to that folder!',__FILE__,__LINE__);
	
		
	// ok, add the stuff needed in the config cache
	$attach_config = array(	'attach_always_deny'	=>	'html"htm"php"php3"php4"php5"exe"com"bat',
							'attach_basefolder'		=>	$basepath,
							'attach_create_orphans'	=>	'1',
							'attach_cur_version'	=>	$mod_version,
							'attach_icon_folder'	=>	'img/attach/',
							'attach_icon_extension'	=>	'txt"log"doc"pdf"wav"mp3"ogg"avi"mpg"mpeg"png"jpg"jpeg"gif"zip"rar"7z"gz"tar',
							'attach_icon_name'		=>	'text.png"text.png"doc.png"doc.png"audio.png"audio.png"audio.png"video.png"video.png"video.png"image.png"image.png"image.png"image.png"compress.png"compress.png"compress.png"compress.png"compress.png',
							'attach_max_size'		=>	'100000',
							'attach_subfolder'		=>	$newname,
							'attach_use_icon'		=>	'1');
	
	foreach($attach_config AS $key => $value)
		$db->query("INSERT INTO ".$db->prefix."config (conf_name, conf_value) VALUES ('$key', '".$db->escape($value)."')") or error('Unable to add column "'.$key.'" to config table', __FILE__, __LINE__, $db->error());


	// and now, update the cache...
	require_once PUN_ROOT.'include/cache.php';
	generate_config_cache();

} //End install_attach()

function attach_create_subfolder($newfolder='',$basepath){
		
	// check to see if that folder is there already, then just update the config ...
	if(!is_dir($basepath.$newfolder)){
		// if the folder doesn't exist, try to create it
		if(!mkdir($basepath.$newfolder,0775))
			error('Unable to create new subfolder with name \''.$basepath.$newfolder.'\' with mode 0755',__FILE__,__LINE__);
		// create a .htaccess and index.html file in the new subfolder
		if(!copy($basepath.'.htaccess', $basepath.$newfolder.'/.htaccess'))
			error('Unable to copy .htaccess file to new subfolder with name \''.$basepath.$newfolder.'\'',__FILE__,__LINE__);
		if(!copy($basepath.'index.html', $basepath.$newfolder.'/index.html'))
			error('Unable to copy index.html file to new subfolder with name \''.$basepath.$newfolder.'\'',__FILE__,__LINE__);
		// if the folder was created continue
	}
	// return true if everything has gone as planned, return false if the new folder could not be created (rights etc?)
	return true;
}

function attach_generate_pathname($storagepath=''){
	if(strlen($storagepath)!=0){
		//we have to check so that path doesn't exist already...
		$not_unique=true;
		while($not_unique){
			$newdir = attach_generate_pathname();
			if(!is_dir($storagepath.$newdir))return $newdir;
		}
	}else
		return substr(md5(time().'54£7 k3yw0rd, r3pl4ce |f U w4nt t0'),0,32);
}



function attach_generate_filename($storagepath, $messagelenght=0, $filesize=0){
	$not_unique=true;
	while($not_unique){
		$newfile = md5(attach_generate_pathname().$messagelenght.$filesize.'Some more salt keyworbs, change if you want to').'.attach';
		if(!is_file($storagepath.$newfile))return $newfile;
	}
}

/**
 * Installs the table for RSS feed support.
 */
function install_feed() {
	global $db;
	$db->query('CREATE TABLE '.$db->prefix.'feeds ( url varchar(255) NOT NULL default \'\', max int(11) NOT NULL default 0, closed tinyint(1) NOT NULL default 0, forum_id int(11) NOT NULL default 0, last_post INT(10) NOT NULL default 0, num_posts INT(10) NOT NULL default 0, PRIMARY KEY  (url) )' );
} //End install_feed().

/**
 * Installs the tables for poll support.
 */

function install_poll()
{
	global $db, $db_type, $pun_config;

	$db->add_field('topics', 'poll_type', 'TINYINT(4)', false, 0) or error('Unable to add poll_type field', __FILE__, __LINE__, $db->error());
	$db->add_field('topics', 'poll_time', 'INT(10) UNSIGNED', false, 0) or error('Unable to add poll_time field', __FILE__, __LINE__, $db->error());
	$db->add_field('topics', 'poll_term', 'TINYINT(4)', false, 0) or error('Unable to add poll_term field', __FILE__, __LINE__, $db->error());
	$db->add_field('topics', 'poll_kol', 'INT(10) UNSIGNED', false, 0) or error('Unable to add poll_kol field', __FILE__, __LINE__, $db->error());

	$schema = array(
			'FIELDS'			=> array(
					'tid'				=> array(
							'datatype'			=> 'INT(10) UNSIGNED',
							'allow_null'    	=> false,
							'default'			=> '0'
					),
					'question'			=> array(
							'datatype'			=> 'TINYINT(4)',
							'allow_null'    	=> false,
							'default'			=> '0'
					),
					'field'			=> array(
							'datatype'			=> 'TINYINT(4)',
							'allow_null'    	=> false,
							'default'			=> '0'
					),
					'choice'			=> array(
							'datatype'			=> 'VARCHAR(255)',
							'allow_null'    	=> false,
							'default'			=> '\'\''
					),
					'votes'				=> array(
							'datatype'			=> 'INT(10) UNSIGNED',
							'allow_null'    	=> false,
							'default'			=> '0'
					)

			),
			'PRIMARY KEY'		=> array('tid', 'question', 'field')
	);
	$db->create_table('poll', $schema) or error('Unable to create table poll', __FILE__, __LINE__, $db->error());

	$schema = array(
			'FIELDS'			=> array(
					'tid'				=> array(
							'datatype'			=> 'INT(10) UNSIGNED',
							'allow_null'    	=> false
					),
					'uid'			=> array(
							'datatype'			=> 'INT(10) UNSIGNED',
							'allow_null'		=> false
					),
					'rez'			=> array(
							'datatype'			=> 'TEXT',
							'allow_null'    	=> true
					)
			),
			'PRIMARY KEY'		=> array('tid', 'uid')
	);
	
	$db->create_table('poll_voted', $schema) or error('Unable to create table poll_voted', __FILE__, __LINE__, $db->error());

	// Insert config data
	$config = array(
		'o_poll_enabled'			=> "'0'",
		'o_poll_max_ques'			=> "'3'",
		'o_poll_max_field'		=> "'20'",
		'o_poll_time'					=> "'60'",
		'o_poll_term'					=> "'3'",
		'o_poll_guest'				=> "'0'",
	);

	while (list($conf_name, $conf_value) = @each($config))
	{
		$db->query('INSERT INTO '.$db->prefix."config (conf_name, conf_value) VALUES('$conf_name', $conf_value)")
			or error('Unable to insert into table '.$db->prefix.'config. Please check your configuration and try again.');
	}

	forum_clear_cache();
}