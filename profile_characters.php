<?php

if (!defined('FROM_PROFILE')) {
	message("Failed to access characters.");
} //End if.

//Get our language file.
require PUN_ROOT.'lang/'.$pun_user['language'].'/profile_characters.php';

$error = array();

$sql = "
	SELECT
		ts3.token,
		ts3.username AS nickname,
		sc.*,
		c.*,
		corp.*,
		ally.*,
		a.api_user_id
	FROM
		".$db->prefix."api_selected_char AS sc
	INNER JOIN
		".$db->prefix."api_characters AS c
	ON
		sc.character_id=c.character_id
	INNER JOIN
		".$db->prefix."api_auth AS a
	ON
		a.api_character_id=c.character_id
	LEFT JOIN
		".$db->prefix."teamspeak3 AS ts3
	ON
		ts3.user_id=sc.user_id
	LEFT JOIN
		".$db->prefix."api_allowed_corps AS corp
	ON
		corp.corporationID=c.corp_id
	LEFT JOIN
		".$db->prefix."api_alliance_list AS ally
	ON
		corp.allianceID=ally.allianceID
	WHERE
		sc.user_id=".$id."
";
	
if (!$result = $db->query($sql)) {
	$error[] = "Unable to fetch your character.";
} //End if.

if ($db->num_rows($result) == 0) {
	$error[] = "Unable to find your character.";
} //End if.

if (empty($error)) {
	$selected_char = $db->fetch_assoc($result);
} //End if.

if ($action == 'add_corp') {
	
	if ($pun_user['g_id'] == PUN_ADMIN) {
				
		add_corp($selected_char['corp_id']) or error("Unable to add corp", __FILE__, __LINE__, $db->error());
		
		redirect('profile.php?section=characters&amp;id='.$id, $lang_profile_characters['add_corp_redirect']);
	} //End if.
	
} //End if.

if ($action == 'select_character') {
	if (isset($_POST['form_sent_characters'])) {
		
		$character_id = intval($_POST['select_character']);
		
		if (!select_character($id, $character_id)) {
			message("Unable to select that character.");
		} //End if.
		
		$log = '';
		apply_rules($log); //We need to make sure they get moved correctly.
		
		redirect('profile.php?section=characters&amp;id='.$id, $lang_profile_characters['select_redirect']);
	} //End if.
} //End if.

if ($action == 'regen_token') {
	
	//Let it silently fall through.
	if ($pun_user['g_id'] == PUN_ADMIN && $pun_config['ts3_enabled'] == '1') {
		
		if (!function_exists('create_token')) {
			require(PUN_ROOT.'plugins/hooks/H_Teamspeak3.php');
			
			$username = $user['ticker'].'-'.$user['character_name'];
			if (strlen($user['allianceID']) > 0) {
				$username = $user['shortName'].'-'.$username;
			} //End if.
			
			create_token($id, $username) or message("Unable to regen the key, enable debugging for more information.");
			
			redirect('profile.php?section=characters&amp;id='.$id, $lang_profile_characters['regen_redirect']);
			
		} //End if.
		
	} //End if.
	
} //End if.

if ($action == 'refresh_keys') {
	if (!isset($_GET['keys'])) {
		message("Incorrect vars.");
	} //End if.

	$api_user_id = intval($_GET['keys']);
	
	if ($api_user_id == 0) {
		message("Malformed Vars.");
	} //End if.
	
	//Need to fetch the API key to match.
	$sql = "SELECT * FROM ".$db->prefix."api_auth WHERE api_user_id=".$api_user_id;
	if (!$result = $db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to get ApiUserID.", __FILE__, __LINE__, $db->error());
		} //End if.
		message('[DB ERR] Unable to refresh your api details.');
	} //End if.
	
	if ($db->num_rows($result) == 0) {
		message('Bad API details sent or they don\'t exist in the DB. (Unlikely)');
	} //End if.
	
	$result = $db->fetch_assoc($result);
	
	$cak($result['api_user_id'],$result['api_key']);
	$result = update_characters($id, $cak);
	
	if ($result === false) {
		message('['.$_LAST_ERROR.'] Unable to update your api details.');
	} else if (is_array($result)) {
		message(sprintf($lang_profile_characters['add_errors'], implode('<br/>', $result)));
	}  //End if - else if.
	
	redirect('profile.php?section=characters&amp;id='.$id, $lang_profile_characters['add_redirect']);
} //End if.

if ($action == 'add_character') {
	if (isset($_POST['form_sent_characters'])) {
		
		if (!isset($_POST['api_user_id']) || !isset($_POST['api_key'])) {
			message("Incorrect vars.");
		} //End if.

		$api_user_id = intval($_POST['api_user_id']);
		$api_key = strip_special($_POST['api_key']);
		
		if ($id == 0 || $api_user_id == 0 || strlen($api_key) == 0) {
			message("Malformed Vars.");
		} //End if.
		
		$cak($api_user_id, $api_key);
		$result = update_characters($id, $cak);
		
		if ($result === false) {
			message('['.$_LAST_ERROR.'] Unable to update your api details.');
		} else if (is_array($result)) {
			message(sprintf($lang_profile_characters['add_errors'], implode('<br/>', $result)));
		}  //End if - else if.
		
		redirect('profile.php?section=characters&amp;id='.$id, $lang_profile_characters['add_redirect']);
	} //End if.
} //End if.

if ($action == 'remove_keys') {
	if (!isset($_GET['keys'])) {
		message("Incorrect vars.");
	} //End if.

	$api_user_id = intval($_GET['keys']);
	
	if ($api_user_id == 0) {
		message("Malformed Vars.");
	} //End if.
	
	$sql = "SELECT DISTINCT api_user_id FROM ".$db->prefix."api_auth WHERE user_id=".$id.";";
	if (!$result = $db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to get user ID's.", __FILE__, __LINE__, $db->error());
		} //End if.
		message('[DB ERR] Unable to remove your api details.');
	} //End if.
	
	if ($db->num_rows($result) < 2) {
		message($lang_profile_characters['need_more_keys']);
	} //End if.
	
	$sql = "
		SELECT
			sc.*,
			a.api_user_id
		FROM
			".$db->prefix."api_selected_char AS sc
		INNER JOIN
			".$db->prefix."api_auth AS a
		ON
			a.api_character_id=sc.character_id
		WHERE
			sc.user_id=".$id."
	";
	
	if (!$result = $db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to get character.", __FILE__, __LINE__, $db->error());
		} //End if.
		message("Unable to fetch character data.");
	} //End if.
	
	if ($db->num_rows($result) != 1) {
		if (defined('PUN_DEBUG')) {
			error("No Characters.", __FILE__, __LINE__, $db->error());
		} //End if.
		message('You apparently have no characters. Way to break it.');
	} //End if.
	
	$result = $db->fetch_assoc($result);
	
	if ($result['api_user_id'] == $api_user_id) {
		message($lang_profile_characters['change_character_first']);
	} //End if.
	
	//Looks like we're good to go.
	$sql = "DELETE FROM ".$db->prefix."api_auth WHERE api_user_id=".$api_user_id;
	if (!$db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to dlete keys.", __FILE__, __LINE__, $db->error());
		} //End if.
		message('Can\'t delete your keys.');
	} //End if.
	
	redirect('profile.php?section=characters&amp;id='.$id, $lang_profile_characters['removed_redirect']);
} //End if.

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Section characters']);
define('PUN_ACTIVE_PAGE', 'profile');
require PUN_ROOT.'header.php';

generate_profile_menu('characters');

?>

	<div class="block">
		<h2><span><?php echo $lang_profile_characters['title'] ?></span></h2>
		<div class="box">
			<div class="inbox">
				<p><?php echo $lang_profile_characters['Explanation 1'] ?></p>
				<p><?php echo $lang_profile_characters['Explanation 2'] ?></p>
			</div>
		</div>
	</div>

	<div class="blockform">
<?php
	
	if (empty($error)) {
		$now = time();
		$offset = date('Z');
		$now = $offset > 0 ? $now - $offset : $now + offset;
		$queue = array();
		$last_skill = null;
		$total_width = 0;
		
		$sql = "
			SELECT
				*
			FROM
				".$db->prefix."api_skill_queue AS s
			LEFT JOIN
				".$db->prefix."api_skill_types AS t
			ON
				s.typeID=t.typeID
			WHERE
				s.character_id=".$selected_char['character_id']."
			ORDER BY
				s.queuePosition
			ASC";
		if (!$skills = $db->query($sql)) {
			$skills = array();
		} else if ($db->num_rows($skills) > 0) {
			while ($skill = $db->fetch_assoc($skills)) {
				$end_stamp = convert_to_stamp($skill['endtime'], true);
				
				if ($end_stamp < $now) {
					echo "skill out of date. $end_stamp vs $now";
					continue;
				} //End if.
				
				if ($last_skill == null) {
					$last_skill = $now;
				} //End if.
				
				$skill['color'] = '';
				$skill['width'] = intval(($end_stamp - $last_skill) / (60 * 60));
				
				if ($skill['width'] >= 24) {
					$skill['width'] = 100;
					$skill['color'] = 'green';
				} else {
					$skill['width'] = intval($skill['width'] / (0.24));
					$skill['color'] = 'orange';
				} //End if - else.
				
				if ($skill['width'] < 6) {
					$skill['color'] = 'red';
					if ($skill['width'] == 0) {
						$skill['width'] = 1; //Show *something*
					} //End if.
				} //End if.
				
				if (($skill['width'] + $total_width) >= 100) {
					$skill['width'] = (100 - $total_width);
				} //End if.
				
				$skill['left_width'] = $total_width;
				
				$total_width += $skill['width'];
				$queue[] = $skill;
			} //End while loop().
			
		} else {
			$skills = array();
		} //End if - else.
		
		$level = array(1 => 'I', 2 => 'II', 3 => 'III', 4=> 'IV', 5 => 'V');

?>
	<h2><span><?php echo $lang_profile_characters['characters'] ?></span></h2>
	<div class="box">
		<form id="dummy_character_form" method="post" action="profile.php?section=characters&amp;id=<?php echo $id ?>">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_profile_characters['selected_char'] ?></legend>
					<div class="infldset" id="selected_char_info">
						<table class="aligntop" cellspacing="0">
							<tr>
								<th valign="top" scope="row" rowspan="6" style="width: 128px;">	<img src="img/chars/<?php echo $selected_char['character_id']; ?>_128.jpg" width="128px" height="128px" alt="" /></th>
								<td>&nbsp;<strong><?php echo $lang_profile_characters['name']; ?></strong></td>
								<td><?php echo $selected_char['character_name']; ?></td>
							</tr>
							<tr>
								<td>&nbsp;<strong><?php echo $lang_profile_characters['corp']; ?></strong></td>
								<td><?php echo $selected_char['corp_name']; ?> <?php if ($pun_user['g_id'] == PUN_ADMIN && $selected_char['allowed'] == 0) { ?><a href="profile.php?section=characters&amp;id=<?php echo $id ?>&amp;action=add_corp"><?php echo $lang_profile_characters['auth_corp']?></a><?php }?></td>
							</tr>
							<tr>
								<td>&nbsp;<strong><?php echo $lang_profile_characters['ally']; ?></strong></td>
								<td><?php echo $selected_char['ally_name']; ?></td>
							</tr>
							<tr>
								<td>&nbsp;<strong><?php echo $lang_profile_characters['race']; ?></strong></td>
								<td><?php echo $selected_char['race']; ?></td>
							</tr>
							<tr>
								<td>&nbsp;<strong><?php echo $lang_profile_characters['blood_line']; ?></strong></td>
								<td><?php echo $selected_char['blood_line']; ?></td>
							</tr>
							<tr>
								<td>&nbsp;<strong><?php echo $lang_profile_characters['ancestry']; ?></strong></td>
								<td><?php echo $selected_char['ancestry']; ?></td>
							</tr>
							<tr>
								<td rowspan="4">&nbsp;</td>
								<td>&nbsp;<strong><?php echo $lang_profile_characters['dob']; ?></strong></td>
								<td><?php echo $selected_char['dob']; ?></td>
							</tr>
							<tr>
								<td>&nbsp;<strong><?php echo $lang_profile_characters['clone']; ?></strong></td>
								<td><?php echo $selected_char['clone_name']; ?> (<?php echo number_format($selected_char['clone_sp']); ?> SP)</td>
							</tr>
							<tr>
								<td>&nbsp;<strong><?php echo $lang_profile_characters['wallet']; ?></strong></td>
								<td><?php echo number_format($selected_char['balance']); ?> Isk</td>
							</tr>
							<tr>
								<td valign="top">&nbsp;<strong><?php echo $lang_profile_characters['skill_queue']; ?></strong></td>
								<td>
								<?php
									foreach($queue as $skill) {
								?>
									&nbsp;<strong><?php echo (isset($skill['typename']) ? $skill['typename'].' '.$level[$skill['level']] : $lang_profile_characters['unknown']); ?></strong> - <?php  echo (isset($skill['typename']) ? sprintf($lang_profile_characters['skill_queue_remaining'], format_time_diff($now, $end_stamp)) : ''); ?><br/>
									<div class="box" style="width: 100%; border-style: solid; border-width: 1px; padding:0;">
										
										<table class="infldset"><tr style="border-style: solid; border-width: 1px;"><td style="width: <?php echo $skill['left_width']?>%;"></td><td style="background-color: <?php echo $skill['color']; ?>; width: <?php echo $skill['width']; ?>%;"></td><td style="width: <?php echo 100-($skill['width']+$skill['left_width']);?>%;"></td></tr></table>
									
										<!-- <div class="infldset" style="margin: 0 auto 0 <?php echo $skill['left_width']?>%;border-style: solid; border-width: 1px; background-color: <?php echo $skill['color']; ?>; padding: 1px; width: <?php echo $skill['width']; ?>%; height: 10px; display:block;"></div> -->
									</div>
									
								<?php
								} //End foreach().
								if (empty($queue)) {
									echo $lang_profile_characters['next_update'];
								} //End if.
								?>
								</td>
							</tr>
							
						</table>
					</div>
				</fieldset>
				<br/>
				<?php
					if ($pun_config['o_eve_use_image_server'] != '1') {
				?>
				<a class="api_reload_avatars" href="profile.php?section=characters&amp;id=<?php echo $id ?>&amp;action=reload_pics"><?php echo $lang_profile_characters['reload_avatars']; ?></a><br/><br/>
				<?php
					} //End if.
					if (strlen($selected_char['token']) > 0 && $pun_config['ts3_enabled'] == '1') {
				?>
				<a href="ts3server://<?php
					echo $pun_config['ts3_ip']?>:<?php
					echo $pun_config['ts3_port']?>?nickname=<?php
					echo $selected_char['nickname']?>&amp;addbookmark=<?php
					echo $pun_config['ts3_server_name']?>&amp;token=<?php
					echo $selected_char['token']?>">Click here to connect to Teamspeak 3</a>
				<?php
			
						if ($pun_user['g_id'] == PUN_ADMIN) {
							//Let them regenerate the token, for what ever reason.
							echo '<br/><br/><a href="profile.php?section=characters&amp;id='.$id.'&amp;action=regen_token">Issue User New Token</a>';
						} //End if.
				
					} //End if.
				?>
			</div>
		</form>
<?php
	} else {
?>

		<h2><span><?php echo $lang_profile_characters['error'] ?></span></h2>
		<div class="box">
			<div class="inbox">
				<p>&nbsp;<?php foreach($error as $err) {echo $err."<br/>\n";} ?></p>
			</div>
		</div>
		<br/>
<?php
	echo '
	<h2><span>'.$lang_profile_characters['characters'].'</span></h2>
		<div class="box">'; //This prevents it showing up as an error in my IDE.
	} //End if - else.

?>
		
		<form id="select_character" method="post" action="profile.php?section=characters&amp;action=select_character&amp;id=<?php echo $id ?>" onsubmit="return process_form(this)">
			<div class="inform">
				<input type="hidden" name="form_sent_characters" value="1" />
				<fieldset>
					<legend><?php echo $lang_profile_characters['character_list'] ?></legend>
					<div class="infldset">
						<table class="aligntop" cellspacing="0">
<?php

//Since we are using INNER JOIN, we shouldn't need to check if the character is active or not - inactive characters don't have API keys.
$sql = "
	SELECT
		*
	FROM
		".$db->prefix."api_characters AS c
	INNER JOIN
		".$db->prefix."api_auth AS a
	ON
		a.api_character_id=c.character_id
	WHERE c.user_id=".$id.";";
$result = $db->query($sql) or message("Unable to fetch character list.");
$current_keys = null;
while ($row = $db->fetch_assoc($result)) {
	cache_char_pic($row['character_id'], ($action == 'reload_pics'));
	if ($row['api_user_id'] != $current_keys) {
		echo '
							<tr>
								<th scope="row" colspan="3">
									'.sprintf($lang_profile_characters['api_heading'], $row['api_user_id']).'
									<span id="remove_api_keys">
									<a href="profile.php?section=characters&amp;action=refresh_keys&amp;keys='.$row['api_user_id'].'&amp;id='.$id.'">'.$lang_profile_characters['refresh_keys'].'</a>
									</span>
									<span id="remove_api_keys">
									'.($row['api_user_id'] == $selected_char['api_user_id'] ? '' :
									'<a href="profile.php?section=characters&amp;action=remove_keys&amp;keys='.$row['api_user_id'].'&amp;id='.$id.'">'.$lang_profile_characters['remove_keys'].'</a>').'
									</span>
								</th>
							</tr>';
	} //End if.
	echo '
							<tr>
								<th scope="row" style="width: 64px;"><img src="img/chars/'.$row['character_id'].'_64.jpg" width="64px" height="64px" alt="" /></th>
								<td>
									&nbsp;<strong>'.$row['character_name'].'</strong><br/>
									&nbsp;<em>'.$row['corp_name'].'</em><br/>
									&nbsp;'.$row['ally_name'].'
								</td>
								<td><input type="radio" name="select_character" value="'.$row['character_id'].'" '.(($row['character_id'] == $selected_char['character_id']) ? ' checked="checked"' : '').' />&#160;<strong>'.$lang_profile_characters['select'].'</strong></td>
							</tr>';
	$current_keys = $row['api_user_id'];
} //End while loop.

?>
						</table>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="update" value="<?php echo $lang_profile_characters['save'] ?>" /></p>
		</form>
	
		<form id="add_character" method="post" action="profile.php?section=characters&amp;action=add_character&amp;id=<?php echo $id ?>" onsubmit="return process_form(this)">
			<div class="inform">
				<input type="hidden" name="form_sent_characters" value="1" />
				<fieldset>
					<legend><?php echo $lang_profile_characters['add_char'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_profile_characters['add_char_info'] ?></p>
						<label class="required"><strong><?php echo $lang_profile_characters['api_id'] ?><span><?php echo $lang_common['Required'] ?></span></strong><br /><input id="api_user_id" type="text" name="api_user_id" value="<?php echo pun_htmlspecialchars($api_user_id) ?>" size="50" maxlength="80" /><br /></label>
						<label class="required"><strong><?php echo $lang_profile_characters['api_vcode']?><span><?php echo $lang_common['Required'] ?></span></strong><br /><input id="api_key" type="text" name="api_key" value="<?php echo pun_htmlspecialchars($api_key) ?>" size="50" maxlength="80" /><br /></label><br/>
						<p><?php echo $lang_profile_characters['api_link']; ?></p>
						<div class="clearer"></div>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="update" value="<?php echo $lang_profile_characters['add_char_submit'] ?>" /></p>
		</form>
	

	
</div>
	

	<div class="clearer"></div>
</div>
<?php
echo "</div>";
require PUN_ROOT.'footer.php';
?>