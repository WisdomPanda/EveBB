<?php

if (!defined('EVE_ENABLED')) {
	exit('Must be called locally.');
} //End if.

//Define this to enable the menu in admin for this page.
define('EVE_ALLIANCE_ENABLED', 1);

/**
 * Used by our task runner to update alliance stuff.
 * Returns a log of events, very handy for seeing what happens if you call it directly.
 */
function task_update_alliance() {
	
	global $db;
	
	$log = array();
	
	//First, let's select the alliance corps.
	//This skips corps previously marked as not allowed, so we don't over write the users choice. If they want that corp back, they will need to add it.
	$ids = array();
	$sql = "
		SELECT
			c.corporationID,
			c.allianceID
		FROM
			".$db->prefix."api_allowed_corps AS c,
			".$db->prefix."api_allowed_alliance AS a
		WHERE
			c.allianceID=a.allianceID
		AND
			a.allowed=1
		AND
			c.allowed=1
		ORDER BY
			c.allianceID;";
	if (!$result = $db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to fetch corp list.", __FILE__, __LINE__, $db->error());
		} //End if.
		$log[] = "Unable to fetch corp list.";
		return $log;
	} //End if.
	
	//What we're doing is building a current list of allowed corps.
	//When we refresh the alliance list, we'll repeat the proccess.
	//If there are any errors, we'll remove the offending corp.
	while ($row = $db->fetch_assoc($result)) {
		$ids[] = $row['corporationid'];
	} //End while loop.
	
	//$ids now contains a list of the old allowed corps.
	
	//Next we refresh the alliance list and corps.
	if (!refresh_alliance_list()) {
		$log[] = "Unable to refresh alliance list.";
		return $log;
	} //End if.
	
	//Now we do a similar DB query to above, but instead we join it to the api_alliance_corps table, giving us a current list of corps.
	$sql = "
		SELECT
			c.corporationID,
			c.allianceID,
			a.allianceName
		FROM
			".$db->prefix."api_alliance_corps AS c,
			".$db->prefix."api_allowed_alliance AS a
		WHERE
			c.allianceID=a.allianceID
		AND
			a.allowed=1
		ORDER BY
			c.allianceID;";
	if (!$result = $db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to fetch corp list.", __FILE__, __LINE__, $db->error());
		} //End if.
		$log[] = "Unable to fetch corp list.";
		return $log;
	} //End if.
	
	//This is all very messy and needs a clean up, but it's functional for now.
	
	//Now lets loop through and check them.
	while ($row = $db->fetch_assoc($result)) {
		$new_ids[] = $row['corporationid']; //For the next cross check...
		
		if (!in_array($row['corporationid'], $ids)) {
			//This is a new corp, add it to DB.
			$corp = add_corp($row['corporationid']);
			$log[] = "[".$corp['corporationid']."] ".$corp['corporationname']." from [".$corp['allianceid']."] ".$corp['alliancename']." has been freshly added!";
		} //End if.
	} //End while loop.
	
	foreach ($ids as $id) {
		if (in_array($id, $new_ids)) {
			$log[] = "[".$id."] is still up to date..";
			continue; //Nothing to see here.
		} //End if.
		
		//They are not in the alliance anymore, purge them.
		purge_corp($id);
		$log[] = "[".$id."] is no longer in [".$row['allianceid']."] ".$row['alliancename'].".";
	} //End foreach().
	
	return $log;
	
} //End task_update_alliance().

/**
 * Re-Inserts all the alliances and their member corps into the databases, updating where needed.
 * Best to leave this to task_update_alliance as the ally list isn't exactly small.
 */
function refresh_alliance_list() {
	
	$alliance = new Alliance();
	
	return $alliance->update_list();
	
	//Now we can hand it off to something like refresh_alliance_permissions to check if a corp has left.
	
} //End refresh_alliance_list().


/**
 * Removes an alliance, alliance groups, corps and corp groups.
 * $skip_corp should be set to the users current corp, regardless as to if they're in the alliance.
 */
function purge_alliance($id, $skip_corp, $remove_group = true) {
	global $db;

	$sql = "UPDATE ".$db->prefix."api_allowed_alliance SET allowed=0 WHERE allianceID=".$id.";";
	if (!$db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to delete alliance.<br/>", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	//Now we remove any existing rules applying to this alliance.
	if ($remove_group) {
		$sql = "DELETE FROM ".$db->prefix."api_groups WHERE id=".$id." AND type=1;";
		if (!$db->query($sql)) {
			if (defined('PUN_DEBUG')) {
				error("Unable to delete corp related groups.<br/>".$sql, __FILE__, __LINE__, $db->error());
			} //End if.
			return false;
		} //End if.
	} //End if.
	
	$sql = "SELECT corporationID FROM ".$db->prefix."api_allowed_corps WHERE allianceID=".$id.";";
	if (!$result = $db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to fetch corps.<br/>".$sql, __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	$passed = true;
	
	while ($row = $db->fetch_assoc($result)) {
		
		if ($skip_corp == $row['corporationid']) {
			continue; //We don't touch the corp specified as it is used as the "failsafe" corp. This makes sure we always have 1 corp left, minimum.
		} //End if.
		
		if (!purge_corp($row['corporationid'], $remove_group)) {
			$passed = false; //We want to get as many of them out as possible, so this lets the loop continue.
		} //End if.
	} //End while loop.
	
	return $passed;
	
} //End purge_alliance().

/**
 * Takes an allianceID and adds the alliance and it's corps to the allowed list.
 */
function add_alliance($allianceID) {
	global $db;
	
	$sql = "SELECT allianceid, name, shortname, executorcorpid, membercount, startdate FROM ".$db->prefix."api_alliance_list WHERE allianceID=".$allianceID.";";
	if (!$result = $db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to fetch alliance.", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	if ($db->num_rows($result) == 0) {
		if (defined('PUN_DEBUG')) {
			error("Alliance not in database.", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	$alliance = $db->fetch_assoc($result);
	
	$sql = "SELECT corporationid FROM ".$db->prefix."api_alliance_corps WHERE allianceID=".$allianceID.";";
	if (!$result = $db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to fetch corps.", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	if ($db->num_rows($result) == 0) {
		if (defined('PUN_DEBUG')) {
			error("Alliance has no corps in database.<br/>".$sql, __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	$fields = array(
			'allianceID' => $allianceID,
			'allianceName' => addslashes($alliance['name']),
			'ticker' => $alliance['shortname'],
			'startDate' => $alliance['startdate'],
			'executorCorpID' => $alliance['executorcorpid'],
			'memberCount' => $alliance['membercount'],
			'allowed' => 1
		);
	
	if (!$db->insert_or_update($fields, 'allianceID', $db->prefix.'api_allowed_alliance')) {
		if (defined('PUN_DEBUG')) {
			error("Unable to insert alliance.", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	$win = true;
	
	while ($corp = $db->fetch_assoc($result)) {
		if (!add_corp($corp['corporationid'])) {
			if (defined('PUN_DEBUG')) {
				error("Unable to insert corp.", __FILE__, __LINE__, $db->error());
			} //End if.
			$win = false; //Again, get as many of them in as possible. Also, We just lost.
		} //End if.
	} //End while loop.
	
	return $win;
	
} //End add_alliance().

?>