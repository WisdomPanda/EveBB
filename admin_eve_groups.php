<?php

// Tell header.php to use the admin template
define('PUN_ADMIN_CONSOLE', 1);

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'include/common_admin.php';


if ($pun_user['g_id'] != PUN_ADMIN)
	message($lang_common['No permission']);

// Load the eve language file
require PUN_ROOT.'lang/'.$admin_language.'/admin_eve_online.php';
	
$action = isset($_GET['action']) ? $_GET['action'] : null;

if ($action == "add_group_rule") {
	if (isset($_POST['form_sent'])) {
		
		if (!isset($_POST['from_group']) || !isset($_POST['to_group']) || !isset($_POST['role']) || !isset($_POST['priority'])) {
			message("Incorrect vars.");
		} //End if.
		
		$from = explode(':', $_POST['from_group']);
		$type = $from[1]; //The type, 0 for corp, 1 for alliance.
		$from = $from[0]; //The actual ID we use.
		//It's possible to just run them through the database to get a match... but meh.
		
		$from = intval($from);
		$type = intval($type);
		$to = intval($_POST['to_group']);
		$priority = intval($_POST['priority']);
		$role = (string)$_POST['role'];
		
		if (($from < 4 && $from !=  0) || $to < 1 || ($type > 1 || $type < 0) || !in_array($role, $api_roles)) {
			message("Incorrect variables sent.");
		} //End if.
		
		//Groups first...
		$sql = "SELECT g_id FROM ".$db->prefix."groups WHERE g_id=".$to.";";
		$result = $db->query($sql) or error("Unable to fetch group information.", __FILE__, __LINE__, $db->error());
		if ($db->num_rows($result) != 1) {
			message("Unable to find the specified group.");
		} //End if.
		
		if ($from > 0) {
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
		} //End if.
		
		$fields = array(
			'id' => $from,
			'group_id' => $to,
			'type' => $type,
			'priority' => $priority,
			'role' => $role
		);
		
		$db->insert_or_update($fields, array('id', 'group_id', 'role'), $db->prefix.'api_groups') or error("Unable to update group rule information.".@current(@end($db->saved_queries)), __FILE__, __LINE__, $db->error());
		
		redirect('admin_eve_groups.php', $lang_admin_eve_online['group_rule_add_redirect']);
		
	} //End if.
} //End if.

if ($action == "del_group") {
	
	$id = intval($_GET['id']);
	$role = $_GET['role'];
	$group = intval($_GET['g_id']);
	$type = intval($_GET['type']);
	
	if (($type > 1 || $type < 0) || !check_numeric($_GET['role'])) {
		message("Incorrect variables sent for delete.");
	} //End if.
	
	$log = '';
	remove_rule($id, $group, $type, $role) or message("Unable to modify group rule information.");
	apply_rules($log);
	redirect('admin_eve_groups.php', $lang_admin_eve_online['group_rule_del_redirect']);
} //End if.

if ($action == 'refresh_rules') {
	$log = '';
	if (!apply_rules($log)) {
		message("Unable to apply rules.");
	} //End if.
	
	message($lang_admin_eve_online['apply_rules'].'<br/>
		<br/>
		<div class="codebox"><pre class="vscroll"><code>'.$log.'</code></pre></div>');
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
										<table>
											<tr>
												<th scope="row">
													<?php echo $lang_admin_eve_online['group_rule_role']; ?>
												</th>
												<td>
													<select id="roles" name="role" tabindex="1">
<?php

foreach ($api_roles as $key => $value) {
		echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$value.'">'.$key.'</option>'."\n";
} //End 'i' for loop.

?>
													</select>
												<td>
											</tr>
											<tr>
												<th scope="row">
													<input type="hidden" name="form_sent" value="1" />
													<?php echo $lang_admin_eve_online['group_rule_members_from']; ?>
												</th>
												<td>
										<select id="from_group" name="from_group" tabindex="1">
											<option value="0:0">Any</option>
											<optgroup label="Corporations">
<?php

$result = $db->query('SELECT corporationname, corporationid FROM '.$db->prefix.'api_allowed_corps WHERE allowed=1 ORDER BY corporationName') or error('Unable to fetch corporation list', __FILE__, __LINE__, $db->error());

while ($corp = $db->fetch_assoc($result))
{
	echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$corp['corporationid'].':0">'.pun_htmlspecialchars($corp['corporationname']).'</option>'."\n";
}

?>
											</optgroup>
<?php

$result = $db->query('SELECT alliancename, allianceid FROM '.$db->prefix.'api_allowed_alliance WHERE allowed=1 ORDER BY alliancename') or error('Unable to fetch corporation list', __FILE__, __LINE__, $db->error());

if ($db->num_rows($result) > 0) {
?>
											<optgroup label="Alliances">
<?php
while ($corp = $db->fetch_assoc($result))
{
	echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$corp['allianceid'].':1">'.pun_htmlspecialchars($corp['alliancename']).'</option>'."\n";
}
?>
											</optgroup>
<?php

} //End if.
?>
													</select><br/>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<?php echo $lang_admin_eve_online['group_rule_members_to']; ?>
												</th>
												<td>
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
												</td>
											</tr>
											<tr>
												<th scope="row">
													<?php echo $lang_admin_eve_online['group_rule_priority']; ?>
												</th>
												<td>
													<select id="priority" name="priority" tabindex="1">
<?php
$iter = (!empty($pun_config['o_eve_max_groups'])) ? $pun_config['o_eve_max_groups'] : 100;
for ($i = 0; $i < $iter; $i++) {
		echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$i.'">'.$i.'</option>'."\n";
} //End 'i' for loop.

?>
													</select>
												<td>
											</tr>
												<tr>
												<td colspan="2">
													<span><?php echo sprintf($lang_admin_eve_online['group_rule_info'], $iter); ?></span>
												</td>
											</tr>
										</table>
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
		ag.*, c.corporationName, c.corporationID, a.allianceName, a.allianceID, g.g_title, g.g_id
	FROM
		'.$db->prefix.'api_groups AS ag
	INNER JOIN
		'.$db->prefix.'groups AS g
	ON
		g.g_id=ag.group_id
	LEFT JOIN
		'.$db->prefix.'api_allowed_alliance AS a
	ON
		a.allianceID=ag.id
	LEFT JOIN
		'.$db->prefix.'api_allowed_corps AS c
	ON
		c.corporationID=ag.id
	ORDER BY
		ag.priority ASC
	';

$result = $db->query($sql) or error('Unable to fetch user group list', __FILE__, __LINE__, $db->error());

while ($row = $db->fetch_assoc($result)) {
	$name = 'Any';
	if (!empty($row['corporationID'])) {
		$name = $row['corporationName'];
	} else if (!empty($row['allianceID'])) {
		$name = $row['allianceName'];
	} //End if - else if.
	
	echo "\t\t\t\t\t\t\t\t".'
	<tr>
		<th scope="row">
			<a href="admin_eve_groups.php?action=del_group&amp;g_id='.$row['g_id'].'&amp;role='.$row['role'].'&amp;id='.$row['id'].'&amp;type='.$row['type'].'">
				'.$lang_admin_eve_online['delete'].'
			</a>
		</th>
		<td>
			&lt;'.array_search($row['role'], $api_roles).'&gt; ['.$row['priority'].'] '.pun_htmlspecialchars($name).' -&gt; '.pun_htmlspecialchars($row['g_title']).'
		</td>
	</tr>'."\n";
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