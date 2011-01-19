<?php

if (!defined('EVE_ENABLED')) {
	exit('Must be called locally.');
} //End if.

if (!defined(PUN_ROOT)) {
	define('PUN_ROOT', './');
} //End if/

if (file_exists(PUN_ROOT.'include/eve_alliance_functions.php')) {
	include PUN_ROOT.'include/eve_alliance_functions.php';
} else {
	include PUN_ROOT.'include/eve_corp_functions.php';
} //End if - else.

/**
 * AUTHORS NOTE
 *
 * The follow code is classed as a first draft. This means that it's only purpose is to *work*.
 * As such, there have been no major refractoring efforts or performance focusing.
 * This will be happening in the future as we look towards a release of 1.0.
 *
 * The code you see has been in testing and has passed our user load tests well.
 * We do however expect to see more issues crop up  with a larger release.
 * Should find a bug the in the features, please report it at www.eve-bb.com.
 *
 * Should you find a critical security issue, please PM it to WisdomPanda on the eve-bb.com forums.
 *
 * If you find a performance issue, panic not, I already have a list of bits for clean up and refractoring.
 *
 * Thank you for using/checking out EveBB, I hope you enjoy the experience!
 *
 * ~ WisdomPanda
 *
 * Side note: PostgreSQL and SQLite will be supported as of 1.0.0 again.
 * I was simply loathed to start fiddling the with the DB layer right off the bat.
 * (aka; all platforms should support the ability to update on a duplicate key. Will be enabling this in the db layer for pgsql/sqlite.)
 *
 */

//Define some values.
define(API_SERVER_DOWN, 1000);
define(API_BAD_REQUEST, 1001);
define(API_BAD_AUTH, 1002);
define(API_SERVER_ERROR, 1003);

/**
 * This function handles all our task running needs.
 * Just like to group similar things together in a function pretty much.
 */
function task_runner() {
	global $db, $pun_config;
	
	$run_auth = $run_ally = $run_rules = $run_char = $force_char = false;
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
		
		if ($action == 'update_all') {
			$run_ally = $run_char = true;
		} //End if.
	} else if ($pun_config['o_eve_use_cron'] == '0') {
		//We need to fetch any characters that need to be updated.
		$sql = "SELECT last_update FROM  ".$db->prefix."api_characters WHERE last_update<".(time()-($pun_config['o_eve_cache_char_sheet_interval']*60*60))." ORDER BY  last_update DESC LIMIT 0 , 1";
		if (!$result = $db->query($sql)) {
			if (defined('PUN_DEBUG')) {
				error("Unable to fetch corp list.", __FILE__, __LINE__, $db->error());
			} //End if.
		} else {
			if ($db->num_rows($result) == 1) {
				$run_ally = $run_char = true; //There is at least one character that needs to be updated.
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
		try {
			if (!task_check_auth()) {
				$log[] = 'Unable to complete auth check!<br/>';
			} else {
				$log[] = 'Auth checked!<br/>';
			} //End if - else.
			$sql = "INSERT INTO ".$db->prefix."config(conf_name, conf_value) VALUES('o_eve_last_auth_check', '".time()."') ON DUPLICATE KEY UPDATE conf_value='".time()."'";
			$db->query($sql);
		} catch (Exception $e) {} //End try - catch().
	} //End if.
	
	if ($run_rules) {
		try {
			if (!apply_rules()) {
				$log[] = 'Unable to complete rule check!<br/>';
			} else {
				$log[] = 'Rule applied!<br/>';
			} //End if - else.
			$sql = "INSERT INTO ".$db->prefix."config(conf_name, conf_value) VALUES('o_eve_last_rule_check', '".time()."') ON DUPLICATE KEY UPDATE conf_value='".time()."'";
			$db->query($sql);
		} catch (Exception $e) {} //End try - catch().
	} //End if.
	
	if ($run_char) {
		try {
			if (isset($_GET['force_char'])) {
				$force_Char = true;
			} //End if.
		 	$log = array_merge($log, task_update_characters(1, defined('EVE_CRON_ACTIVE'), $force_char));
		} catch (Exception $e) {} //End try - catch().
	} //End if.
	
		if ($run_ally) {
		try {
		 	$log = array_merge($log, task_update_alliance());
		} catch (Exception $e) {} //End try - catch().
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
 * Fetchs all API's in need of update and updates them.
 * Best to run this via cronjob, as it will be waiting on the servers end.
 * force will override the default behaviour of only getting characters that require updates.
 */
function task_update_characters($limit = 1, $force = false, $full_force = false) {
	global $db, $pun_config, $lang_eve_bb;
	
	$sql = "SELECT c.character_name,c.last_update,c.character_id,a.* FROM `".$db->prefix."api_characters` AS c,`".$db->prefix."api_auth`AS a WHERE a.api_character_id=c.character_id ";
	
	if (!$full_force) {
		$sql .= " AND c.last_update<".(time()-($pun_config['o_eve_cache_char_sheet_interval']*60*60)); //(update_time - (time-x hours)) x being set in config.
	} //End if.
	
	if (!$force && !$full_force) {
		$sql .= " LIMIT 0, ".$limit.";";
	} //End if.
	
	if (!$result = $db->query($sql)) {
		$err = $db->error();
		throw new Exception("Unable to fetch character information.<br/>".$err['error_msg']);
	} //End if.
	
	$log = array();
	$error = 0;
	
	while ($row = $db->fetch_assoc($result)) {
		if (update_character_sheet($row['user_id'], array('apiKey' => $row['api_key'],'userID' => $row['api_user_id'],'characterID' => $row['api_character_id']), false, $error)) {
			$log [] = sprintf($lang_eve_bb['char_sheet_updated'], $row['character_id'], $row['character_name']);
		} else {
			if ($error == API_BAD_AUTH) {
				$log [] = sprintf($lang_eve_bb['char_sheet_failed'], $row['character_id'], $row['character_name']);
			} else if ($error == API_BAD_FETCH || $error == API_SERVER_ERROR) {
				if (defined('PUN_DEBUG')) {
					$log [] = sprintf("Unable to fetch API data.", $row['character_id'], $row['character_name']);
				} //End if.
			} else if ($error == API_SERVER_DOWN) {
				if (defined('PUN_DEBUG')) {
					$log [] = sprintf("API Server is down.");
				} //End if.
				return $log; //Die early, as there is little point us trying to get the rest.
			} //End if.
			
		} //End if - else.
	} //End while loop.
	
	return $log;
} //End task_update_characters().

/**
 * Checks to see if a user is not in the corp/alliance specified, and if they aren't move them to an inactive status.
 *
 */
function task_check_auth() {
	global $db, $pun_config, $lang_common;
	
	//We catch any exceptions thrown, so their text is more for debugging than real input.
	
	//Get the allowed corps.
	$sql = "SELECT corporationID FROM ".$db->prefix."api_allowed_corps WHERE allowed=1;";
	if (!$result = $db->query($sql)) {
		$err = $db->error();
		throw new Exception("Unable to allowed corporation information.<br/>".$err['error_msg']);
	} //End if.
	
	if ($db->num_rows($result) == 0) {
		error($lang_common['api_no_allowed_corp_err']);
	} //End if.
	
	$corps = array();
	
	while ($row = $db->fetch_assoc($result)) {
		$corps[] = $row['corporationID'];
	} //End while loop.
	
	
	//Get the users.
	$sql = "SELECT c.corp_id,c.character_name,c.last_update,c.character_id,a.user_id FROM ".$db->prefix."api_characters AS c,".$db->prefix."api_auth AS a WHERE a.api_character_id=c.character_id";
	if (!$result = $db->query($sql)) {
		$err = $db->error();
		throw new Exception("Unable to find user auth information.<br/>".$err['error_msg']);
	} //End if.
	
	if ($db->num_rows($result) == 0) {
		if (defined('PUN_DEBUG')) {
			error($lang_common['api_no_users']);
		} //End if.
	} //End if.
	
	$users = array();
	
	while ($row = $db->fetch_assoc($result)) {
		if (!in_array($row['corp_id'], $corps)) {
			$users[] = $row;
		} //End if.
	} //End while loop.
	
	if (count($users) == 0) {
		//Hurrah, all auth'd!
		return true;
	} //End if.
	
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
	
	foreach ($users as $row) {
		$sql = "UPDATE ".$db->prefix."users SET group_id=".$group_id." WHERE id=".$row['user_id'].";";
		if (!$result = $db->query($sql)) {
			$log[] = sprintf($lang_common['eve_purge_user_failed'], $row['character_name']);
			continue;
		} //End if.
		$log[] = sprintf($lang_common['eve_purge_user_done'], $row['character_name']);
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
				$corp_ids[] = $corp_temp['corporationID'];
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
				$ally_ids[] = $ally_temp['allianceID'];
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
function apply_rules() {
	global $db, $pun_config;
	
	//Before we do anything, we make sure the rules are safe.
	if (!check_rules()) {
		if (defined('PUN_DEBUG')) {
			error("Unable to cehck rules.", __FILE__, __LINE__, $db->error());
		} //End if.
		return false; //We do NOT want to carry on if the rules can't be verified.
	} //End if.
	
	$sql = '';
	$characters = array();
	
	//We are fetching a lot of character data here, this is mainly for future planning, the information will just be there.
	
	//We need to get the characters we'll be working with first.
	
	$sql = "
	SELECT
		sc.*,
		c.*,
		corp.*,
		u.*,
		g.*
	FROM
		".$db->prefix."api_selected_char AS sc
	INNER JOIN
		".$db->prefix."api_characters AS c
	ON
		sc.character_id=c.character_id
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
		corp.corporationID=c.corp_id;
	";
	
	if (!$result = $db->query($sql)) {
		
		if (defined('PUN_DEBUG')) {
			error("Unable to get character list.", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	while($row = $db->fetch_assoc($result)) {
		$characters[] = $row;
	} //End while loop().
	
	if (empty($characters)) {
		if (defined('PUN_DEBUG')) {
			error("No characters in the list.<br/>".$sql, __FILE__, __LINE__, $db->error());
		} //End if.
		return false; //This is bad.
	} //End if.
	
	
	//This whores on the DB alot, want to reduce it for scaling reasons.
	//Will need to pre-fetch the data first.
	//Marked for cleanup.
	foreach($characters as $row) {
		
		//No point looking at them if they're admin/mods... (group 0 = new user)
		if (($row['group_id'] < 3 && $row['group_id'] != 0) || $row['g_moderator'] == 1 || $row['g_email_flood'] == 666) {
			continue;
		} //End if.
		
		//This is where any future refinements will go.
		//Watch this space.
		
		//First we see if there is a rule setup for their corp, as that takes priority.
		$sql = "SELECT * FROM ".$db->prefix."api_groups WHERE id=".$row['corp_id']." AND type=0;";
		if (!$corp_result = $db->query($sql)) {
			if (defined('PUN_DEBUG')) {
				error("Unable to get group corp listing.<br/>".$sql, __FILE__, __LINE__, $db->error());
			} //End if.
			return false;
		} //End if.
		
		if ($db->num_rows($corp_result) > 0) {
			//We've found a rule.
			$corp_rule = $db->fetch_assoc($corp_result);
			$sql = "UPDATE ".$db->prefix."users SET group_id=".$corp_rule['group_id']." WHERE id=".$row['id'].";";
			if (!$db->query($sql)) {
				if (defined('PUN_DEBUG')) {
					error("Unable to update groups.".$sql, __FILE__, __LINE__, $db->error());
				} //End if.
				return false;
			} //End if.
			
			continue; //We're done, alliance rules don't over write the corp rule.
		} //End if.
		
		//Next we check alliances.
		$sql = "SELECT * FROM ".$db->prefix."api_groups WHERE id=".$row['ally_id']." AND type=1;";
		if (!$ally_result = $db->query($sql)) {
			if (defined('PUN_DEBUG')) {
				error("Unable to get group alliance listing.", __FILE__, __LINE__, $db->error());
			} //End if.
			return false;
		} //End if.
		
		if ($db->num_rows($ally_result) > 0) {
			//We've found a rule.
			$rule = $db->fetch_assoc($ally_result);
			$sql = "UPDATE ".$db->prefix."users SET group_id=".$rule['group_id']." WHERE id=".$row['id'].";";
			if (!$db->query($sql)) {
				if (defined('PUN_DEBUG')) {
					error("Unable to update groups.".$sql, __FILE__, __LINE__, $db->error());
				} //End if.
				return false;
			} //End if.
			continue; //Alliance rules are the final step if there was one found.
		} //End if.
		
		//Lets dump them into the default group, just to be safe.

		$sql = "UPDATE ".$db->prefix."users SET group_id=".$pun_config['o_eve_restricted_group']." WHERE id=".$row['id'].";";
		if (!$db->query($sql)) {
			if (defined('PUN_DEBUG')) {
				error("Unable to update groups.".$sql, __FILE__, __LINE__, $db->error());
			} //End if.
			return false;
		} //End if.
		
	} //End foreach().
	
	return true;
	
	
} //End apply_rules().

/**
 * Removes a rule. Simplicity function basically.
 */
function remove_rule($id, $group_id, $type = 0) {
	global $db;
	
	$sql = "DELETE FROM ".$db->prefix."api_groups WHERE id=".$id." AND group_id=".$group_id." AND type=".$type.";";
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
	$sql = "SELECT corporationID FROM ".$db->prefix."api_allowed_corps WHERE allowed=1 AND corporationID=".$corpID.";";
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
 * Takes an auth array (userID, apiKey, characterID)
 * Used for internal stuff, api.php is purefly for javascript.
 * Returns a SimpleXML Object. Don't try and use it like an array...
 */

function fetch_character_api($auth) {
	
	if (!isset($auth['apiKey']) || !isset($auth['userID']) || !isset($auth['characterID'])) {
		return false;
	} //End if.
	
	$url = "http://api.eve-online.com/char/CharacterSheet.xml.aspx";
			
	try {
		$xml = post_request($url, $auth);
		
		if (!$char_sheet = simplexml_load_string($xml)) {
			if (defined('PUN_DEBUG')) {
				error(print_r(libxml_get_errors(), true), __FILE__, __LINE__, $db->error());
			} //End if.
			return false;
		} //End if.
		
		if (isset($char_sheet->error)) {
			if (defined('PUN_DEBUG')) {
				error($char_sheet->error, __FILE__, __LINE__, $db->error());
			} //End if.
			return false;
		} //End if.
		
		return $char_sheet;
		
	} catch (Exception $e) {
		return false;
	} //End try - catch().
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
		corp.corporationID=c.corp_id
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
	
	$sql = "UPDATE ".$db->prefix."api_allowed_corps SET allowed=0 WHERE corporationID=".$id.";";
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
	global $db;
	$url = "http://api.eve-online.com/corp/CorporationSheet.xml.aspx";
	$corp_sheet;
	
	try {
		$xml = post_request($url, array('corporationID' => $corpID));
		
		if (!$corp_sheet = simplexml_load_string($xml)) {
			if (defined('PUN_DEBUG')) {
				error(print_r(libxml_get_errors(), true), __FILE__, __LINE__, $db->error());
			} //End if.
			return false;
		} //End if.
		
		if (isset($corp_sheet->error)) {
			if (defined('PUN_DEBUG')) {
				error($corp_sheet->error, __FILE__, __LINE__, $db->error());
			} //End if.
			return false;
		} //End if.
		
		$sql = "INSERT INTO ".$db->prefix."api_allowed_corps
					(
						corporationID,
						corporationName,
						ticker,
						ceoID,
						ceoName,
						description,
						url,
						allianceID,
						taxRate,
						allowed
					)
				VALUES
					(
						".(int)$corp_sheet->result->corporationID.",
						'".$db->escape((string)$corp_sheet->result->corporationName)."',
						'".$db->escape((string)$corp_sheet->result->ticker)."',
						".(int)$corp_sheet->result->ceoID.",
						'".$db->escape((string)$corp_sheet->result->ceoName)."',
						'".$db->escape((string)$corp_sheet->result->description)."',
						'".$db->escape((string)$corp_sheet->result->url)."',
						".(int)$corp_sheet->result->allianceID.",
						".(float)$corp_sheet->result->taxRate.",
						".(($allowed) ? '1' : '0')."
					)
				ON DUPLICATE KEY UPDATE
					corporationID=".(int)$corp_sheet->result->corporationID.",
					corporationName='".$db->escape((string)$corp_sheet->result->corporationName)."',
					ticker='".$db->escape((string)$corp_sheet->result->ticker)."',
					ceoID=".(int)$corp_sheet->result->ceoID.",
					ceoName='".$db->escape((string)$corp_sheet->result->ceoName)."',
					description='".$db->escape((string)$corp_sheet->result->description)."',
					url='".$db->escape((string)$corp_sheet->result->url)."',
					allianceID=".(int)$corp_sheet->result->allianceID.",
					taxRate=".(float)$corp_sheet->result->taxRate."
					".(($allowed) ? ',allowed=1' : '')."
				;
		";
		
		if (!$db->query($sql)) {
			return false;
		} //End if.
		
	} catch (Exception $e) {
		if (defined('PUN_DEBUG')) {
			error($e->getMessage(), __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End try - catch().
	
	return array(
		'corporationID' => (int)$corp_sheet->result->corporationID,
		'corporationName' => (string)$corp_sheet->result->corporationName,
		'allianceID' => (int)$corp_sheet->result->allianceID,
		'allianceName' => (string)$corp_sheet->result->allianceName
	);
	
} //End add_corp().

/**
 * Adds a set of API keys to the database, updating where needed.
 */
function add_api_keys($user_id, $api_user_id, $api_character_id, $api_key) {
	global $db;
	
	$sql = '
		INSERT INTO
			'.$db->prefix."api_auth
				(
					user_id,
					api_character_id,
					api_user_id,
					api_key
				)
		VALUES
			(
				".$user_id.",
				".(int)$api_character_id.",
				".(int)$api_user_id.",
				'".$db->escape($api_key)."'
			)
		ON DUPLICATE KEY UPDATE
			user_id=".$user_id.",
			api_character_id=".(int)$api_character_id.",
			api_user_id=".(int)$api_user_id.",
			api_key='".$db->escape($api_key)."'";
	
	if (!$db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to update API keys.", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if/
	
	return true;
	
} //End add_api_keys().

/**
 * Removes all API keys associated with a user.
 */
function remove_api_keys($user_id) {
	global $db;
	
	$sql = '
		DELETE FROM
			'.$db->prefix."api_auth
		WHERE
			user_id=".$user_id.";";
	
	if (!$db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to removeAPI keys.", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if/
	
	return true;
	
} //End remove_api_keys().

/**
 * Requires that you pass it $user_id (the forum user ID) and an $api assoc array with apiKey,characterID and userID all set.
 * If you want to use an already existing SimpleXML object, pass it after the auth array (the auth array need not be full)
 * You may also pass it a 4th value, $error, which will be given the error number from the API server, should it encounter one.
 *
 * Returns the characterID fetched. (if any)
 *
 * This function handles both inserts and updating, using the "ON DUPLICATE KEY" value of MySQL.
 * Support for PostgreSQL/SQLite will be done in the DB layer as, imo, it is easier to read.
 */
function update_character_sheet($user_id, $api = array(), $sheet = false, &$error = 0) {
	global $db;
	
	$error = 0;
	
	//If any of them are not set and if sheet is false...
	if ((!isset($api['apiKey']) || !isset($api['userID']) || !isset($api['characterID'])) && !$sheet) {
		return false;
	} //End if.
	
	$url = "http://api.eve-online.com/char/CharacterSheet.xml.aspx";
	$char_sheet;
	
	try {
		if (!$sheet) {
			$xml = post_request($url, $api);
			
			if (!$char_sheet = simplexml_load_string($xml)) {
				if (defined('PUN_DEBUG')) {
					error("Unable to convert xml.".print_r(libxml_get_errors(), true), __FILE__, __LINE__, $db->error());
				} //End if.
				return false;
			} //End if.
			
			if (isset($char_sheet->error)) {
				if (defined('PUN_DEBUG')) {
					error("API error while fetching character data.".$char_sheet->error, __FILE__, __LINE__, $db->error());
				} //End if.
				
				$err = (int)$char_sheet->error['code'];
				
				if ($err >= 200 && $err < 300) {
					$error = API_BAD_AUTH;
				} else {
					$error = API_SERVER_ERROR;
				} //End if - else.
				
				return false;
			} //End if.
			
			if (isset($char_sheet->html)) {
				$error = API_SERVER_DOWN;
				return false;
			} //End if.
			
		} else {
			$char_sheet = $sheet;
		} //End if - else.
		
		//We should be good from here.
		$sql = "
				INSERT INTO ".$db->prefix."api_characters
					(
						user_id,
						character_id,
						character_name,
						corp_id,
						corp_name,
						ally_id,
						ally_name,
						dob,
						race,
						blood_line,
						ancestry,
						gender,
						clone_name,
						clone_sp,
						balance,
						last_update
					)
				VALUES
					(
						".$user_id.",
						".(int)$char_sheet->result->characterID.",
						'".$db->escape((string)$char_sheet->result->name)."',
						".(int)$char_sheet->result->corporationID.",
						'".$db->escape((string)$char_sheet->result->corporationName)."',
						".(int)$char_sheet->result->allianceID.",
						'".$db->escape((string)$char_sheet->result->allianceName)."',
						'".$db->escape((string)$char_sheet->result->DoB)."',
						'".$db->escape((string)$char_sheet->result->race)."',
						'".$db->escape((string)$char_sheet->result->bloodLine)."',
						'".$db->escape((string)$char_sheet->result->ancestry)."',
						'".$db->escape((string)$char_sheet->result->gender)."',
						'".$db->escape((string)$char_sheet->result->cloneName)."',
						".(int)$char_sheet->result->cloneSkillPoints.",
						".(float)$char_sheet->result->balance.",
						".time()."
					)
			ON DUPLICATE KEY UPDATE
				user_id=".$user_id.",
				character_id=".(int)$char_sheet->result->characterID.",
				character_name='".$db->escape((string)$char_sheet->result->name)."',
				corp_id=".(int)$char_sheet->result->corporationID.",
				corp_name='".$db->escape((string)$char_sheet->result->corporationName)."',
				ally_id=".(int)$char_sheet->result->allianceID.",
				ally_name='".$db->escape((string)$char_sheet->result->allianceName)."',
				dob='".$db->escape((string)$char_sheet->result->DoB)."',
				race='".$db->escape((string)$char_sheet->result->race)."',
				blood_line='".$db->escape((string)$char_sheet->result->bloodLine)."',
				ancestry='".$db->escape((string)$char_sheet->result->ancestry)."',
				gender='".$db->escape((string)$char_sheet->result->gender)."',
				clone_name='".$db->escape((string)$char_sheet->result->cloneName)."',
				clone_sp=".(int)$char_sheet->result->cloneSkillPoints.",
				balance=".(float)$char_sheet->result->balance.",
				last_update=".time()."
		";
		//Incase you're wondering, the values we reference from the XML object are not actually the types we want until we type cast them.
		//They are in fact SimpleXML objects. (as in, child objects) Trying to add them without type casting can lead to interesting side effects. :)
		
		if (!$db->query($sql)) {
			if (defined('PUN_DEBUG')) {
			error("Unable to run update query for character data.<br/>".$sql, __FILE__, __LINE__, $db->error());
		} //End if.
			return false;
		} //End if.
		
	} catch (Exception $e) {
		if (defined('PUN_DEBUG')) {
			error("Unable to update character data.<br/>".$e->getMessage(), __FILE__, __LINE__, $db->error());
		} //End if.
		$error = API_BAD_FETCH; //Generic error.
		return false;
	} //End try - catch().
	
	return (int)$char_sheet->result->characterID;
	
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
	
	$sql = "INSERT INTO ".$db->prefix."api_selected_char(user_id, character_id) VALUES(".$user_id.",".$character_id.") ON DUPLICATE KEY UPDATE character_id=".$character_id.";";
	if (!$db->query($sql)) {
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
			".$db->prefix."forums AS lp,
			".$db->prefix."posts AS p,
			".$db->prefix."api_selected_char AS sc,
			".$db->prefix."api_characters AS c
		WHERE
			lp.id=".$id."
		AND
			lp.last_post_id=p.id
		AND
			p.poster_id=sc.user_id
		AND
			sc.character_id=c.character_id
	";
	
	if (!$result = $db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to query character data.", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	if ($db->num_rows($result) == 0) {
		if (defined('PUN_DEBUG')) {
			error("Unable to find character data.", __FILE__, __LINE__, $db->error());
		}//End if.
		return false;
	} //End if.
	
	return $db->fetch_assoc($result);
	
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
				".$db->prefix."posts AS p,
				".$db->prefix."api_selected_char AS sc,
				".$db->prefix."api_characters AS c
			WHERE
				p.id=".$id."
			AND
				p.poster_id=sc.user_id
			AND
				sc.character_id=c.character_id
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
			".$db->prefix."posts AS p,
			".$db->prefix."api_selected_char AS sc,
			".$db->prefix."api_characters AS c,
			".$db->prefix."topics AS t
		WHERE
			t.id=".$id."
		AND
			p.id=t.last_post_id
		AND
			p.poster_id=sc.user_id
		AND
			sc.character_id=c.character_id
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
			".$db->prefix."posts AS p,
			".$db->prefix."api_selected_char AS sc,
			".$db->prefix."api_characters AS c,
			".$db->prefix."topics AS t
		WHERE
			t.id=".$id."
		AND
			p.id=t.first_post_id
		AND
			p.poster_id=sc.user_id
		AND
			sc.character_id=c.character_id
	";
	
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
		/*if (defined('PUN_DEBUG')) {
			error("Unable to find character data.<br/>".$sql, __FILE__, __LINE__, $db->error());
		} //End if.*/
		return false;
	} //End if.
	
	return $db->fetch_assoc($result);
	
} //End fetch_last_poster_character().

/**
 * Tries to sliently cache the pic of a character.
 * Will be updating this later to add the cache status to database as checking the filesystem isn't ideal.
 */
function cache_char_pic($id) {
	$img = 'img/chars/'.$id.'_64.jpg';
	if (!file_exists($img)) {
		$url = 'http://image.eveonline.com/Character/'.$id.'_64.jpg';
		if (@file_put_contents($img, file_get_contents($url))) {
			return false;
		} //End if.
	} //End if.
	
	$img = 'img/chars/'.$id.'_128.jpg';
	if (!file_exists($img)) {
		$url = 'http://image.eveonline.com/Character/'.$id.'_128.jpg';
		if (@file_put_contents($img, file_get_contents($url))) {
			return false;
		} //End if.
	} //End if.
	return true;
} //End cache_char_pic().

/**
 * Main work horse for all the API functions, POST's a request and returns the result.
 * Why POST? Well, why not?
 */
function post_request($url, $data = array(), $optional_headers = array()) {
	
	$context;
	
	if (count($data) == 0) {
		
		$context = stream_context_create();
		
		$file = fopen($url, 'r', false, $context);

	} else {
		$params = array('http' =>
				array(
					'method' => 'POST',
					'content' => http_build_query($data)
				)
		);
		
		$context = stream_context_create($params);

	}  //End if - else.
	
	$file = @fopen($url, 'r', false, $context);
	
	if (!$file) {
		throw new Exception("Unable to fetch data.<br/>URL: ".$url);
	} //End if.
	
	$response = stream_get_contents($file);
	
	fclose($file);
	
	return $response;
	
} //End post_request().

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