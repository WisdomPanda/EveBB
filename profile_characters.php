<?php

if (!defined('FROM_PROFILE')) {
	message("Failed to access characters.");
} //End if.

//Get our language file.
require PUN_ROOT.'lang/'.$pun_user['language'].'/profile_characters.php';

/**
 * Logic stuff goes here.
 */

if ($action == 'select_character') {
	if (isset($_POST['form_sent_characters'])) {
		
		$character_id = intval($_POST['select_character']);
		
		if (!select_character($id, $character_id)) {
			message("Unable to select that character.");
		} //End if.
		
		apply_rules();
		
		redirect('profile.php?section=characters&amp;id='.$id, $lang_profile_characters['select_redirect']);
	} //End if.
} //End if.

if ($action == 'add_character') {
	if (isset($_POST['form_sent_characters'])) {
		
		if (!isset($_POST['api_character_id']) | !isset($_POST['api_user_id']) || !isset($_POST['api_key'])) {
			message("Incorrect vars.");
		} //End if.
		
		$api_character_id = intval($_POST['api_character_id']);
		$api_user_id = intval($_POST['api_user_id']);
		$api_key = strip_special($_POST['api_key']);
		
		if ($api_character_id == 0 || $id == 0 || $api_user_id == 0 || strlen($api_key) == 0) {
			message("Malformed Vars.");
		} //End if.
		
		//Now we make sure we can access the character sheet...
		$auth = array('userID' => $api_user_id,'characterID' => $api_character_id,'apiKey' => $api_key);
		if (!$char = fetch_character_api($auth)) {
			message("Unable to fetch character information.");
		} //End if.
		
		//Ok, now we're in business.
		add_api_keys($id, $api_user_id, $api_character_id, $api_key);
		update_character_sheet($id, array(), $char); //Pass it a dummy array and our already fetched character sheet.
		
		redirect('profile.php?section=characters&amp;id='.$id, $lang_profile_characters['add_redirect']);
	} //End if.
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
	$error = array();

	$sql = "
		SELECT
			sc.*,
			c.*
		FROM
			".$db->prefix."api_selected_char AS sc,
			".$db->prefix."api_characters AS c
		WHERE
			sc.user_id=".$id."
		AND
			sc.character_id=c.character_id
	";
	
	if (!$result = $db->query($sql)) {
		$error[] = "Unable to fetch your character.";
		message("Unable to fetch character.");
	} //End if.
	
	if ($db->num_rows($result) == 0) {
		$error[] = "Unable to find your character.";
	} //End if.
	
	if (empty($error)) {
	$selected_char = $db->fetch_assoc($result);
	
	if (!file_exists('img/chars/'.$selected_char['character_id'].'_64.jpg') || !file_exists('img/chars/'.$selected_char['character_id'].'_128.jpg')) {
		cache_char_pic($selected_char['character_id']);
	} //End if.

?>
	<h2><span><?php echo $lang_profile_characters['characters'] ?></span></h2>
	<div class="box">
		<form id="dummy_character_form" method="post" action="profile.php?section=characters&amp;id=<?php echo $id ?>">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_profile_characters['selected_char'] ?></legend>
					<div class="infldset">
						<table class="aligntop" cellspacing="0">
							<tr>
								<th scope="row" rowspan="6" style="width: 128px;"><img src="img/chars/<?php echo $selected_char['character_id']; ?>_128.jpg" width="128px" height="128px" alt="" /></th>
								<td><strong><?php echo $lang_profile_characters['name']; ?></strong></td>
								<td><?php echo $selected_char['character_name']; ?></td>
							</tr>
							<tr>
								<td><strong><?php echo $lang_profile_characters['corp']; ?></strong></td>
								<td><?php echo $selected_char['corp_name']; ?></td>
							</tr>
							<tr>
								<td><strong><?php echo $lang_profile_characters['ally']; ?></strong></td>
								<td><?php echo $selected_char['ally_name']; ?></td>
							</tr>
							<tr>
								<td><strong><?php echo $lang_profile_characters['race']; ?></strong></td>
								<td><?php echo $selected_char['race']; ?></td>
							</tr>
							<tr>
								<td><strong><?php echo $lang_profile_characters['blood_line']; ?></strong></td>
								<td><?php echo $selected_char['blood_line']; ?></td>
							</tr>
							<tr>
								<td><strong><?php echo $lang_profile_characters['ancestry']; ?></strong></td>
								<td><?php echo $selected_char['ancestry']; ?></td>
							</tr>
							<tr>
								<td rowspan="3">&nbsp;</td>
								<td><strong><?php echo $lang_profile_characters['dob']; ?></strong></td>
								<td><?php echo $selected_char['dob']; ?></td>
							</tr>
							<tr>
								<td><strong><?php echo $lang_profile_characters['clone']; ?></strong></td>
								<td><?php echo $selected_char['clone_name']; ?> (<?php echo number_format($selected_char['clone_sp']); ?> SP)</td>
							</tr>
							<tr>
								<td><strong><?php echo $lang_profile_characters['wallet']; ?></strong></td>
								<td><?php echo number_format($selected_char['balance']); ?> Isk</td>
							</tr>
						</table>
					</div>
				</fieldset>
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

$sql = "SELECT * FROM ".$db->prefix."api_characters WHERE user_id=".$id.";";
$result = $db->query($sql) or message("Unable to fetch character list.");

while ($row = $db->fetch_assoc($result)) {
	echo '
							<tr>
								<th scope="row" style="width: 64px;"><img src="img/chars/'.$row['character_id'].'_64.jpg" width="64px" height="64px" alt="" /></th>
								<td>
									<strong>'.$row['character_name'].'</strong><br/>
									<em>'.$row['corp_name'].'</em><br/>
									'.$row['ally_name'].'
								</td>
								<td><input type="radio" name="select_character" value="'.$row['character_id'].'" '.(($row['character_id'] == $selected_char['character_id']) ? ' checked="checked"' : '').' />&#160;<strong>'.$lang_profile_characters['select'].'</strong></td>
							</tr>';
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
						<label class="required"><strong>API UserID <span>(Required)</span></strong><br /><input id="api_user_id" type="text" name="api_user_id" value="<?php echo pun_htmlspecialchars($api_user_id) ?>" size="50" maxlength="80" /><br /></label>
						<label class="required"><strong>API Key <span>(Required)</span></strong><br /><input id="api_key" type="text" name="api_key" value="<?php echo pun_htmlspecialchars($api_key) ?>" size="50" maxlength="80" /><br /></label><br/>
						<span id="api_holder"><a class="fetch_chars" href="index.php" onclick="fetchCharacters(); return false;"><span id="char_fetch_text"><?php echo $lang_profile_characters['fetch_chars']; ?></span></a></span>
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