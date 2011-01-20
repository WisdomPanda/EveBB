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
		$ids[] = $row['corporationID'];
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
		$new_ids[] = $row['corporationID']; //For the next cross check...
		
		if (!in_array($row['corporationID'], $ids)) {
			//This is a new corp, add it to DB.
			$corp = add_corp($row['corporationID']);
			$log[] = "[".$corp['corporationID']."] ".$corp['corporationName']." from [".$corp['allianceID']."] ".$corp['allianceName']." has been freshly added!";
		} //End if.
	} //End while loop.
	
	foreach ($ids as $id) {
		if (in_array($id, $new_ids)) {
			$log[] = "[".$row['corporationID']."] ".$row['corporationName']." is still up to date..";
			continue; //Nothing to see here.
		} //End if.
		
		//They are not in the alliance anymore, purge them.
		purge_corp($row['corporationID']);
		$log[] = "[".$row['corporationID']."] ".$row['corporationName']." is no longer in [".$row['allianceID']."] ".$row['allianceName'].".";
	} //End foreach().
	
	return $log;
	
} //End task_update_alliance().

/**
 * Re-Inserts all the alliances and their member corps into the databases, updating where needed.
 * Best to leave this to task_update_alliance as the ally list isn't exactly small.
 */
function refresh_alliance_list() {
	
	global $db;
	
	$url = 'http://api.eve-online.com/eve/AllianceList.xml.aspx';
	
	if (!$xml = post_request($url)) {
		return false;
	} //End if.
	
	if (!$list = simplexml_load_string($xml)) {
		if (defined('PUN_DEBUG')) {
			error(print_r(libxml_get_errors(), true), __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	if (isset($list->error)) {
		if (defined('PUN_DEBUG')) {
			error($list->error, __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	//Hokay, we're good to go now.
	
	//Now then, we can't be 100% assured that current corps are in the alliance, so we purge ALL the member corps.
	$sql = "TRUNCATE TABLE ".$db->prefix."api_alliance_corps";
	if (!$db->query($sql)) {
		if (defined('PUN_DEBUG')) {
				error("Unable to delete corps.<br/>".$sql."<br/>".print_r($row, true)."<br/>", __FILE__, __LINE__, $db->error());
			} //End if.
		return false;
	} //End if.
	
	foreach ($list->result->rowset->row as $row) {
		//Need to use this in corp loop, so type cast it here so it's only type cast once.
		$id = (int)$row['allianceID'];
		$sql = "
			INSERT INTO
				".$db->prefix."api_alliance_list
				(
					allianceID,
					name,
					shortName,
					executorCorpID,
					memberCount,
					startDate
				)
			VALUES
				(
					".$id.",
					'".addslashes((string)$row['name'])."',
					'".addslashes((string)$row['shortName'])."',
					".(int)$row['executorCorpID'].",
					".(int)$row['memberCount'].",
					'".(string)$row['startDate']."'
				)
			ON DUPLICATE KEY UPDATE
				memberCount=".(int)$row['memberCount'].",
				executorCorpID=".(int)$row['executorCorpID']."
			;";
		
		if (!$db->query($sql)) {
			if (defined('PUN_DEBUG')) {
					error((string)$list->error."<br/>".$sql."<br/>".print_r($row, true)."<br/>", __FILE__, __LINE__, $db->error());
				} //End if.
			return false;
		} //End if.
		
		//Now we've inserted the alliance, we update the member corps.
		foreach ($row->rowset->row as $corp) {
			$sql = "INSERT INTO ".$db->prefix."api_alliance_corps(allianceID, corporationID, startDate) VALUES(".$id.", ".(int)$corp['corporationID'].", '".(string)$corp['startDate']."')";
			
			if (!$db->query($sql)) {
				if (defined('PUN_DEBUG')) {
					error("Unable to insert corp.<br/>".$sql."<br/>".print_r($corp, true)."<br/>", __FILE__, __LINE__, $db->error());
				} //End if.
				return false;
			} //End if.
			
		} //End foreach().
		
	} //End foreach().
	
	return true;
	
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
		
		if ($skip_corp == $row['corporationID']) {
			continue; //We don't touch the corp specified as it is used as the "failsafe" corp. This makes sure we always have 1 corp left, minimum.
		} //End if.
		
		if (!purge_corp($row['corporationID'], $remove_group)) {
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
	
	$sql = "SELECT * FROM ".$db->prefix."api_alliance_list WHERE allianceID=".$allianceID.";";
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
	
	$sql = "SELECT * FROM ".$db->prefix."api_alliance_corps WHERE allianceID=".$allianceID.";";
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
	
	$sql = "
			INSERT INTO
				".$db->prefix."api_allowed_alliance
					(
						allianceID,
						allianceName,
						ticker,
						executorCorpID,
						memberCount,
						startDate,
						allowed
					)
			VALUES
					(
						".$allianceID.",
						'".$alliance['name']."',
						'".$alliance['shortName']."',
						".$alliance['executorCorpID'].",
						".$alliance['memberCount'].",
						'".$alliance['startDate']."',
						1
					)
			ON DUPLICATE KEY UPDATE
				executorCorpID=".$alliance['executorCorpID'].",
				memberCount=".$alliance['memberCount'].",
				allowed=1
			;";
	if (!$db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to insert alliance.", __FILE__, __LINE__, $db->error());
		} //End if.
		return false;
	} //End if.
	
	$win = true;
	
	while ($corp = $db->fetch_assoc($result)) {
		if (!add_corp($corp['corporationID'])) {
			if (defined('PUN_DEBUG')) {
				error("Unable to insert corp.", __FILE__, __LINE__, $db->error());
			} //End if.
			$win = false; //Again, get as many of them in as possible. Also, We just lost.
		} //End if.
	} //End while loop.
	
	return $win;
	
} //End add_alliance().

?>