<?php
/**
 * 03/03/2011
 * update.php
 * WisdomPanda
 */

define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';

$base_url = 'http://www.eve-bb.com/updates/'.EVE_BB_VERSION.'/';
$fetch_url = 'http://www.eve-bb.com/updates/get_file.php?file='.EVE_BB_VERSION.'/';

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

if (isset($_GET['patch'])) {
	//The patch file needed a breather, most likely from large SQL queries, we pass it strait back.
	include(FORUM_CACHE_DIR.'patch.php');
} else {
	
	//Not in patch, lets run the check.

	//Lets see if they need to update, using the existing code from admin_index.php, but not using short if format. (Messy IMO.)
	if (!ini_get('allow_url_fopen')) {
		message('fopen is not enabled. Please fix this before trying again.');
	} //End if.
	
	$latest_version = trim(@file_get_contents('http://www.eve-bb.com/latest_version'));
	
	if (empty($latest_version)) {
		message('Update check failed.');
	} //End if.
	
	if (version_compare(EVE_BB_VERSION, $latest_version, '>=')) {
		message('Your EveBB install is up to date!');
	} //End if.
		

	echo "Newer version found, starting upgrade proccess.<br/>\n";
	
	//Let's get the md5 sum of the updater script, then download the file and check it!
	echo "Fetching MD5 sum...";
	
	if (!fetch_file($fetch_url.'patch.md5', EVE_BB_VERSION.'patch.md5')) {
		echo "<br/>\nUnable to download the checksum for the patch file.<br/>\n";
		exit;
	} //End if.
	
	echo "Done.<br/>\n";
	echo "Fetching patch file...";
	
	if (!fetch_file($fetch_url.'patch.php', 'patch.php')) {
		echo "<br/>\nUnable to download the latest patch.<br/>\n";
		exit;
	} //End if.
	
	echo "Done.<br/>\n";
	
	//Do they match?
	$md5 = file_get_contents(FORUM_CACHE_DIR.EVE_BB_VERSION.'patch.md5');
	
	if ($md5 != md5_file(FORUM_CACHE_DIR.'patch.php')) {
		echo "Patch file [".md5(FORUM_CACHE_DIR.'patch.php')."] does not match the MD5 checksum. [".$md5."] Please restart the update.<br/>\n";
		exit;
	} //End if.
	
	@unlink(FORUM_CACHE_DIR.EVE_BB_VERSION.'patch.md5');
	
	//Ok, now we hand control over to our patch file. This will download any extra files required and update the DB according to the new scheme.
	echo "Patch verified, starting patching process.<br/>\n";

	//Redirect to make it prettier.
	redirect("update.php?patch&step=0", "Patch verified, starting patching process. This may take a few minutes.");

} //End if - else.

if (defined('PATCH_SUCCESS')) {
	//For now, just message it, we may change this functionality later.
	
	message('Patch successful! All cache files have been removed and your EveBB install is ready to use.<br/>
		<br/>
		The file permissions may have been changed. It is suggested - but not required - that you verify the permissions of the files on the server.<br/>
		<br/>
		If you are unsure how they should be configured, please contact your systems administrator.');
} //End if.

?>
