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

/**
 * This changed in 1.0.0, so we must provide a copy with the updater.
 */
function redirect_no_back($destination_url, $message, $link_back = true)
{
	global $db, $pun_config, $lang_common, $pun_user;

	// Prefix with o_base_url (unless there's already a valid URI)
	if (strpos($destination_url, 'http://') !== 0 && strpos($destination_url, 'https://') !== 0 && strpos($destination_url, '/') !== 0)
		$destination_url = $pun_config['o_base_url'].'/'.$destination_url;

	// Do a little spring cleaning
	$destination_url = preg_replace('/([\r\n])|(%0[ad])|(;\s*data\s*:)/i', '', $destination_url);

	// If the delay is 0 seconds, we might as well skip the redirect all together
	if ($pun_config['o_redirect_delay'] == '0')
		header('Location: '.str_replace('&amp;', '&', $destination_url));

	// Send no-cache headers
	header('Expires: Thu, 21 Jul 1977 07:30:00 GMT'); // When yours truly first set eyes on this world! :)
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: post-check=0, pre-check=0', false);
	header('Pragma: no-cache'); // For HTTP/1.0 compatibility

	// Send the Content-type header in case the web server is setup to send something else
	header('Content-type: text/html; charset=utf-8');

	if (file_exists(PUN_ROOT.'style/'.$pun_user['style'].'/redirect.tpl'))
	{
		$tpl_file = PUN_ROOT.'style/'.$pun_user['style'].'/redirect.tpl';
		$tpl_inc_dir = PUN_ROOT.'style/'.$pun_user['style'].'/';
	}
	else
	{
		$tpl_file = PUN_ROOT.'include/template/redirect.tpl';
		$tpl_inc_dir = PUN_ROOT.'include/user/';
	}

	$tpl_redir = file_get_contents($tpl_file);

	// START SUBST - <pun_include "*">
	preg_match_all('#<pun_include "([^/\\\\]*?)\.(php[45]?|inc|html?|txt)">#', $tpl_redir, $pun_includes, PREG_SET_ORDER);

	foreach ($pun_includes as $cur_include)
	{
		ob_start();

		// Allow for overriding user includes, too.
		if (file_exists($tpl_inc_dir.$cur_include[1].'.'.$cur_include[2]))
			require $tpl_inc_dir.$cur_include[1].'.'.$cur_include[2];
		else if (file_exists(PUN_ROOT.'include/user/'.$cur_include[1].'.'.$cur_include[2]))
			require PUN_ROOT.'include/user/'.$cur_include[1].'.'.$cur_include[2];
		else
			error(sprintf($lang_common['Pun include error'], htmlspecialchars($cur_include[0]), basename($tpl_file)));

		$tpl_temp = ob_get_contents();
		$tpl_redir = str_replace($cur_include[0], $tpl_temp, $tpl_redir);
		ob_end_clean();
	}
	// END SUBST - <pun_include "*">


	// START SUBST - <pun_language>
	$tpl_redir = str_replace('<pun_language>', $lang_common['lang_identifier'], $tpl_redir);
	// END SUBST - <pun_language>


	// START SUBST - <pun_content_direction>
	$tpl_redir = str_replace('<pun_content_direction>', $lang_common['lang_direction'], $tpl_redir);
	// END SUBST - <pun_content_direction>


	// START SUBST - <pun_head>
	ob_start();

	$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Redirecting']);

?>
<meta http-equiv="refresh" content="<?php echo $pun_config['o_redirect_delay'] ?>;URL=<?php echo str_replace(array('<', '>', '"'), array('&lt;', '&gt;', '&quot;'), $destination_url) ?>" />
<title><?php echo generate_page_title($page_title) ?></title>
<link rel="stylesheet" type="text/css" href="style/<?php echo $pun_user['style'].'.css' ?>" />
<?php

	$tpl_temp = trim(ob_get_contents());
	$tpl_redir = str_replace('<pun_head>', $tpl_temp, $tpl_redir);
	ob_end_clean();
	// END SUBST - <pun_head>


	// START SUBST - <pun_redir_main>
	ob_start();

?>
<div class="block">
	<h2><?php echo $lang_common['Redirecting'] ?></h2>
	<div class="box">
		<div class="inbox">
			<p><?php echo $message.($link_back ? '<br /><br /><a href="'.$destination_url.'">'.$lang_common['Click redirect'].'</a>' : '') ?></p>
		</div>
	</div>
</div>
<?php

	$tpl_temp = trim(ob_get_contents());
	$tpl_redir = str_replace('<pun_redir_main>', $tpl_temp, $tpl_redir);
	ob_end_clean();
	// END SUBST - <pun_redir_main>


	// START SUBST - <pun_footer>
	ob_start();

	// End the transaction
	$db->end_transaction();

	// Display executed queries (if enabled)
	if (defined('PUN_SHOW_QUERIES'))
		display_saved_queries();

	$tpl_temp = trim(ob_get_contents());
	$tpl_redir = str_replace('<pun_footer>', $tpl_temp, $tpl_redir);
	ob_end_clean();
	// END SUBST - <pun_footer>


	// Close the db connection (and free up any result data)
	$db->close();

	exit($tpl_redir);
}

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
	redirect_no_back("update.php?patch&step=0", "Patch verified, starting patching process. This may take a few minutes.", false);

} //End if - else.

if (defined('PATCH_SUCCESS')) {
	//For now, just message it, we may change this functionality later.
	
	message('Patch successful! <a href="index.php">Your EveBB install is ready to use.</a><br/>
		<br/>
		The file permissions may have been changed. It is suggested - but not required - that you verify the permissions of the files on the server.<br/>
		<br/>
		If you are unsure how they should be configured, please contact your systems administrator.', true);
} //End if.

?>
