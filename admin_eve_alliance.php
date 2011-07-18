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

if ($action == 'refresh_alliance_list') {
	
	if (!refresh_alliance_list()) {
		message('Unable to refresh alliance list.');
	} //End if.
	
	$sql = "SELECT * FROM ".$db->prefix."api_allowed_alliance";
	$result = $db->query($sql) or error("Unable to fetch current alliance list.");
	
	while ($row = $db->fetch_assoc($result)) {
		add_alliance($row['allianceID']);
	} //End while loop.

	task_check_auth();
	apply_rules();
	
	redirect('admin_eve_alliance.php', $lang_admin_eve_online['alliance_list_refresh_redirect']);
	
} //End if.

if ($action == 'add_allowed_alliance') {
	if (isset($_POST['form_sent'])) {
		if (check_numeric($_POST['allowed_alliance'])) {
			if (!add_alliance(intval($_POST['allowed_alliance']))) {
				message("Unable to add alliance.");
			} else {
				task_check_auth();
				apply_rules();
				redirect('admin_eve_alliance.php', $lang_admin_eve_online['allowed_alliance_redirect']);
			} //End if - else.
		} else {
			message("Incorrect vars.");
		} //End if - else.
	} //End if.
} //End if.

if ($action == 'del_alliance') {
	if (!isset($_GET['allianceID']) || !check_numeric($_GET['allianceID'])) {
		message("Incorrect vars.");
	} //End if.
	
	if (!purge_alliance($_GET['allianceID'], $pun_user['corp_id'])) {
		message("Unable to purge alliance.");
	} //End if.
	
	task_check_auth();
	apply_rules();
	redirect('admin_eve_alliance.php', $lang_admin_eve_online['removed_alliance_redirect']);
	
} //End if.

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Eve Online']);
$required_fields = array('req_title' => $lang_admin_eve_online['title']);
define('PUN_ACTIVE_PAGE', 'admin');
require PUN_ROOT.'header.php';

generate_admin_menu('eve_ally');

?>
	<div class="plugin blockform">
	
<!-- START ALLOWED LIST -->
		<h2 class="block2"><span><?php echo $lang_admin_eve_online['allowed_title'] ?></span></h2>
		<div class="box">
			<form id="allowed_alliance_form" method="post" action="admin_eve_alliance.php?action=add_allowed_alliance">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_eve_online['allowed_alliance_legend'] ?></legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row"><?php echo $lang_admin_eve_online['allowed_alliance'] ?><div><input type="submit" name="show_text" value="<?php echo $lang_admin_eve_online['allowed_alliance_add'] ?>" tabindex="2" /></div></th>
									<td>
										<input type="hidden" name="form_sent" value="1" />
										<select id="allowed_alliance" name="allowed_alliance" tabindex="1">
<?php

$result = $db->query("SELECT * FROM ".$db->prefix."api_alliance_list ORDER BY name") or error('Unable to fetch alliance list', __FILE__, __LINE__, $db->error());

while ($row = $db->fetch_assoc($result)) {
	echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$row['allianceID'].'">'.pun_htmlspecialchars($row['name']).'</option>'."\n";
} //End while loop.

?>
										</select>
										<span><?php echo $lang_admin_eve_online['allowed_alliance_info'] ?></span>
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
						<legend><?php echo $lang_admin_eve_online['delete_allowed_legend'] ?></legend>
						<div class="infldset">
							<p><?php echo $lang_admin_eve_online['delete_allowed'] ?></p>
							<table class="aligntop" cellspacing="0">
<?php

$sql = 'SELECT a.* FROM '.$db->prefix.'api_allowed_alliance AS a WHERE a.allowed=1 ORDER BY allianceName';

$result = $db->query($sql) or error('Unable to fetch alliance list', __FILE__, __LINE__, $db->error());

while ($row = $db->fetch_assoc($result)) {
	echo "\t\t\t\t\t\t\t\t".'<tr><th scope="row"><a href="admin_eve_alliance.php?action=del_alliance&amp;allianceID='.$row['allianceID'].'">'.$lang_admin_eve_online['delete'].'</a></th><td><strong>'.pun_htmlspecialchars($row['allianceName']).'</strong><br />';
	
	
	$sql = "SELECT c.corporationName, c.corporationID, c.allianceID FROM ".$db->prefix."api_allowed_corps AS c WHERE c.allowed=1 AND c.allianceID=".$row['allianceID']." ORDER BY c.corporationName;";
	
	$corp_result = $db->query($sql) or error('Unable to fetch corp list', __FILE__, __LINE__, $db->error());
	echo '<ul class="bblist">';
	while ($corp = $db->fetch_assoc($corp_result)) {
		echo '<li>'.pun_htmlspecialchars($corp['corporationName']).'</li>';
		echo "\n";
	} //End while loop.
	
	echo '</ul></td></tr>'."\n";
	
} //End while loop.
?>
							</table>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
<!-- END ALLOWED LIST -->
	</div>
		<div class="clearer"></div>
</div>
<?php
require PUN_ROOT.'footer.php';
?>