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

if ($action == "add_group_rule") {
	if (isset($_POST['form_sent'])) {
		
		if (!isset($_POST['from_group']) || !isset($_POST['to_group'])) {
			message("Incorrect vars.");
		} //End if.
		
		$from = explode(':', $_POST['from_group']);
		$type = $from[1]; //The type, 0 for corp, 1 for alliance.
		$from = $from[0]; //The actual ID we use.
		//It's possible to just run them through the database to get a match... but meh.
		
		$from = intval($from);
		$type = intval($type);
		$to = intval($_POST['to_group']);
		
		if ($from < 4 || $to < 1 || ($type > 1 && $type < 0)) {
			message("Incorrect variables sent.");
		} //End if.
		
		//Groups first...
		$sql = "SELECT g_id FROM ".$db->prefix."groups WHERE g_id=".$to.";";
		$result = $db->query($sql) or error("Unable to fetch group information.", __FILE__, __LINE__, $db->error());
		if ($db->num_rows($result) != 1) {
			message("Unable to find the specified group.");
		} //End if.
		
		
		//Now corp/ally...
		if ($type == 0) {
			$sql = "SELECT corporationID FROM ".$db->prefix."api_allowed_corps WHERE corporationID=".$from.";";
			$result = $db->query($sql) or error("Unable to fetch corporation information.", __FILE__, __LINE__, $db->error());
			if ($db->num_rows($result) != 1) {
				message("Unable to find the specified corporation.<br/> Check that you have allowed this corporation to use the forum first.");
			} //End if.
		} else {
			$sql = "SELECT allianceID FROM ".$db->prefix."api_allowed_alliance WHERE allianceID=".$from.";";
			$result = $db->query($sql) or error("Unable to fetch alliance information.", __FILE__, __LINE__, $db->error());
			if ($db->num_rows($result) != 1) {
				message("Unable to find the specified alliance.<br/> Check that you have allowed this alliance to use the forum first.");
			} //End if.
		} //End if - else.
		
		$sql = "INSERT INTO ".$db->prefix."api_groups(id, group_id, type) VALUES(".$from.",".$to.", ".$type.") ON DUPLICATE KEY UPDATE id=".$from.";"; //Duplicate, just update (does nothing) and keep it quiet.
		$result = $db->query($sql) or error("Unable to update group rule information.", __FILE__, __LINE__, $db->error());
		
		redirect('admin_eve_groups.php', $lang_admin_eve_online['group_rule_add_redirect']);
		
	} //End if.
} //End if.

if ($action == "del_group") {
	
	$corp = intval($_GET['corp_id']);
	$group = intval($_GET['g_id']);
	$type = intval($_GET['type']);
	
	if ($corp < 1 || $group < 4 || ($type > 1 || $type < 0)) {
		message("Incorrect variables sent.");
	} //End if.
	
	remove_rule($corp, $group, $type) or message("Unable to modify group rule information.");
	apply_rules();
	redirect('admin_eve_groups.php', $lang_admin_eve_online['group_rule_del_redirect']);
} //End if.

if ($action == 'refresh_rules') {
	if (!apply_rules()) {
		message("Unable to apply rules.");
	} //End if.
	
	redirect('admin_eve_groups.php', $lang_admin_eve_online['apply_rules_redirect']);
} //End if.

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Eve Online']);
$required_fields = array('req_title' => $lang_admin_eve_online['title']);
define('PUN_ACTIVE_PAGE', 'admin');
require PUN_ROOT.'header.php';

generate_admin_menu('eve_groups');

?>

	<div class="plugin blockform">
		<h2 class="block2"><span><?php echo $lang_admin_eve_online['group_rule_title'] ?></span></h2>
		<div class="box">
			<form id="groups" method="post" action="admin_eve_groups.php?action=add_group_rule">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_eve_online['group_rule_legend'] ?></legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row"><?php echo $lang_admin_eve_online['group_rule'] ?><div><input type="submit" name="show_text" value="<?php echo $lang_admin_eve_online['group_rule_add'] ?>" tabindex="2" /></div></th>
									<td>
										<input type="hidden" name="form_sent" value="1" />
										<?php echo $lang_admin_eve_online['group_rule_members_from']; ?>
										<select id="from_group" name="from_group" tabindex="1">
											<optgroup label="Corporations">
<?php

$result = $db->query('SELECT corporationName, corporationID FROM '.$db->prefix.'api_allowed_corps WHERE allowed=1 ORDER BY corporationName') or error('Unable to fetch corporation list', __FILE__, __LINE__, $db->error());

while ($corp = $db->fetch_assoc($result))
{
	echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$corp['corporationID'].':0">'.pun_htmlspecialchars($corp['corporationName']).'</option>'."\n";
}

?>
											</optgroup>
<?php

$result = $db->query('SELECT allianceName, allianceID FROM '.$db->prefix.'api_allowed_alliance WHERE allowed=1 ORDER BY allianceName') or error('Unable to fetch corporation list', __FILE__, __LINE__, $db->error());

if ($db->num_rows($result) > 0) {
?>
											<optgroup label="Alliances">
<?php
while ($corp = $db->fetch_assoc($result))
{
	echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$corp['allianceID'].':1">'.pun_htmlspecialchars($corp['allianceName']).'</option>'."\n";
}
?>
											</optgroup>
<?php

} //End if.
?>
										</select>
										<?php echo $lang_admin_eve_online['group_rule_members_to']; ?>
										<select id="to_group" name="to_group" tabindex="1">
<?php

$result = $db->query('SELECT g_id, g_title FROM '.$db->prefix.'groups WHERE g_id!='.PUN_ADMIN.' AND g_id!='.PUN_GUEST.' ORDER BY g_title') or error('Unable to fetch user group list', __FILE__, __LINE__, $db->error());

$groups = array();

while ($cur_group = $db->fetch_assoc($result))
{
	$groups[] = $cur_group;
	if ($cur_group['g_id'] == $pun_config['o_default_user_group'])
		echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.pun_htmlspecialchars($cur_group['g_title']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.pun_htmlspecialchars($cur_group['g_title']).'</option>'."\n";
}

?>
										</select>
										<span><?php echo $lang_admin_eve_online['group_rule_info'] ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
			</form>
			<form action="">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_eve_online['delete_group_rule_legend'] ?></legend>
						<div class="infldset">
							<p><?php echo $lang_admin_eve_online['delete_group_rule'] ?></p>
							<table class="aligntop" cellspacing="0">
<?php

$sql = '
	SELECT
		ag.*,
		g.g_id,
		g.g_title,
		c.corporationID,
		c.corporationName
	FROM
		'.$db->prefix.'api_groups AS ag,
		'.$db->prefix.'api_allowed_corps AS c,
		'.$db->prefix.'groups AS g
	WHERE
		ag.group_id=g.g_id
	AND
		ag.id=c.corporationID
	AND
		ag.type=0
	';

$result = $db->query($sql) or error('Unable to fetch user group list', __FILE__, __LINE__, $db->error());

while ($row = $db->fetch_assoc($result)) {
	echo "\t\t\t\t\t\t\t\t".'<tr><th scope="row"><a href="admin_eve_groups.php?action=del_group&amp;g_id='.$row['g_id'].'&amp;corp_id='.$row['corporationID'].'&amp;type=0">'.$lang_admin_eve_online['delete'].'</a></th><td>'.pun_htmlspecialchars($row['corporationName']).' -&gt; '.pun_htmlspecialchars($row['g_title']).'</td></tr>'."\n";
} //End while loop.

$sql = '
	SELECT
		ag.*,
		g.g_id,
		g.g_title,
		a.allianceID,
		a.allianceName
	FROM
		'.$db->prefix.'api_groups AS ag,
		'.$db->prefix.'api_allowed_alliance AS a,
		'.$db->prefix.'groups AS g
	WHERE
		ag.group_id=g.g_id
	AND
		ag.id=a.allianceID
	AND
		ag.type=1';

$result = $db->query($sql) or error('Unable to fetch user group list', __FILE__, __LINE__, $db->error());

while ($row = $db->fetch_assoc($result)) {
	echo "\t\t\t\t\t\t\t\t".'<tr><th scope="row"><a href="admin_eve_groups.php?action=del_group&amp;g_id='.$row['g_id'].'&amp;corp_id='.$row['allianceID'].'&amp;type=1">'.$lang_admin_eve_online['delete'].'</a></th><td>'.pun_htmlspecialchars($row['allianceName']).' -> '.pun_htmlspecialchars($row['g_title']).'</td></tr>'."\n";
} //End while loop.
?>
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