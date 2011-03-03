<?php
/**
 * 03/03/2011
 * update.php
 * WisdomPanda
 */

define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';

if ($pun_user['g_id'] != PUN_ADMIN) {
	message('You must be an admin to do this.');
} //End if.

if (!function_exists('fetch_file')) {
	/**
	 * Gets a file from a remote place and puts it where you specify, in the cache folder.
	 * For now this will be restricted to the cache folder. You can always manually move it from there.
	 * Specified here as a just-in-caser. Included with 1.0.0 onwards.
	 */
	function fetch_file($url, $cache_name) {
		if (!$file = fopen($url, 'r')) {
			return false;
		} //End if.
		
		if (!$fout = fopen(FORUM_CACHE_DIR.$cache_name, 'w')) {
			return false;
		} //End if.
		
		while(!feof($file)) {
			$buffer = fread($file, 1024);
			fwrite($fout, $buffer);
		} //End if.
		
		fflush($fout);
		fclose($fout);
		fclose($file);
		return true;
	} //End fetch_file().
} //End if.

//Lets see if they need to update, using the existing code from admin_index.php, but not using short if format. (Messy IMO.)
if (!ini_get('allow_url_fopen')) {
	message('fopen is not enabled. Please fix this before trying again.');
} //End if.

$latest_version = trim(@file_get_contents('http://www.eve-bb.com/latest_version.txt'));

if (empty($latest_version)) {
	message('Update check failed.');
} //End if.

if (version_compare(EVE_BB_VERSION, $latest_version, '>=')) {
	message('Your EveBB install is up to date!');
} //End if.
	
//Ok, from here we want to push these messages to the page right away.
//This gives the user feed back as the page loads and means they are less likely to do stupid things like hit refresh in the middle of the update.
//Keep in mind that, while we are telling it to flush the buffer to the browser, this will not always have the correct effect.
//It varies greatly depending on the browser, server and settings being used. Still, no reason not to use it for those that take advantage of it.

echo "Newer version found, starting upgrade proccess.<br/>\n";
ob_fluish();

//Let's get the md5 sum of the updater script, then download the file and check it!

echo "Fetching MD5 sum...";
ob_flush();

if (!fetch_file('http://www.eve-bb.com/updates/'.EVE_BB_VERSION.'/patch.php.md5', EVE_BB_VERSION.'patch.php.md5')) {
	echo "<br/>\nUnable to download the checksum for the patch file.<br/>\n";
	exit;
} //End if.

echo "Done.<br/>\n";
ob_flush();

echo "Fetching patch file...";
ob_flush();

if (!fetch_file('http://www.eve-bb.com/updates/'.EVE_BB_VERSION.'/patch.php', EVE_BB_VERSION.'patch.php')) {
	echo "<br/>\nUnable to download the latest patch.<br/>\n";
	exit;
} //End if.

echo "Done.<br/>\n";
ob_flush();

//Do they match?
$md5 = file_get_contents(FORUM_CACHE_DIR.EVE_BB_VERSION.'patch.php.md5');

if ($md5 != md5_file(FORUM_CACHE_DIR.EVE_BB_VERSION.'patch.php')) {
	echo "Patch file does not match the MD5 checksum. Please restart the update.<br/>\n";
	exit;
} //End if.

//Ok, now we hand control over to our patch file. This will download any extra files required and update the DB according to the new scheme.
echo "Patch verified, starting patching process.<br/>\n";
ob_flush();

include(FORUM_CACHE_DIR.EVE_BB_VERSION.'patch.php');

if (defined(PATCH_SUCCESS)) {
	//Lets clean up the cache file now.
	
	//TODO: Loop through the file list the patch generates and delete the files.
	
	
	message('Patch successful! All cache files have been removed and your EveBB install is ready to use.');
} //End if.

?>
