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

if ($action == 'add_allowed_corp') {
	if (isset($_POST['form_sent'])) {
		if (!isset($_POST['api_corp_id']) || !isset($_POST['api_corp_name']) || strlen($_POST['api_corp_name']) == 0) {
			message("You must select a valid corp.");
		} //End if.
		
		if (!add_corp(intval($_POST['api_corp_id']))) {
			message("Unable to add corp.");
		} //End if.

		task_check_auth();
		apply_rules();
		redirect('admin_eve_corps.php', $lang_admin_eve_online['allowed_corp_add_redirect']);
		
	} //End if.
} //End if.

if ($action == 'del_corp') {
	if (!isset($_GET['corpID']) || !check_numeric($_GET['corpID'])) {
		message("Incorrect vars.");
	} //End if.
	
	$sql = "SELECT corporationID FROM ".$db->prefix."api_allowed_corps WHERE allowed=1;";
	if (!$result = $db->query($sql)) {
		message("Unable to gather corporation information.");
	} //End if.
	
	if ($db->num_rows($result) == 1) {
		message("You can not delete your last remaining corp...");
	} //End if.
	
	if (!purge_corp($_GET['corpID'])) {
		message("Unable to purge corp.<br/>Please insure that there are no administrators in this corp.");
	} //End if.
	
	task_check_auth();
	apply_rules();
	redirect('admin_eve_corps.php', $lang_admin_eve_online['removed_corp_redirect']);
	
} //End if.

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Eve Online']);
$required_fields = array('req_title' => $lang_admin_eve_online['title']);
define('PUN_ACTIVE_PAGE', 'admin');
require PUN_ROOT.'header.php';

generate_admin_menu('eve_corp');

?>
	<div class="plugin blockform">
	
<!-- START ALLOWED LIST -->
		<h2 class="block2"><span><?php echo $lang_admin_eve_online['allowed_corp_title'] ?></span></h2>
		<div class="box">
			<form id="allowed_corp_form" method="post" action="admin_eve_corps.php?action=add_allowed_corp">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_eve_online['allowed_corp_legend'] ?></legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row"><?php echo $lang_admin_eve_online['allowed_corp'] ?><div><input type="submit" name="show_text" value="<?php echo $lang_admin_eve_online['allowed_corp_add'] ?>" tabindex="2" /></div></th>
									<td>
										<input type="hidden" name="form_sent" value="1" />
										<input type="text" id="api_corp_id" name="api_corp_id" size="25" tabindex="1" value=""/><br />
										<?php echo $lang_admin_eve_online['allowed_corp_id']; ?><br/>
										<br />
										<span id="api_holder"><a class="fetch_corp" href="index.php" onClick="fetchCorp(); return false;"><span id="corp_fetch_text"><?php echo $lang_admin_eve_online['corp_fetch']; ?></span></a></span>
										<br />
										<span><?php echo $lang_admin_eve_online['allowed_corp_info'] ?></span>
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
						<legend><?php echo $lang_admin_eve_online['delete_allowed_corp_legend'] ?></legend>
						<div class="infldset">
							<p><?php echo $lang_admin_eve_online['delete_allowed_corp'] ?></p>
							<table class="aligntop" cellspacing="0">
<?php

$sql = "SELECT c.corporationName, c.corporationID, c.allianceID FROM ".$db->prefix."api_allowed_corps AS c WHERE allowed=1 ORDER BY c.allianceID, c.corporationID;";

$result = $db->query($sql) or error('Unable to fetch corp list', __FILE__, __LINE__, $db->error());
if ($db->num_rows($result) > 1) {
	while ($row = $db->fetch_assoc($result)) {
		echo "\t\t\t\t\t\t\t\t".'<tr><th scope="row"><a href="admin_eve_corps.php?action=del_corp&amp;corpID='.$row['corporationID'].'">'.$lang_admin_eve_online['delete'].'</a></th><td>'.pun_htmlspecialchars($row['corporationName']).'</td></tr>'."\n";
	} //End while loop.
} else {
	$row = $db->fetch_assoc($result);
	echo "\t\t\t\t\t\t\t\t".'<tr><th scope="row">'.$lang_admin_eve_online['delete'].'</th><td>'.pun_htmlspecialchars($row['corporationName']).'</td></tr>'."\n";
} //End if - else.
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