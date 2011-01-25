<?php

// Tell header.php to use the admin template
define('PUN_ADMIN_CONSOLE', 1);

define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'include/common_admin.php';


if ($pun_user['g_id'] != PUN_ADMIN)
	message($lang_common['No permission']);

// Load the eve language file
require PUN_ROOT.'lang/'.$admin_language.'/admin_eve_online.php';

$action = isset($_GET['action']) ? $_GET['action'] : null;

//This is the avatar uploading script, modified for our purposes.
if ($action == 'upload_banner')
{
	if (isset($_POST['form_sent']))
	{
		if (!isset($_FILES['req_file'])) {
			message($lang_admin_eve_online['No file']." 1".print_r($_FILES, true));
		} //End if.

		$uploaded_file = $_FILES['req_file'];
		$name = strip_special($uploaded_file['name']);

		// Make sure the upload went smooth
		if (isset($uploaded_file['error']))
		{
			switch ($uploaded_file['error'])
			{
				case 1: // UPLOAD_ERR_INI_SIZE
				case 2: // UPLOAD_ERR_FORM_SIZE
					message($lang_admin_eve_online['Too large ini']);
					break;

				case 3: // UPLOAD_ERR_PARTIAL
					message($lang_admin_eve_online['Partial upload']);
					break;

				case 4: // UPLOAD_ERR_NO_FILE
					message($lang_admin_eve_online['No file']." 2");
					break;

				case 6: // UPLOAD_ERR_NO_TMP_DIR
					message($lang_admin_eve_online['No tmp directory']);
					break;

				default:
					// No error occured, but was something actually uploaded?
					if ($uploaded_file['size'] == 0) {
						message($lang_admin_eve_online['No file']." 3");
					} //End if.
					break;
			}
		}

		if (is_uploaded_file($uploaded_file['tmp_name']))
		{
			// Preliminary file check, adequate in most cases
			$allowed_types = array('image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png');
			if (!in_array($uploaded_file['type'], $allowed_types)) {
				message($lang_admin_eve_online['Bad type']);
			} //End if.

			// Make sure the file isn't too big
			if ($uploaded_file['size'] > $pun_config['o_eve_banner_size']) {//800KB for now.
				message($lang_admin_eve_online['Too large'].' '.forum_number_format($pun_config['o_eve_banner_size']).' '.$lang_admin_eve_online['bytes'].'.');
				
			}//End if.

			// Move the file to the banner directory. We do this before checking the width/height to circumvent open_basedir restrictions
			if (!@move_uploaded_file($uploaded_file['tmp_name'], $pun_config['o_eve_banner_dir'].'/'.$name.'.tmp')) {
				message($lang_admin_eve_online['Move failed'].' <a href="mailto:'.$pun_config['o_admin_email'].'">'.$pun_config['o_admin_email'].'</a>.');
			} //End if.

			list($width, $height, $type,) = @getimagesize($pun_config['o_eve_banner_dir'].'/'.$name.'.tmp');

			// Determine type
			$extension = null;
			if ($type == IMAGETYPE_JPEG)
				$extension = '.jpg';
			else if ($type == IMAGETYPE_PNG)
				$extension = '.png';
			else
			{
				// Invalid type
				@unlink($pun_config['o_eve_banner_dir'].'/'.$name.'.tmp');
				message($lang_admin_eve_online['Bad type']);
			}

			// Now check the width/height
			if (empty($width) || empty($height) || $width > $pun_config['o_eve_banner_width'] || $height > $pun_config['o_eve_banner_height'])
			{
				@unlink($pun_config['o_eve_banner_dir'].'/'.$name.'.tmp');
				message($lang_admin_eve_online['Too wide or high'].' '.$pun_config['o_eve_banner_width'].'x'.$pun_config['o_eve_banner_height'].' '.$lang_admin_eve_online['pixels'].'.');
			}
			
			@rename($pun_config['o_eve_banner_dir'].'/'.$name.'.tmp', $pun_config['o_eve_banner_dir'].'/'.$name) or message("Unable to rename banner.");
			@chmod($pun_config['o_eve_banner_dir'].'/'.$name, 0775) or message("Unable to chmod file.");
			
			if (!file_exists($pun_config['o_eve_banner_dir'].'/'.$name)) {
				message("I've lost the file. HALP.");
			} //End if.
			
		}
		else
			message($lang_admin_eve_online['Unknown failure']);

		redirect('admin_eve_online.php', $lang_admin_eve_online['upload_redirect']);
	}
	
	message("No form received.");

} //End banner upload

if ($action == "select_banner") {
	
	if (isset($_POST['form_sent'])) {
		
		$sent_banner = strip_special($_POST['banner']);
		
		$banners = scandir(PUN_ROOT.$pun_config['o_eve_banner_dir']);
		$exists = false;
		
		foreach ($banners as $row) {
			if (strlen($row) < 5) {
				continue;
			} //End if.
			
			if ($sent_banner == $row) {
				$exists = true;
				break;
			} //End if.
			
		} //End foreach().
		
		if (!$exists) {
			message("You have selected a banner that doesn't exist. Good job.");
		} //End if.
		
		$sql = "UPDATE ".$db->prefix."config SET conf_value='".$sent_banner."' WHERE conf_name='o_eve_banner';";

		$db->query($sql) or error("Unable to update banner selection.", __FILE__, __LINE__, $db->error());
			// Regenerate the config cache
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
			require PUN_ROOT.'include/cache.php';
		} //End if.

		generate_config_cache();
		
		redirect('admin_eve_online.php', $lang_admin_eve_online['banner_select_redirect']);
	} //End if.
} //End banner select.

if ($action == "update_settings") {
	if (isset($_POST['form_sent'])) {
		//settings we'll be changing.
		$settings = array (
		'o_eve_use_iga',
		'o_eve_use_corp_name',
		'o_eve_use_corp_ticker'	,
		'o_eve_use_ally_name',
		'o_eve_use_ally_ticker',
		'o_eve_cache_char_sheet_interval', //4 hours
		'o_eve_rules_interval', //4 hours
		'o_eve_auth_interval', //4 hours
		'o_eve_use_banner',
		'o_eve_restrict_reg_corp',
		'o_eve_restricted_group',
		'o_eve_last_auth_check',
		'o_eve_banner_dir',
		'o_eve_banner_size',
		'o_eve_banner_width',
		'o_eve_banner_height',
		'o_eve_banner_text_enable',
		'o_eve_use_cron');
		
		$log ='';
		
		foreach ($settings as $key) {
			//Lets check if it's set in both $_POST and $pun_config...
			//I like to use ''.$key.'' to remind my self that it's a text key, does no harm.
			if (isset($_POST[''.$key.'']) && isset($pun_config[''.$key.''])) {
				//It's in $_POST and in $pun_config, lets see if it's changed.
				if ($_POST[''.$key.''] == $pun_config[''.$key.'']) {
					continue; //Nothing has changed, don't bother.
				} //End if.
				
				$sql = "UPDATE ".$db->prefix."config SET conf_value='".strip_special($_POST[''.$key.''])."' WHERE conf_name='".$key."';";
				if (!$db->query($sql)) {
					$log .= "Unable to update '".$key."' to '".$_POST[''.$key.'']."'.";
				} //End if.
				
			} //End if.
		} //End for each().
		
		if (strlen($log) > 0) {
			message($log);
		} //End if.
		
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
			require PUN_ROOT.'include/cache.php';
		} //End if.
		
		generate_config_cache();
		
		redirect('admin_eve_online.php', $lang_admin_eve_online['update_settings_redirect']);
		
	} //End if.
	
} //End if.

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Eve Online']);
$required_fields = array('req_title' => $lang_admin_eve_online['title']);
define('PUN_ACTIVE_PAGE', 'admin');
require PUN_ROOT.'header.php';

generate_admin_menu('eve_online');

?>

	<div class="plugin blockform">
		<h2><span><?php echo $lang_admin_eve_online['title'] ?></span></h2>
		<div class="box">
			<div class="inbox">
				<p><?php echo $lang_admin_eve_online['Explanation 1'] ?></p>
				<p><?php echo $lang_admin_eve_online['Explanation 2'] ?></p>
			</div>
		</div>
		
		<h2 class="block2"><span><?php echo $lang_admin_eve_online['corp_setting'] ?></span></h2>
		<div class="box">
			<form id="settings" method="post" action="admin_eve_online.php?action=update_settings">
				<div class="inform">
				
					<fieldset>
						<legend><?php echo $lang_admin_eve_online['general_legend_text'] ?></legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row"><?php echo $lang_admin_eve_online['o_eve_use_iga'] ?></th>
									<td>
										<input type="radio" name="o_eve_use_iga" value="1"<?php if ($pun_config['o_eve_use_iga'] == '1') echo ' checked="checked"' ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong>&#160;&#160;&#160;<input type="radio" name="o_eve_use_iga" value="0"<?php if ($pun_config['o_eve_use_iga'] == '0') echo ' checked="checked"' ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong>
										<span><?php echo $lang_admin_eve_online['o_eve_use_iga_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_eve_online['o_eve_use_banner'] ?></th>
									<td>
										<input type="radio" name="o_eve_use_banner" value="1"<?php if ($pun_config['o_eve_use_banner'] == '1') echo ' checked="checked"' ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong>&#160;&#160;&#160;<input type="radio" name="o_eve_use_banner" value="0"<?php if ($pun_config['o_eve_use_banner'] == '0') echo ' checked="checked"' ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong>
										<span><?php echo $lang_admin_eve_online['o_eve_use_banner_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_eve_online['o_eve_use_banner_text'] ?></th>
									<td>
										<input type="radio" name="o_eve_banner_text_enable" value="1"<?php if ($pun_config['o_eve_banner_text_enable'] == '1') echo ' checked="checked"' ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong>&#160;&#160;&#160;<input type="radio" name="o_eve_banner_text_enable" value="0"<?php if ($pun_config['o_eve_banner_text_enable'] == '0') echo ' checked="checked"' ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong>
										<span><?php echo $lang_admin_eve_online['o_eve_use_banner_text_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_eve_online['o_eve_banner_dir'] ?><br/><span>Relative to root directory</span></th>
									<td>
										<input type="text" name="o_eve_banner_dir" size="25" tabindex="1" value="<?php echo $pun_config['o_eve_banner_dir']; ?>"/>
										<span><?php echo $lang_admin_eve_online['o_eve_banner_dir_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_eve_online['o_eve_banner_size'] ?><br/><span>In Bytes</span></th>
									<td>
										<input type="text" name="o_eve_banner_size" size="25" tabindex="1" value="<?php echo $pun_config['o_eve_banner_size']; ?>"/>
										<span><?php echo $lang_admin_eve_online['o_eve_banner_size_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_eve_online['o_eve_banner_width'] ?><br/><span>In Pixels</span></th>
									<td>
										<input type="text" name="o_eve_banner_width" size="25" tabindex="1" value="<?php echo $pun_config['o_eve_banner_width']; ?>"/>
										<span><?php echo $lang_admin_eve_online['o_eve_banner_width_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_eve_online['o_eve_banner_height'] ?><br/><span>In Pixels</span></th>
									<td>
										<input type="text" name="o_eve_banner_height" size="25" tabindex="1" value="<?php echo $pun_config['o_eve_banner_height']; ?>"/>
										<span><?php echo $lang_admin_eve_online['o_eve_banner_height_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_eve_online['o_eve_use_cron'] ?></th>
									<td>
										<input type="radio" name="o_eve_use_cron" value="1"<?php if ($pun_config['o_eve_use_cron'] == '1') echo ' checked="checked"' ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong>&#160;&#160;&#160;<input type="radio" name="o_eve_use_cron" value="0"<?php if ($pun_config['o_eve_use_cron'] == '0') echo ' checked="checked"' ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong>
										<span><?php echo $lang_admin_eve_online['o_eve_use_cron_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_eve_online['o_eve_rules_interval'] ?><br/><span>In Hours</span></th>
									<td>
										<input type="text" name="o_eve_rules_interval" size="25" tabindex="1" value="<?php echo $pun_config['o_eve_rules_interval']; ?>"/>
										<span><?php echo $lang_admin_eve_online['o_eve_rules_interval_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_eve_online['o_eve_auth_interval'] ?><br/><span>In Hours</span></th>
									<td>
										<input type="text" name="o_eve_auth_interval" size="25" tabindex="1" value="<?php echo $pun_config['o_eve_auth_interval']; ?>"/>
										<span><?php echo $lang_admin_eve_online['o_eve_auth_interval_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_eve_online['o_eve_cache_char_sheet_interval'] ?><br/><span>In Hours</span></th>
									<td>
										<input type="text" name="o_eve_cache_char_sheet_interval" size="25" tabindex="1" value="<?php echo $pun_config['o_eve_cache_char_sheet_interval']; ?>"/>
										<span><?php echo $lang_admin_eve_online['o_eve_cache_char_sheet_interval_info'] ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
					<br />
					<fieldset>
						<legend><?php echo $lang_admin_eve_online['corp_legend_text'] ?></legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row"><?php echo $lang_admin_eve_online['o_eve_use_corp_name'] ?></th>
									<td>
										<input type="radio" name="o_eve_use_corp_name" value="1"<?php if ($pun_config['o_eve_use_corp_name'] == '1') echo ' checked="checked"' ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong>&#160;&#160;&#160;<input type="radio" name="o_eve_use_corp_name" value="0"<?php if ($pun_config['o_eve_use_corp_name'] == '0') echo ' checked="checked"' ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong>
										<span><?php echo $lang_admin_eve_online['o_eve_use_corp_name_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_eve_online['o_eve_use_ticker_corp'] ?></th>
									<td>
										<input type="radio" name="o_eve_use_corp_ticker" value="1"<?php if ($pun_config['o_eve_use_corp_ticker'] == '1') echo ' checked="checked"' ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong>&#160;&#160;&#160;<input type="radio" name="o_eve_use_corp_ticker" value="0"<?php if ($pun_config['o_eve_use_corp_ticker'] == '0') echo ' checked="checked"' ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong>
										<span><?php echo $lang_admin_eve_online['o_eve_use_ticker_corp_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_eve_online['o_eve_use_ally_name'] ?></th>
									<td>
										<input type="radio" name="o_eve_use_ally_name" value="1"<?php if ($pun_config['o_eve_use_ally_name'] == '1') echo ' checked="checked"' ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong>&#160;&#160;&#160;<input type="radio" name="o_eve_use_ally_name" value="0"<?php if ($pun_config['o_eve_use_ally_name'] == '0') echo ' checked="checked"' ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong>
										<span><?php echo $lang_admin_eve_online['o_eve_use_ally_name_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_eve_online['o_eve_use_ticker_ally'] ?></th>
									<td>
										<input type="radio" name="o_eve_use_ally_ticker" value="1"<?php if ($pun_config['o_eve_use_ally_ticker'] == '1') echo ' checked="checked"' ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong>&#160;&#160;&#160;<input type="radio" name="o_eve_use_ally_ticker" value="0"<?php if ($pun_config['o_eve_use_ally_ticker'] == '0') echo ' checked="checked"' ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong>
										<span><?php echo $lang_admin_eve_online['o_eve_use_ticker_ally_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_eve_online['o_eve_restrict_reg_corp'] ?></th>
									<td>
										<input type="radio" name="o_eve_restrict_reg_corp" value="1"<?php if ($pun_config['o_eve_restrict_reg_corp'] == '1') echo ' checked="checked"' ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong>&#160;&#160;&#160;<input type="radio" name="o_eve_restrict_reg_corp" value="0"<?php if ($pun_config['o_eve_restrict_reg_corp'] == '0') echo ' checked="checked"' ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong>
										<span><?php echo $lang_admin_eve_online['o_eve_restrict_reg_corp_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_eve_online['purge_group'] ?></th>
									<td>
										<input type="hidden" name="form_sent" value="1" />
										<select id="o_eve_restricted_group" name="o_eve_restricted_group" tabindex="1">
<?php

$result = $db->query('SELECT g_id, g_title FROM '.$db->prefix.'groups WHERE g_id!='.PUN_ADMIN.' AND g_id!='.PUN_GUEST.' AND g_moderator=0 ORDER BY g_title') or error('Unable to fetch user group list', __FILE__, __LINE__, $db->error());

$groups = array();

while ($row = $db->fetch_assoc($result))
{
	if ($row['g_id'] == $pun_config['o_default_user_group'])
		echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$row['g_id'].'" selected="selected">'.pun_htmlspecialchars($row['g_title']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$row['g_id'].'">'.pun_htmlspecialchars($row['g_title']).'</option>'."\n";
}

?>
										</select>
										<span><?php echo $lang_admin_eve_online['purge_group_info'] ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<p class="submitend"><input type="submit" name="save" value="<?php echo $lang_admin_common['Save changes'] ?>" /></p>
			</form>
		</div>
		

		<h2 class="block2"><span><?php echo $lang_admin_eve_online['banner form'] ?></span></h2>
		<div class="box">
			<form id="banner_upload" method="post" action="admin_eve_online.php?action=upload_banner" enctype="multipart/form-data">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_eve_online['Legend text'] ?></legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row"><?php echo $lang_admin_eve_online['Text to show'] ?><div><input type="submit" name="show_text" value="<?php echo $lang_admin_eve_online['Show text button'] ?>" tabindex="2" /></div></th>
									<td>
										<input type="hidden" name="form_sent" value="1" />
										<input name="req_file" type="file" size="40" />
										<span><?php echo $lang_admin_eve_online['Input content'] ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
			</form>
			<form id="banner_select" method="post" action="admin_eve_online.php?action=select_banner">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_eve_online['banner_select_legend'] ?></legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row"><?php echo $lang_admin_eve_online['banner_select'] ?><div><input type="submit" name="banner_select_submit" value="<?php echo $lang_admin_eve_online['banner_select_submit'] ?>" tabindex="2" /></div></th>
									<td>
										<input type="hidden" name="form_sent" value="1" />
										<select id="banner" name="banner" tabindex="1" onChange="previewImage(this.options[this.selectedIndex].value);">
<?php

$banners = scandir(PUN_ROOT.$pun_config['o_eve_banner_dir']);
$current_banner = '';
foreach ($banners as $row)
{
	
	if (strlen($row) < 5) {
		continue; //Easy blanket way to remove any non-images. (1 name character + 1 '.' character + 3 file type characters = 5)
	} //End if.
	
	if ($row == $pun_config['o_eve_banner']) {
		$current_banner = $row;
		echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.pun_htmlspecialchars($row).'" selected="selected">'.pun_htmlspecialchars($row).'</option>'."\n";
	} else {
		echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.pun_htmlspecialchars($row).'">'.pun_htmlspecialchars($row).'</option>'."\n";
	} //End if - else.
}

?>
										</select>
										<span><?php echo $lang_admin_eve_online['banner_select_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row">&nbsp;</th>
									<td>
										<input type="hidden" id="preview_banner_dir" value="<?php echo $pun_config['o_eve_banner_dir']; ?>"/>
										<img id="current_banner_preview" src="<?php echo $pun_config['o_eve_banner_dir']."/".$pun_config['o_eve_banner']; ?>" width="300px" height="45px"/>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
		
	</div>
	<div class="clearer"></div>
</div>
<?php
require PUN_ROOT.'footer.php';
?>