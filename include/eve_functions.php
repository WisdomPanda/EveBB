<?php

if (!defined('EVE_ENABLED')) {
	exit('Must be called locally.');
} //End if.


require(PUN_ROOT.'include/eve_alliance_functions.php');
require(PUN_ROOT.'include/api/api_common.php');
require(PUN_ROOT.'include/bcmath.php');

//Define some values.
define('API_SERVER_DOWN', 1000);
define('API_BAD_REQUEST', 1001);
define('API_BAD_AUTH', 1002);
define('API_SERVER_ERROR', 1003);
define('API_ACCOUNT_STATUS', 1004);

$_LAST_ERROR = 0;

/**
 * This function handles all our task running needs.
 * Just like to group similar things together in a function pretty much.
 */
function task_runner() {
	global $db, $pun_config;
	
	$run_skills = $run_auth = $run_ally = $run_rules = $run_char = $force_char = false;
	$action = $_GET['action'];
	$log = array();
	
	if (defined('EVE_CRON_ACTIVE')) {
		//We're running a cron job, so it's safe to run the more lengthy tasks.
		if ($action == 'update_characters') {
			$run_char = true;
		} //End if.
		
		if ($action == 'update_alliance') {
			$run_ally = true;
		} //End if.
		
		if ($action == 'update_skills') {
			$run_skills = true;
		} //End if.
		
		if ($action == 'update_all') {
			$run_skills = $run_ally = $run_char = true;
		} //End if.
		
	} else if ($pun_config['o_eve_use_cron'] == '0') {
		//We need to fetch any characters that need to be updated.
		$sql = "SELECT last_update FROM  ".$db->prefix."api_characters WHERE last_update<".(time()-($pun_config['o_eve_cache_char_sheet_interval']*60*60))." ORDER BY  last_update DESC LIMIT 0,1";
		if (!$result = $db->query($sql)) {
			if (defined('PUN_DEBUG')) {
				error("Unable to fetch corp list.", __FILE__, __LINE__, $db->error());
			} //End if.
		} else {
			if ($db->num_rows($result) == 1) {
				$run_char = true; //There is at least one character that needs to be updated.
			} //End if.
		} //End if - else.
	} else {
		return; //Not time to do anything.
	} //End if - else.
	
	if ($pun_config['o_eve_use_cron'] == '0') {
	
		if (!isset($pun_config['o_eve_last_auth_check']) || !isset($pun_config['o_eve_last_rule_check'])) {
			$run_auth = $run_rules = true;
		} else {
			//See if it's time to check them...
			if ($pun_config['o_eve_last_auth_check'] < time()-($pun_config['o_eve_auth_interval']*60*60)) {
				$run_auth = true;
			} //End if.
			
			if ($pun_config['o_eve_last_rule_check'] < time()-($pun_config['o_eve_rules_interval']*60*60)) {
				$run_rules = true;
			} //End if.
		} //End if - else.
	
	} //End if.
	
	//For debugging...
	//$run_auth = $run_rules = true;
	
	if ($run_auth) {
		if (!task_check_auth()) {
			$log[] = 'Unable to complete auth check!<br/>';
		} else {
			$log[] = 'Auth checked!<br/>';
		} //End if - else.
		$db->insert_or_update(array('conf_name' => 'o_eve_last_auth_check', 'conf_value' => time()), 'conf_name', $db->prefix.'config');
	} //End if.
	
	if ($run_rules) {
		$ignore = '';
		if (!apply_rules($ignore)) {
			$log[] = 'Unable to complete rule check!<br/>';
		} else {
			$log[] = 'Rule applied!<br/>';
		} //End if - else.
		$db->insert_or_update(array('conf_name' => 'o_eve_last_rule_check', 'conf_value' => time()), 'conf_name', $db->prefix.'config');
	} //End if.
	
	if ($run_char) {
		if (isset($_GET['force_char'])) {
			$force_char = true;
		} //End if.
		$log = array_merge($log, task_update_characters(1, defined('EVE_CRON_ACTIVE'), $force_char));
	} //End if.
	
	if ($run_ally) {
		$log = array_merge($log, task_update_alliance());
	} //End if.
	
	if ($run_skills) {
		$log = array_merge($log, task_update_skills(defined('EVE_CRON_ACTIVE')));
	} //End if.
	
	if ($run_auth || $run_rules) {
		//We ran one or the other, so now we need to rebuild the config.
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
			require PUN_ROOT.'include/cache.php';
		} //End if.
		
		generate_config_cache();
		$log[] = "Config rebuilt.";
	} //End if.
	
	return $log;
	
} //End task_runner().

/**
 * Updates the list of currently training skills for the selected characters.
 *
 */
function task_update_skills($cron = false, $character = 0) {
	global $db, $pun_config, $lang_eve_bb, $_LAST_ERROR;
	
	$sql = "
		SELECT
			c.character_name,
			c.last_update,
			c.character_id,
			a.*,
			s.last_update
		FROM
			".$db->prefix."api_characters AS c
		INNER JOIN
			".$db->prefix."api_auth AS a
		ON
			a.api_character_id=c.character_id
		LEFT JOIN
			".$db->prefix."api_skill_queue AS s
		ON
			s.character_id=c.character_id";
	
	if (!$cron && $character == 0) {
		$sql .= " WHERE s.last_update<".(time()-($pun_config['o_eve_cache_char_sheet_interval']*60*60)); //(update_time - (time-x hours)) x being set in config.
	} else if ($character != 0) {
		$sql .= " WHERE c.character_id=".$character;
	} //End if - else if.
	
	$sql .= " ORDER BY s.last_update ASC";
	
	if (!$result = $db->query($sql)) {
		$err = $db->error();
		$log[] = "Unable to fetch character skill information.<br/>".$err['error_msg'].'<br/>'.$sql;
		return $log;
	} //End if.
	
	$log = array();
	
	$_LAST_ERROR = 0;
	
	while ($row = $db->fetch_assoc($result)) {
		$now = time();
		$char = new Character();
		$cak = new CAK($row['api_user_id'],$row['api_key'],$row['api_character_id']);
		if ($char->load_skill_queue($cak)) {
			
			//Purge old skills
			$db->query("DELTE FROM ".$db->prefix."api_skill_queue WHERE character_id=".$row['api_character_id']);

			
			foreach($char->skillQueue as $skill) {
				
				$fields = array(
					'character_id' => $row['character_id'],
					'queuePosition' => $skill['queuePosition'],
					'typeID' => $skill['typeID'],
					'level' => $skill['level'],
					'startSP' => $skill['startSP'],
					'endSP' => $skill['endSP'],
					'startTime' => $skill['startTime'],
					'endTime' => $skill['endTime'],
					'last_update' => $now,
				);
				
				if (!$db->insert_or_update($fields, array('character_id', 'typeID'), $db->prefix."api_skill_queue")) {
					$log[] = sprintf("[%s] %s - Unable to add skill.".print_r($db->error, true), $row['character_id'], $row['character_name']);
				} //End if.
				
			} //End foreach().
			
			$log [] = sprintf($lang_eve_bb['skill_queue_updated'], $row['character_id'], $row['character_name']);
		} else {
			if ($_LAST_ERROR == API_BAD_AUTH) {
				$log [] = sprintf($lang_eve_bb['char_sheet_failed'], $row['character_id'], $row['character_name']);
			} else if ($_LAST_ERROR == API_BAD_FETCH || $_LAST_ERROR == API_SERVER_ERROR) {
				if (defined('PUN_DEBUG')) {
					$log [] = sprintf("[%s] %s - Unable to fetch API data.", $row['character_id'], $row['character_name']);
				} //End if.
			} else if ($_LAST_ERROR == API_SERVER_DOWN) {
				if (defined('PUN_DEBUG')) {
					$log [] = sprintf("[%s] %s - API Server is down, aborting future tries.", $row['character_id'], $row['character_name']);
				} //End if.
				return $log; //Die early, as there is little point us trying to get the rest.
			} //End if.
			
		} //End if - else.
	} //End while loop.
	$_LAST_ERROR = 0;
	return $log;
} //End update_skills.

/**
 * Fetchs all API's in need of update and updates them.
 * Best to run this via cronjob, as it will be waiting on the servers end.
 * force will override the default behaviour of only getting characters that require updates.
 */
function task_update_characters($limit = 1, $force = false, $full_force = false) {
	global $db, $pun_config, $lang_eve_bb, $_LAST_ERROR;
	
	$sql = "SELECT c.character_name,c.last_update,c.character_id,a.* FROM ".$db->prefix."api_characters AS c,".$db->prefix."api_auth AS a WHERE a.api_character_id=c.character_id ";
	
	if (!$full_force) {
		$sql .= " AND c.last_update<".(time()-($pun_config['o_eve_cache_char_sheet_interval']*60*60)); //(update_time - (time-x hours)) x being set in config.
	} //End if.
	
	$log = array();
	
	if (!$result = $db->query($sql)) {
		$err = $db->error();
		$log[] = "Unable to fetch character information.<br/>".$err['error_msg'];
		return $log;
	} //End if.
	
	
	$log[] = "Starting the character update on [".$db->num_rows($result)."] characters.<br/><br/>";
	
	$_LAST_ERROR = 0;
	
	
	while ($row = $db->fetch_assoc($result)) {
		$cak = new CAK($row['api_user_id'],$row['api_key'],$row['api_character_id']);
		if (update_character_sheet($row['user_id'], $cak, false)) {
			$log [] = sprintf($lang_eve_bb['char_sheet_updated'], $row['character_id'], $row['character_name']);
		} else {
			
			if ($_LAST_ERROR == API_ACCOUNT_STATUS) {
				$log [] = sprintf("[%s] %s - API account is inactive, purging.", $row['character_id'], $row['character_name']);
				purge_unclean(array($row['user_id']), $pun_config['o_eve_restricted_group']);
				remove_api_keys($row['user_id']);
			} else if ($_LAST_ERROR == API_BAD_AUTH) {
				$log [] = sprintf($lang_eve_bb['char_sheet_failed'], $row['character_id'], $row['character_name']);
			} else if ($_LAST_ERROR == API_BAD_FETCH || $_LAST_ERROR == API_SERVER_ERROR) {
				if (defined('PUN_DEBUG')) {
					$log [] = sprintf("[%s] %s - Unable to fetch API data.", $row['character_id'], $row['character_name']);
				} //End if.
			} else if ($_LAST_ERROR == API_SERVER_DOWN) {
				if (defined('PUN_DEBUG')) {
					$log [] = sprintf("API Server is down.");
				} //End if.
				return $log; //Die early, as there is little point us trying to get the rest.
			} //End if.
			
		} //End if - else.
		$_LAST_ERROR = 0;
	} //End while loop.
	return $log;
} //End task_update_characters().

/**
 * Checks to see if a user is not in the corp/alliance specified, and if they aren't move them to an inactive status.
 *
 */
function task_check_auth() {
	global $db, $pun_config, $lang_common;
				
	//End any open transactions, incase we've recently added corps or the likes.
	$db->end_transaction();
	
	//Start a new one...
	$db->start_transaction();
	
	$sql = "
		SELECT
			noauth.user_id,
			noauth.character_id,
			user.group_id
		FROM
			".$db->prefix."api_selected_char AS noauth
		INNER JOIN
			".$db->prefix."users AS user
		ON
			noauth.user_id=user.id
		WHERE
			user.group_id != ".PUN_ADMIN."
		AND
			noauth.character_id
		NOT IN
			(
				SELECT
					s.character_id
				FROM
					".$db->prefix."api_selected_char AS s
				INNER JOIN
					".$db->prefix."api_characters AS c
				ON
					s.character_id=c.character_id
				INNER JOIN
					".$db->prefix."api_allowed_corps AS ac
				ON
					ac.corporationID=c.corp_id
			)";
	if (!$result = $db->query($sql)) {
		return false;
	} //End if.
	
	if ($db->num_rows($result) == 0) {
		return true;
	} //End if.
	
	$users = array();
	
	while ($row = $db->fetch_assoc($result)) {
		$users[] = $row;
	} //End while loop.
	
	//OMGAH! UUUUNNNNNNCLLLLLEEEEEEEAAAAAAAANNNNNNNNNNNNNZZZZZ
	//PURRRRGGGGEEEEEEE THHHHHEEEEEEEMMMMMMMMMMMMMMM
	
	return purge_unclean($users, $pun_config['o_eve_restricted_group']);
	
} //End task_check_auth().


/**
 * Purges corp groups of the unclean heathens.
 */
function purge_unclean($users, $group_id) {
	global $db, $lang_common;
	
	$log = array();
	$hook = (count($_HOOKS['rules']) > 0) ? true : false;
	
	foreach ($users as $row) {
		
		$pass = false;
		
		if ($hook) {
			foreach ($_HOOKS['rules'] as $hook) {
				$pass = $hook->restrict_user($row);
			} //End foreach().
		} //End if.
		
		if ($pass !== true) {
			$sql = "UPDATE ".$db->prefix."users SET group_id=".$group_id." WHERE id=".$row['user_id']." AND group_id!=".PUN_ADMIN.";";
			$sql1 = "DELETE FROM ".$db->prefix."groups_users WHERE user_id=".$row['user_id'];
			if (!$db->query($sql) || !$db->query($sql1)) {
				$log[] = sprintf($lang_common['eve_purge_user_failed'], $row['character_name']);
				continue;
			} //End if.
			$log[] = sprintf($lang_common['eve_purge_user_done'], $row['character_name']);
		} //End if.
		
	} //End foreach.
	
	return $logs;
	
} //End purge_unclean.

function check_rules() {
	global $db;
	
	//Get the rules first..
	$sql = "SELECT * FROM ".$db->prefix."api_groups";
	if (!$rules = $db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable fetch group rules.<br/>".$sql."<br/>", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	//Now we get the user defined groups.
	$sql = "SELECT g_id FROM ".$db->prefix."groups";
	if (!$groups = $db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable fetch groups.<br/>".$sql."<br/>", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.

	//Allowed corps...
	$sql = "SELECT corporationID FROM ".$db->prefix."api_allowed_corps";
	if (!$corps = $db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable fetch groups.<br/>".$sql."<br/>", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	//And finally, the alliances.
	$sql = "SELECT allianceID FROM ".$db->prefix."api_allowed_alliance";
	if (!$allies = $db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable fetch groups.<br/>".$sql."<br/>", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	//Going to do all the fetching in one loop!
	//Other alternatives in my head right now are looping individually, or using the database to run SQL statemets on a per-rule basis.
	//Also thinking about altering the DB layer to get better support for it there.
	//I am loathed to add more loops.
	//Marked it for clean up either way.
	$corp_more = $ally_more = $group_more = true; //Our controls.
	$corp_temp = $ally_temp = $group_temp = false; //Mainly for peace of mind, we could use the *_more's
	$corp_ids = array();
	$ally_ids = array();
	$group_ids = array();
	
	while ($corp_more || $ally_more || $group_more) {
		
		if ($corp_more) {
			if ($corp_temp = $db->fetch_assoc($corps)) {
				$corp_ids[] = $corp_temp['corporationid'];
			} else {
				$corp_more = false;
			} //End if - else.
		} //End if.
		
		if ($group_more) {
			if ($group_temp = $db->fetch_assoc($groups)) {
				$group_ids[] = $group_temp['g_id'];
			} else {
				$group_more = false;
			} //End if - else.
		} //End if.
		
		if ($ally_more) {
			if ($ally_temp = $db->fetch_assoc($allies)) {
				$ally_ids[] = $ally_temp['allianceid'];
			} else {
				$ally_more = false;
			} //End if - else.
		} //End if.
		
	} //End while loop.
	
	//Now for the actual work loop.
	//As you can see, we have commented out errors.
	//These were put in place for debugging only as they do not actually represent a hard error, rather an error of logic.
	//Seriously, don't uncomment these.
	while ($row = $db->fetch_assoc($rules)) {
		
		if (!in_array($row['group_id'], $group_ids)) {
			//error("Rule not found to be valid: No group found.", __FILE__, __LINE__);
			remove_rule($row['id'], $row['group_id']);
			continue;
		} //End if.
		
		if ($row['id'] == 0) {
			continue; //No issue here.
		} //End if.
		
		if ($row['type'] == 0) {
			if (!in_array($row['id'], $corp_ids)) {
				//error("Rule not found to be valid: No corp id.", __FILE__, __LINE__);
				remove_rule($row['id'], $row['group_id']);
			} //End if.
		} else {
			if (!in_array($row['id'], $ally_ids)) {
				//error("Rule not found to be valid: No ally id.<br/>Ally ID's: ".print_r($ally_ids)."<br/>ID:".$row['id'], __FILE__, __LINE__);
				remove_rule($row['id'], $row['group_id']);
			} //End if.
		} //End if - else.
		
	} //End while loop.
	
	return true;
	
} //End if.

/**
 * Applies any user defined rules to the user list.
 */
function apply_rules(&$log) {
	global $db, $pun_config, $_HOOKS;
	
	//Finish any out standing transactions.
	$db->end_transaction();
	
	//Start a new one.
	$db->start_transaction();
	
	//Before we do anything, we make sure the rules are safe.
	if (!check_rules()) {
		if (defined('PUN_DEBUG')) {
			error("Unable to cehck rules.", __FILE__, __LINE__, $db->error());
		} //End if.
		return false; //We do NOT want to carry on if the rules can't be verified.
	} //End if.
	
	$sql = '';
	$characters = array();
	$purge = array();
	
	//We need to get the characters we'll be working with first.
	
	$sql = "
	SELECT
		sc.*,
		c.*,
		corp.*,
		u.*,
		g.*,
		ally.*
	FROM
		".$db->prefix."api_selected_char AS sc
	INNER JOIN
		".$db->prefix."api_characters AS c
	ON
		sc.character_id=c.character_id
	INNER JOIN
		".$db->prefix."api_auth AS auth
	ON
		auth.api_character_id=c.character_id
	INNER JOIN
		".$db->prefix."users AS u
	ON
		u.id=sc.user_id
	INNER JOIN
		".$db->prefix."groups AS g
	ON
		u.group_id=g.g_id
	LEFT JOIN
		".$db->prefix."api_allowed_corps AS corp
	ON
		corp.corporationID=c.corp_id
	LEFT JOIN
		".$db->prefix."api_alliance_list AS ally
	ON
		corp.allianceID=ally.allianceID
	";
	
	if (!$result = $db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to get character list.", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.

	
	//New group rule assignment block.
	
	//Let's tell the hooks we're about to process characters - we won't be passing them an arrayfilled with characters.
	//This is just for inital loads or tasks before the rules.
	foreach ($_HOOKS['rules'] as $hook) {
		$hook->first_load($characters);
	} //End foreach().
	
	$log = '';
	$no_auth = true;
	$old_group = 0;
	//Primary loop, does all the work.
	while ($row = $db->fetch_assoc($result)) {
		$old_group = $row['g_id'];
		$auth = true;
		
		//Get the group rules that apply to this character.
		$sql = "SELECT * FROM ".$db->prefix."api_groups WHERE id=".$row['corp_id']." OR id=".$row['ally_id']." OR id=0 ORDER BY priority DESC;";
		if (!$rules_result = $db->query($sql)) {
			if (defined('PUN_DEBUG')) {
				error("Unable to get rule listing.<br/>".$sql, __FILE__, __LINE__, $db->error());
			} //End if.
			$log .= "[".$row['username']."]: Unable to fetch rules.\n";
			continue;
		} //End if.
		
		//Do they have any rules that apply to them?
		if ($db->num_rows($rules_result) == 0) {
			$log .= "[".$row['username']."]: No rules apply to this user.\n";
			if ($row['g_locked'] == '1' || $row['group_id'] == PUN_ADMIN || $row['group_id'] == PUN_MOD) {
				$log .= "[".$row['username']."]: Is a restricted user, allowing plugin usage.\n";
				//Let it fall through and do the update to old groups.
				//There is also nothing to say that they shouldn't have plugin access.
				$auth = true; //Should be true, but still...
			} else {
				$log .= "[".$row['username']."]: Has no rules associated with them. Plugin usage has been disallowed.\n";
				$auth = false;
			} //End if - else.
		} //End if.
		
		//Now we get the groups that don't apply to them any more!
		//Select all their groups that are not locked and groups that are not specified as rules that apply to them.
		//You could put this as the primary delete SQL as well, but from memory that can be flakey when you go two deep.
		//I seem to recall some cache issues on MySQL as well. Either way; this works.
		$sql = "SELECT g.g_id FROM ".$db->prefix."groups_users AS ug INNER JOIN ".$db->prefix."groups AS g ON g.g_id=ug.group_id WHERE g.g_locked=0 AND ug.group_id NOT IN
			(SELECT group_id FROM ".$db->prefix."api_groups WHERE id=".$row['corp_id']." OR id=".$row['ally_id']." OR id=0)";
		if (!$groups_result = $db->query($sql)) {
			if (defined('PUN_DEBUG')) {
				error("Unable to list groups for removal.<br/>".$sql, __FILE__, __LINE__, $db->error());
			} //End if.
			$log .= "[".$row['username']."]: Unable to fetch groups and rules for removal.\n";
			continue;
			//return false;
		} //End if.
		
		//Remove old groups...
		while ($group = $db->fetch_assoc($groups_result)) {
			//There isn't a huge amount we need to do past this.
			$log .= "[".$row['username']."]: Removing from group [".$group['g_id']."]\n";
			$db->query("DELETE FROM ".$db->prefix."groups_users WHERE group_id=".$group['g_id']." AND user_id=".$row['id'].";");
		} //End while loop.
		
		//Collect post purges...
		$post_purge = array();
		
		//Now we actually assign them groups
		$primary_restricted = ($row['g_locked'] == '1' || $row['group_id'] == PUN_ADMIN || $row['group_id'] == PUN_MOD);
		
		$roles = convert_roles($row['roles']);
		
		while ($rule = $db->fetch_assoc($rules_result)) {
			
			if (!$roles[$rule['role']]) {
				$post_purge[] = $rule['group_id'];
				continue; //Denied based on roles.
			} //End if.
			
			if (!$primary_restricted) {
				//Update their primary group.
				$sql = "UPDATE ".$db->prefix."users SET group_id=".$rule['group_id']." WHERE id=".$row['id'];
				if (!$groups_result = $db->query($sql)) {
					if (defined('PUN_DEBUG')) {
						error("Unable to update primary group.<br/>".$sql, __FILE__, __LINE__, $db->error());
					} //End if.
					$log .= "[".$row['username']."]: Unable to update main group to [".$rule['group_id']."].\n";
					continue;
					//return false;
				} //End if.
				$log .= "[".$row['username']."]: Updated main group to [".$rule['group_id']."].\n";
				
				if ($old_group != $row['g_id']) {
					$fields = array(
						'user_id' => $row['id'],
						'group_id' => $old_group
						);
					if (!$db->insert_or_update($fields, array('user_id', 'group_id'), $db->prefix.'groups_users')) {
						if (defined('PUN_DEBUG')) {
							error('Unable to add group to table.', __FILE__, __LINE__, $db->error());
						} //End if.
						$log .= "[".$row['username']."]: Unable to add user to group [".$old_group."].\n";
						continue;
					} //End if.
					$log .= "[".$row['username']."]: Added to group [".$old_group."].\n";
				} //End if.
				
				$old_group = $rule['group_id'];
				
			} else {
				$fields = array(
					'user_id' => $row['id'],
					'group_id' => $rule['group_id']
					);
				if (!$db->insert_or_update($fields, array('user_id', 'group_id'), $db->prefix.'groups_users')) {
					if (defined('PUN_DEBUG')) {
						error('Unable to add group to table.', __FILE__, __LINE__, $db->error());
					} //End if.
					$log .= "[".$row['username']."]: Unable to add user to group [".$rule['group_id']."].\n";
					continue;
				} //End if.
				$log .= "[".$row['username']."]: Added to group [".$rule['group_id']."].\n";
			} //End if - else.
		} //End while loop().
		
		if (count($post_purges) > 0) {
		//Remove old groups...
			foreach ($post_purge as $p) {
				//There isn't a huge amount we need to do past this.
				$log .= "[".$row['username']."]: Removing from group [".$p."]\n";
				$db->query("DELETE FROM ".$db->prefix."groups_users WHERE group_id=".$p." AND user_id=".$row['id'].";");
			} //End foreach loop.
		} //End if.
		
		//Finally, call the hooks, assuming they have auth.
		if ($auth) {
			foreach ($_HOOKS['rules'] as $hook) {
				$hook->authed_user($row);
			} //End foreach().
		} //End if.
		
		$log .= "\n";
		
	} //End while loop.
	
	//Run any post rule tasks that may be required.
	foreach ($_HOOKS['rules'] as $hook) {
		$hook->last_load($characters);
	} //End foreach().
	
	return true;
	
} //End apply_rules().

/**
 * Removes a rule. Simplicity function basically.
 */
function remove_rule($id, $group_id, $type = 0, $role = 0) {
	global $db;
	
	$sql = "DELETE FROM ".$db->prefix."api_groups WHERE id=".$id." AND group_id=".$group_id." AND type=".$type." AND role='".$role."';";
	if (!$db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to remove rule.<br/>".$sql, __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	return true;
} //End remove_rule.

/**
 * Checks if the passed corpID is allowed.
 */
function is_allowed_corp($corpID) {
	
	global $db;
	
	//Get the allowed corps.
	$sql = "SELECT corporationid FROM ".$db->prefix."api_allowed_corps WHERE allowed=1 AND corporationid=".$corpID.";";
	if (!$result = $db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Enable to get corp list.", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	if ($db->num_rows($result) == 0) {
		return false;
	} //End if.
	
	return true;
} //End is_allowed_corp().

/**
 * Fetches character information from the API about the character.
 * Takes an CAK object.
 * Used for internal stuff, api.php is purefly for javascript.
 * Returns a SimpleXML Object. Don't try and use it like an array...
 */

function fetch_character_api(&$cak) {
	global $_LAST_ERROR;
	$_LAST_ERROR = 0;
	if ($cak->validate(true) != CAK_OK) {
		$_LAST_ERROR = API_BAD_AUTH;
		return false;
	} //End if.
	
	$char_sheet = new Character();
	
	if (!$char_sheet->load_character($cak)) {
		if (defined('PUN_DEBUG')) {
			error("[".$_LAST_ERROR."] Unable to load character.", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	return $char_sheet;
		
} //End fetch_character_api().

/**
 * Fetches corpID from the selected characterID, then passes it to add_corp($corpID) - convience method.
 */
function add_corp_from_character($id, $allowed = true) {
	global $db;
	
	$sql = "SELECT corp_id FROM ".$db->prefix."api_characters WHERE character_id=".$id.";";
	if (!$result = $db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to fetch corpID from character."); //Used in install, so kinda important.
		} //End if.
		return false;
	} //End if.
	
	if ($db->num_rows($result) != 1) {
		if (defined('PUN_DEBUG')) {
			error("Unable to find the character you specified.");
			return false;
		} //End if.
	} //End if.
	
	$result = $db->fetch_assoc($result);
	
	return add_corp($result['corp_id'], $allowed);
	
} //End add_corp_from_character().

/**
 * Dissallows a corp. All the corp details are kept in the database however, for display reasons.
 * Returns true on succes or false on error.
 * Note: Admins in the corp counts as an error. We do so to be on the safe side.
 */
function purge_corp($id, $remove_group = true) {
	global $db;
	//Fetch all users from this corp that are admins.
	$sql = "	SELECT
		sc.*,
		c.*,
		corp.*,
		u.*
	FROM
		".$db->prefix."api_selected_char AS sc,
		".$db->prefix."api_characters AS c,
		".$db->prefix."api_allowed_corps AS corp,
		".$db->prefix."users AS u
	WHERE
		sc.character_id=c.character_id
	AND
		corp.corporationid=c.corp_id
	AND
		u.id=sc.user_id
	AND
		u.group_id=1
	AND
		c.corp_id=".$id;
	if (!$result = $db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to delete corp.<br/>".$sql, __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	//If there are admins that are present, bail. We do NOT remove admin corps.
	if ($db->num_rows($result) > 0) {
		return false;
	} //End if.
	
	//Used to track down some odd behaviours in testing.
	//Lets us reproduce without effecting. :)
	//error("We are trying to disallow a corp for some reason.", __FILE__, __LINE__, $db->error());
	
	$sql = "UPDATE ".$db->prefix."api_allowed_corps SET allowed=0 WHERE corporationid=".$id.";";
	if (!$db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to delete corp.<br/>".$sql, __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	//Now we remove any existing rules applying to this corp.
	if ($remove_group) {
		$sql = "DELETE FROM ".$db->prefix."api_groups WHERE id=".$id." AND type=0;";
		if (!$db->query($sql)) {
			if (defined('PUN_DEBUG')) {
				error("Unable to delete corp related groups.<br/>".$sql, __FILE__, __LINE__, $db->error());
			} //End if.
			return false;
		} //End if.
	} //End if.
	
	return true;
} //End purge_corp().

/**
 * Takes a corporationID value and fetches the corp sheet from the API and puts it into the allowed list, returns an array with corpName, corpId, allianceID and allianceName on success, false otherwise.
 * Like most of these functions, it will update an existing corp should it already be in the list.
 * Please note that setting $allowed to false does NOT disallow a corp, it instead simply uses the existing setting.
 * Use purge corp if you wish to disallow a corp.
 */
function add_corp($corpID, $allowed = true) {
	global $db,$_LAST_ERROR;
	
	$corp_sheet = new Corporation();
	
	if (!$corp_sheet->load_corp($corpID)) {
		if (defined('PUN_DEBUG')) {
			error("[".$_LAST_ERROR."] Unable to fetch corp data.", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	$fields = array(
			'corporationID' => $corp_sheet->corporationID,
			'corporationName' => $corp_sheet->corporationName,
			'ticker' => $corp_sheet->ticker,
			'ceoID' => $corp_sheet->ceoID,
			'ceoName' => $corp_sheet->ceoName,
			'description' => $corp_sheet->description,
			'url' => $corp_sheet->url,
			'allianceID' => $corp_sheet->allianceID,
			'taxRate' => $corp_sheet->taxRate
		);
	
	if ($allowed) {
		$fields['allowed'] = 1;
	} //End if.
		
	if (!$db->insert_or_update($fields, 'corporationID', $db->prefix.'api_allowed_corps')) {
		return false;
	} //End if.
	
	return array(
		'corporationid' => $corp_sheet->corporationID,
		'corporationname' => $corp_sheet->corporationName,
		'allianceid' => $corp_sheet->allianceID,
		'alliancename' => ((!isset($corp_sheet->allianceName)) ? '' : $corp_sheet->allianceName)
	);
	
} //End add_corp().

/**
 * Adds a set of API keys to the database, updating where needed.
 */
function add_api_keys($user_id, $cak) {
	global $db;
	
	$fields = array(
			'user_id' => $user_id,
			'api_character_id' => $cak->char_id,
			'api_user_id' => $cak->id,
			'api_key' => $db->escape($cak->vcode),
			'cak_type' => $cak->type
		);
	
	if (!$db->insert_or_update($fields, 'api_character_id', $db->prefix.'api_auth')) {
		if (defined('PUN_DEBUG')) {
			error("Unable to update API keys.", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if/
	
	return true;
	
} //End add_api_keys().

/**
 * Removes all API keys associated with a user or a single character, depending what you pass it.
 */
function remove_api_keys($user_id = 0, $character_id = 0) {
	global $db;
	
	if ($user_id > 0) {
		$sql = '
			DELETE FROM
				'.$db->prefix."api_auth
			WHERE
				user_id=".$user_id.";";
	} else if ($character_id > 0) {
		$sql = '
			DELETE FROM
				'.$db->prefix."api_auth
			WHERE
				api_character_id=".$character_id.";";
	} else {
		return false;
	} //End if - else.
	
	if (!$db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to remove API keys.", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if/
	
	return true;
	
} //End remove_api_keys().

/**
 * This function replaces the individual access of update_character_sheet and instead lets us handle just the api keys.
 * Internally, this function still calls update_character_sheet.
 * Requires the user_id and a full auth array.
 *
 * This function defaults to updating the characters it fetches.
 * If you do not wish to do this, pass the additional $update value.
 *
 * Returns the character object used to get the list.
 */
function update_characters($user_id, &$cak, $update = true) {
	global $db;
	global $_LAST_ERROR;
	$_LAST_ERROR = 0;
	
	if ($cak->validate() != CAK_OK) {
		$_LAST_ERROR = API_BAD_AUTH;
		return false;
	} //End if.
	
	//First stop, we need to make sure the auth is correct.
	$characters = new Character(); //Despite it's singular name, the character class handles fetching the list.
	
	if (!$characters->get_list($cak)) {
		if (defined('PUN_DEBUG')) {
				error("[".$_LAST_ERROR."] Could not load character list.", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	if (count($characters->characterList) == 0) {
		if (defined('PUN_DEBUG')) {
				error("[".$_LAST_ERROR."] No characters found in list.", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	if ($update) {
		//We need to run some extra checks to see if any characters have been removed off the account.
		$sql = "
			SELECT
				a.api_character_id
			FROM
				".$db->prefix."api_auth AS a
			WHERE
				a.api_user_id=".$cak->id;
		if (!$result = $db->query($sql)) {
			if (defined('PUN_DEBUG')) {
					error("Unable to fetch existing characters.", __FILE__, __LINE__, $db->error());
			} //End if.
			return false;
		} //End if.
		
		$current_characters = array();
		
		while($row = $db->fetch_assoc($result)) {
			$current_characters[$row['api_character_id']] = 1;
		} //End while loop.
		
		$log = array();
		//Getting the list was successful and we want to update, huzzah!
		foreach($characters->characterList as $char) {
			$cak->char_id = $char['characterID'];
			
			//We unset this value in the current_characters array since it's confirmed to exist.
			unset($current_characters[$char['characterID']]);
			
			//Now lets try and update it...
			if (!update_character_sheet($user_id, $cak)) {
				//Since we are loading multiple characters, lets try and continue.
				//Debugging will stop the loading in update_character_sheet.
				$log[] = "[".$_LAST_ERROR."] Could not load character.";
			} else {
				//Lets add the keys to the db.
				add_api_keys($user_id, $cak);
			} //End if - else.
			
		} //End foreach().
		
		if (!empty($current_characters)) {
			//We have characters in the DB that are not associated with this account anymore.
			//Let's make them inactive and remove the api keys.
			//We need to make the characters inactive so that their data will still be able to be referenced by the database.
			foreach ($current_characters as $char => $value) {
				$sql = "UPDATE ".$db->prefix."api_characters SET active=0 WHERE character_id=".$char;
				$db->query($sql);
				
				remove_api_keys(0, $char);
			} //End foreach().
		} //End if.
		
		//Return the log instead of the character array.
		//You can test this via is_array.
		if (!empty($log)) {
			return $log;
		} //End if.
		
	} //End if.
	
	return $characters;
	
} //End update_characters().

/**
 * Requires that you pass it $user_id (the forum user ID) and an $api assoc array with a full CAK object.
 * If you want to use an already existing Character object, pass it after the auth array (the auth array need not be full)
 * You may also pass it a 4th value, $error, which will be given the error number of any errors, should it encounter one.
 *
 * Returns the characterID fetched. (if any)
 *
 * This function handles both inserts and updating.
 */
function update_character_sheet($user_id, $cak = null, $sheet = false) {
	global $db;
	global $_LAST_ERROR;
	$_LAST_ERROR = 0;
	
	//If any of them are not set and if sheet is false...
	if ($cak == null && !$sheet) {
		$_LAST_ERROR = API_BAD_AUTH;
		return false;
	} //End if.
	
	$char_sheet;
	
	if (!$sheet) {
		
		$char_sheet = new Character();
		
		if (!$char_sheet->load_character($cak)) {
			if (defined('PUN_DEBUG')) {
				error("[".$_LAST_ERROR."] Could not load character.", __FILE__, __LINE__, $db->error());
			} //End if.
			return false;
		} //End if.
			
	} else {
		$char_sheet = $sheet;
	} //End if - else.
	
	$fields = array(
		'user_id' => $user_id,
		'character_id' => $char_sheet->characterID,
		'character_name'=> $char_sheet->name,
		'corp_id'=> $char_sheet->corporationID,
		'corp_name'=> $char_sheet->corporationName,
		'ally_id'=> $char_sheet->allianceID,
		'ally_name'=> $char_sheet->allianceName,
		'dob'=> $char_sheet->DoB,
		'race'=> $char_sheet->race,
		'blood_line'=> $char_sheet->bloodLine,
		'ancestry'=> $char_sheet->ancestry,
		'gender'=> $char_sheet->gender,
		'clone_name'=> $char_sheet->cloneName,
		'clone_sp'=> $char_sheet->cloneSkillPoints,
		'balance'=> $char_sheet->balance,
		'last_update'=> time(),
		'roles' => $char_sheet->corporationRoles
	);
	
	if (!$db->insert_or_update($fields, 'character_id', $db->prefix.'api_characters')) {
		if (defined('PUN_DEBUG')) {
		error("Unable to run update query for character data.<br/>", __FILE__, __LINE__, $db->error());
	} //End if.
		return false;
	} //End if.
	
	return $char_sheet->characterID;
	
} //End update_character_sheet().

/**
 * Selects the character that the user will be active on.
 * Doing this means that person uses that characters permissions.
 * At some stage we'll look at decoupling this a little more and giving a bit more freedom.
 * But for now, screw your freedom. Only Spai's want freedom.
 */
function select_character($user_id, $character_id) {
	global $db;
	
	//Let's test to see if the character exists.
	$sql = "SELECT character_id FROM ".$db->prefix."api_characters WHERE character_id=".$character_id.";";
	if (!$result = $db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to see if character exists.<br/>", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	if ($db->num_rows($result) != 1) {
		return false;
	} //End if.
	
	//$sql = "INSERT INTO ".$db->prefix."api_selected_char(user_id, character_id) VALUES(".$user_id.",".$character_id.") ON DUPLICATE KEY UPDATE character_id=".$character_id.";";
	
	
	if (!$db->insert_or_update(array('user_id' => $user_id, 'character_id' => $character_id), 'user_id', $db->prefix.'api_selected_char')) {
		if (defined('PUN_DEBUG')) {
			error("Unable to select character.<br/>", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
		
	return true;
} //End select_character().

//These topic/forum/post bits will be intergrated into the main pages they relate to at some stage.

/**
 * Fetchs the character information for last poster in a forum. Takes a forum_id, returns char array or false.
 */
function fetch_last_forum_poster_character($id) {
	global $db;
	
	$sql = "
		SELECT
			lp.last_post_id,
			lp.id,
			p.poster_id,
			p.id,
			sc.*,
			c.*
		FROM
			".$db->prefix."forums AS lp
		LEFT JOIN
			".$db->prefix."posts AS p
		ON
			lp.last_post_id=p.id
		LEFT JOIN
			".$db->prefix."users AS u
		ON
			u.id=p.poster_id
		LEFT JOIN
			".$db->prefix."api_selected_char AS sc
		ON
			p.poster_id=sc.user_id
		LEFT JOIN
			".$db->prefix."api_characters AS c
		ON
			sc.character_id=c.character_id
		WHERE
			lp.id=".$id."
	";
	
	if (!$result = $db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to query character data.", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	if ($db->num_rows($result) == 0) {
		if (defined('PUN_DEBUG')) {
			error("Unable to find character/user data.", __FILE__, __LINE__, $db->error());
		}//End if.
		return false;
	} //End if.
	
	$char = $db->fetch_assoc($result);
	
	if ($char['group_id'] == PUN_GUEST) {
		$char['character_name'] = null; //We want to make sure their user name is used.
	} //End if.
	
	return $char;
	
} //End fetch_last_forum_poster_character().

/**
 * Fetchs the characrer information for last poster in a forum/topic. Takes an id (post or topic), returns char array or false.
 */
function fetch_last_poster_character($id, $is_topic = false) {
	global $db;
	
	if (!$is_topic) {
		$sql = "
			SELECT
				p.poster_id,
				p.id,
				sc.*,
				c.*
			FROM
				".$db->prefix."posts AS p
			LEFT JOIN
				".$db->prefix."api_selected_char AS sc
			ON
				p.poster_id=sc.user_id
			LEFT JOIN
				".$db->prefix."api_characters AS c
			ON
				sc.character_id=c.character_id
			WHERE
				p.id=".$id."
				
		";
	} else {
		$sql = "
		SELECT
			p.poster_id,
			p.id,
			sc.*,
			c.*,
			t.*
		FROM
			".$db->prefix."posts AS p
		INNER JOIN
			".$db->prefix."topics AS t
		ON
			p.id=t.last_post_id
		LEFT JOIN
			".$db->prefix."api_selected_char AS sc
		ON
			p.poster_id=sc.user_id
		LEFT JOIN
			".$db->prefix."api_characters AS c
		ON
			sc.character_id=c.character_id
		WHERE
			t.id=".$id."
		";
	} //End if - else.
	
	if (!$result = $db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to query character data.", __FILE__, __LINE__, $db->error());
		} //End if.\
		return false;
	} //End if.
	
	if ($db->num_rows($result) == 0) {
		if (defined('PUN_DEBUG')) {
			error("Unable to find character data.".$sql, __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	return $db->fetch_assoc($result);
	
} //End fetch_last_poster_character().

/**
 * Fetchs the characrer information for the topic poster in a forum. Takes a topic_id, returns char array or false.
 */
function fetch_topic_poster_character($id) {
	global $db;
	
	$sql = "
		SELECT
			p.poster_id,
			p.id,
			sc.*,
			c.*,
			t.*
		FROM
			".$db->prefix."posts AS p
		INNER JOIN
			".$db->prefix."topics AS t
		ON
			p.id=t.first_post_id
		LEFT JOIN
			".$db->prefix."api_selected_char AS sc
		ON
			p.poster_id=sc.user_id
		LEFT JOIN
			".$db->prefix."api_characters AS c
		ON
			sc.character_id=c.character_id
		WHERE
			t.id=".$id."
	";
	
	if (!$result = $db->query($sql)) {
		/*if (defined('PUN_DEBUG')) {
			error("Unable to query character data.", __FILE__, __LINE__, $db->error());
		} //End if.*/
		return false;
	} //End if.
	
	if ($db->num_rows($result) == 0) {
		/*if (defined('PUN_DEBUG')) {
			error("Unable to find character data.".$sql, __FILE__, __LINE__, $db->error());
		} //End if.*/
		return false;
	} //End if.
	
	return $db->fetch_assoc($result);
	
} //End fetch_topic_poster_character().

/**
 * Fetchs the selected characrer information for a user. Takes a user_id, returns array of details or false.
 * This is only acceptable to use when you are NOT requiring the character to have a corp associated.
 */
function fetch_selected_character($id, $limited = false) {
	global $db;
		$sql = "
			SELECT
				sc.*,
				c.*,
				corp.*
			FROM
				".$db->prefix."api_selected_char AS sc
			INNER JOIN
				".$db->prefix."api_characters AS c
			ON
				sc.character_id=c.character_id
			LEFT JOIN
				".$db->prefix."api_allowed_corps AS corp
			ON
				corp.corporationID=c.corp_id
			LEFT JOIN
				".$db->prefix."api_allowed_alliance AS ally
			ON
				corp.allianceID=ally.allianceID
			WHERE
				sc.user_id=".$id."
			";
	
	if (!$result = $db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to query character data.<br/>".$sql, __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	if ($db->num_rows($result) == 0) {
		//Not an error, just means the persons corp has been deleted.
		return false;
	} //End if.
	
	return $db->fetch_assoc($result);
	
} //End fetch_selected_character().

/**
 * Take an Eve-o time string (ex: '2011-02-10 00:16:04') and converts it to a time stamp.
 */
function convert_to_stamp($timeString, $use_gmt = false) {
	$time = explode(' ',$timeString);
	
	$date = explode('-', $time[0]);
	$time = explode(':', $time[1]);
	
	for($i =0; $i < 3; $i++) {
		$date[$i] = intval($date[$i]);
		$time[$i] = intval($time[$i]);
	} //End foreach().
	
	if ($use_gmt) {
		gmmktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);
	} //End if.
	return mktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);
} //End convert_to_stamp().

/**
 * Takes one (or two) dates, works out the difference and prints them like: 1y 3d 11h 6m
 */
function format_time_diff($start, $end = 0) {
	$format = '';
	if ($end == 0) {
		$end = time();
	} //End if.
	
	if ($start > $end) {
		return '0m';
	} //End if.
	
	$diff = $end - $start;
	
	//Start computing!
	$years = floor($diff / 31556926); //Exactly 1 year, according to Google. :)
	$diff -= $years * 31556926;
	$format .= $years == 0 ? '' : sprintf('%dy ', $years);
	
	$days = floor($diff / 86400); //One day.
	$diff -= $days * 86400;
	$format .= $days == 0 ? '' : sprintf('%dd ', $days);
	
	$hours = floor($diff / 3600); //One hour
	$diff -= $hours * 3600;
	$format .= $hours == 0 ? '' : sprintf('%dh ', $hours);
	
	$minutes = floor($diff / 60); //One minute
	$format .= sprintf('%dm ', $minutes);
	$diff -= $minutes * 60;
	
	$format .= sprintf('%ds', $diff);
	
	return $format;
	
} //End format_time_diff().

/**
 * This function takes a string created from BC maths and subs out the roles to create a bool array, indexed with the role values as strings.
 * Director would be: '1' => true, for example.
 */
function convert_roles($roles) {
	global $api_roles;
	
	//Lets first set the scale to 0.
	bscale(0);
	$auth = array();
	
	//Now then, we loop through the api_roles array, backwards!
	$temp_api_roles = array_reverse($api_roles);
	foreach($temp_api_roles as $key => $value) {
		$auth[$value] = false;
		if ($value == 0) {
			$auth[$value] = true;
			continue; //The 'Any' value is in there, which will get set to true always.
		} //End if.
		if (bdiv($roles, $value) == 1) {
			$roles = bsub($roles, $value);
			$auth[$value] = true;
		} //End if.
	} //End 'i' for loop().
	
	return $auth;
} //End convert_roles().

/**
 * Gets a file from a remote place and puts it where you specify, in the cache folder.
 * For now this will be restricted to the cache folder. You can always manually move it from there.
 */
function fetch_file($url, $cache_name) {
	
	global $pun_request;
	
	if (!$pun_request->fetch_file($url, FORUM_CACHE_DIR.$cache_name)) {
		return false;
	} //End if.

	return true;
} //End fetch_file().

/**
 * Pulls down the characters avatar from the CCP image server.
 * You can now disable this and whore on CCP's bandwidth if you are tight for disk space.
 */
function cache_char_pic($id, $force = false) {
	
	global $pun_request, $pun_config;
	
	//This has been over hauled as file_get/put_contents is not PHP4.
	if (!is_writable('img/chars') || $pun_config['o_eve_use_image_server'] == '1') {
		return false;
	} //End if.
	
	$img = 'img/chars/'.$id.'_64.jpg';
	if (!file_exists($img) || $force) {
		
		$pun_request->fetch_file('http://image.eveonline.com/Character/'.$id.'_64.jpg', $img);
		
	} //End if.
	
	$img = 'img/chars/'.$id.'_128.jpg';
	if (!file_exists($img) || $force) {
		
		$pun_request->fetch_file('http://image.eveonline.com/Character/'.$id.'_128.jpg', $img);
		
	} //End if.
	return true;
} //End cache_char_pic().

/**
 * Checks if the passed value is numeric and has a length > 0.
 */
function check_numeric() {
	$regex = "/^[0-9]+\.?[0-9]*$/";
	
	$args = func_get_args();
	foreach ($args as $i) {
		if (is_array($i)) {
			foreach($i as $j) {
				if (preg_match($regex,$j) == 0) {
					return false;
				} //End if.
			} //End 'j' foreach loop.
		} else {
			if (preg_match($regex,$i) == 0) {
				return false;
			} //End if.
		} //End if - else.
	} //End 'i' foreach loop.
	return true;
} //End check_numeric().

/**
 * Checks if the passed value is alpha-numeric and has a length > 0.
 */
function check_alpha_numeric() {
	$args = func_get_args();
	
	$regex = "/^[a-zA-Z0-9 ]+$/";
	
	foreach ($args as $i) {
		if (is_array($i)) {
			foreach($i as $j) {
				if (preg_match($regex,$j) == 0) {
					return false;
				} //End if.
			} //End 'j' foreach loop.
		} else {
			if (preg_match($regex,$i) == 0) {
				return false;
			} //End if.
		} //End if - else.
	} //End 'i' foreach loop.
	return true;
} //End check_alpha_numeric().

/**
 * Strips any 'special' characters from a string.
 */
function strip_special($string) {
	$regex = "/\`|\~|\!|\@|\#|\$|\%|\^|\&|\*|\(|\)|\[|\]|\{|\}|\\|\'|\"|\;|\:|\?|\>|\,|\<*/";
	
	return preg_replace($regex, "", $string);
	
} //End strip_special().

?>