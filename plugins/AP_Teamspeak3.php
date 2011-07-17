<?php
/**
 * 05/06/2011
 * AP_Teamspeak3.php
 * Panda
 */

// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// Load the language file
require PUN_ROOT.'lang/'.$admin_language.'/ts3_plugin.php';

// Tell admin_loader.php that this is indeed a plugin and that it is loaded
define('PUN_PLUGIN_LOADED', 1);

if (isset($_POST['update_settings'])) {
		//settings we'll be changing.
		$settings = array(
			'ts3_enabled',
			'ts3_ip',
			'ts3_port',
			'ts3_query_port',
			'ts3_timeout',
			'ts3_user',
			'ts3_pass',
			'ts3_sid',
			'ts3_group_id',
			'ts3_channel_id',
			'ts3_server_name',
			'ts3_auth_group'
		);
		
		$log = '';
		
		foreach ($settings as $key) {
			//Lets check if it's set in both $_POST and $pun_config...
			//I like to use ''.$key.'' to remind my self that it's a text key, does no harm.
			if (isset($_POST[''.$key.''])) {
				//It's in $_POST and in $pun_config, lets see if it's changed.
				if ($_POST[''.$key.''] == $pun_config[''.$key.'']) {
					$log .= 'Nothing has changed for '.$key.'<br/>';
					continue; //Nothing has changed, don't bother.
				} //End if.
				
				//We also don't want to insert empty values
				if ($_POST[''.$key.''] == '') {
					continue;
				} //End if.
				
				$db->insert_or_update(
					array('conf_name' => $key, 'conf_value' => $_POST[$key]),
					'conf_name',
					$db->prefix.'config'
				) or error("Unable to update '".$key."' to '".$_POST[''.$key.'']."'.", __FILE__, __LINE__, $db->error());
				
			} //End if.
		} //End for each().
		
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
			require PUN_ROOT.'include/cache.php';
		} //End if.
		
		generate_config_cache();
		
		redirect(pun_htmlspecialchars($_SERVER['REQUEST_URI']), $lang_ts3_plugin['update_settings_redirect']);
	
} //End if.

if (isset($_GET['create_tokens'])) {
	create_token(2, 'TEST') or message("Unable to create token");
	message("TS3 token created.");
} //End if.

if (isset($_GET['clean_tokens'])) {
	clean_tokens() or message("Unable to clean tokens");
	message("TS3 tokens cleaned.");
} //End if.

// Display the admin navigation menu
generate_admin_menu($plugin);

?>
	<div class="plugin blockform">
		<h2><span><?php echo $lang_ts3_plugin['title1'] ?></span></h2>
		<div class="box">
			<div class="inbox">
				<p><?php echo $lang_ts3_plugin['info1'] ?></p>
				<p><?php echo $lang_ts3_plugin['info2'] ?></p>
				<p><?php echo $lang_ts3_plugin['clean'] ?></p>
			</div>
		</div>

		<h2 class="block2"><span><?php echo $lang_ts3_plugin['title2'] ?></span></h2>
		<div class="box">
			<form id="ts3" method="post" action="<?php echo pun_htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_ts3_plugin['legend1'] ?></legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row"><?php echo $lang_ts3_plugin['ts3_enabled'] ?></th>
									<td>
										<input type="radio" name="ts3_enabled" value="1"<?php if ($pun_config['ts3_enabled'] == '1') echo ' checked="checked"' ?> />&#160;<strong><?php echo $lang_admin_common['Yes'] ?></strong>&#160;&#160;&#160;<input type="radio" name="ts3_enabled" value="0"<?php if ($pun_config['ts3_enabled'] == '0') echo ' checked="checked"' ?> />&#160;<strong><?php echo $lang_admin_common['No'] ?></strong>
										<span><?php echo $lang_ts3_plugin['ts3_enabled_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_ts3_plugin['ts3_ip'] ?></th>
									<td>
										<input type="text" name="ts3_ip" size="25" tabindex="1" value="<?php echo $pun_config['ts3_ip']; ?>"/>
										<span><?php echo $lang_ts3_plugin['ts3_ip_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_ts3_plugin['ts3_port'] ?></th>
									<td>
										<input type="text" name="ts3_port" size="25" tabindex="1" value="<?php echo $pun_config['ts3_port']; ?>"/>
										<span><?php echo $lang_ts3_plugin['ts3_port_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_ts3_plugin['ts3_query_port'] ?></th>
									<td>
										<input type="text" name="ts3_query_port" size="25" tabindex="1" value="<?php echo $pun_config['ts3_query_port']; ?>"/>
										<span><?php echo $lang_ts3_plugin['ts3_query_port_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_ts3_plugin['ts3_timeout'] ?></th>
									<td>
										<input type="text" name="ts3_timeout" size="25" tabindex="1" value="<?php echo $pun_config['ts3_timeout']; ?>"/>
										<span><?php echo $lang_ts3_plugin['ts3_timeout_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_ts3_plugin['ts3_user'] ?></th>
									<td>
										<input type="text" name="ts3_user" size="25" tabindex="1" value="<?php echo $pun_config['ts3_user']; ?>"/>
										<span><?php echo $lang_ts3_plugin['ts3_user_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_ts3_plugin['ts3_pass'] ?></th>
									<td>
										<input type="password" name="ts3_pass" size="25" tabindex="1" value=""/>
										<span><?php echo $lang_ts3_plugin['ts3_pass_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_ts3_plugin['ts3_sid'] ?></th>
									<td>
										<input type="text" name="ts3_sid" size="25" tabindex="1" value="<?php echo $pun_config['ts3_sid']; ?>"/>
										<span><?php echo $lang_ts3_plugin['ts3_sid_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_ts3_plugin['ts3_group_id'] ?></th>
									<td>
										<input type="text" name="ts3_group_id" size="25" tabindex="1" value="<?php echo $pun_config['ts3_group_id']; ?>"/>
										<span><?php echo $lang_ts3_plugin['ts3_group_id_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_ts3_plugin['ts3_channel_id'] ?></th>
									<td>
										<input type="text" name="ts3_channel_id" size="25" tabindex="1" value="<?php echo $pun_config['ts3_channel_id']; ?>"/>
										<span><?php echo $lang_ts3_plugin['ts3_channel_id_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_ts3_plugin['ts3_server_name'] ?></th>
									<td>
										<input type="text" name="ts3_server_name" size="25" tabindex="1" value="<?php echo $pun_config['ts3_server_name']; ?>"/>
										<span><?php echo $lang_ts3_plugin['ts3_server_name_info'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_ts3_plugin['ts3_auth_group'] ?></th>
									<td>
										<select id="ts3_auth_group" name="ts3_auth_group" tabindex="1">
<?php

$result = $db->query('SELECT g_id, g_title FROM '.$db->prefix.'groups WHERE g_id!='.PUN_ADMIN.' AND g_id!='.PUN_GUEST.' AND g_moderator=0 ORDER BY g_title') or error('Unable to fetch user group list', __FILE__, __LINE__, $db->error());

$groups = array();

while ($row = $db->fetch_assoc($result))
{
	if ($row['g_id'] == $pun_config['ts3_auth_group'])
		echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$row['g_id'].'" selected="selected">'.pun_htmlspecialchars($row['g_title']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$row['g_id'].'">'.pun_htmlspecialchars($row['g_title']).'</option>'."\n";
}

?>
										</select>
										<span><?php echo $lang_admin_eve_online['ts3_auth_group_info'] ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<p class="submitend"><input type="submit" name="update_settings" value="<?php echo $lang_ts3_plugin['save'] ?>" tabindex="<?php echo ($cur_index++) ?>" /></p>
			</form>
		</div>
	</div>