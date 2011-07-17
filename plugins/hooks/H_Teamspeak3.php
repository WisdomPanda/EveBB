<?php
/**
 * 05/06/2011
 * H_Teamspeak3.php
 * Panda
 */

class Teamspeak3_UsersHook extends UsersHook {
	
	//We don't care about the registration proccess, as the rules will be checked on success. (authed_user)
	//However, no such action is taken on deletion.
	function user_deleted($id) {
		return;
	} //End user_deleted().
	
} //End Teamspeak3_UsersHook class.

class Teamspeak3_RulesHook extends RulesHook {
	
	function authed_user($user) {
		global $db, $pun_config;
		
		if ($pun_config['ts3_enabled'] != '1') {
			return; //Not enabled, do nothing.
		} //End if.
		
		//Are they in the group we require?
		$groups = array();
		
		//Bit of a time saver first.
		if ($user['group_id'] != $pun_config['ts3_auth_group']) {
			$sql = "SELECT group_id FROM ".$db->prefix."groups_users WHERE user_id=".$user['id'];
			
			if (!$result = $db->query($sql)) {
				if (defined('PUN_DEBUG')) {
					error("Unable to fetch group info.", __FILE__, __LINE__, $db->error());
				} //End if.
				return; //Keep it silent.
			} //End if.
			
			if ($db->num_rows($result) == 0) {
				//Errorz!
				return;
			} //End if.
			
			while ($row = $db->fetch_assoc($result)) {
				$groups[] = $row['group_id'];
			} //End while loop.
			
			if (!in_array($pun_config['ts3_auth_group'], $groups)) {
				return; //Not in the right group.
			} //End if.
			
		} //End if.
		
		//The user checks out. Check to make sure they exist in the DB, and if they don't, we'll add them.
		$sql = "
			SELECT
				ts3.token,
				ts3.username AS nickname
			FROM
				".$db->prefix."teamspeak3 AS ts3
			WHERE
				ts3.user_id=".$user['id'];

		if (!$result = $db->query($sql)) {
			if (defined('PUN_DEBUG')) {
				error("Unable to fetch ts3 info.", __FILE__, __LINE__, $db->error());
			} //End if.
			return; //Keep it silent.
		} //End if.
		
		if ($db->num_rows($result) > 0) {
			//They already exist.
			return;
		} //End if.
		
		//Lets build them a token!
		$username = $user['ticker'].'-'.$user['character_name'];
		if (strlen($user['allianceID']) > 0) {
			$username = $user['shortName'].'-'.$username;
		} //End if.
		return create_token($user['id'], $username);
		
		return;
	} //End authed_row().
	
	function restrict_user($user) {
		global $pun_config;
		
		if ($pun_config['ts3_enabled'] != '1') {
			return; //Not enabled, do nothing.
		} //End if.
		
		//The user has been moved to the naughty corner, lets strip their TS roles as well.
		delete_token($user['id'], $user['character_name']); //username is for debugging.
		
		return false;
	} //End restrict_user().
	
} //End Teamspeak3_RulesHook class.

function telnet_open($ip, $port, $timeout, &$socket) {
	$socket = @fsockopen($ip, $port, $errno, $errstr, $timeout);
	
	if (!$socket) {
		if (defined('PUN_DEBUG')) {
			error("[$errno] $errstr", __FILE__, __LINE__);
		} //End if.
		return false;
	} //End if.
	
	//Clears MotD
	fgets($socket, 4096);
	fgets($socket, 4096);
	
	return true;
	
} //End telnet_open().

function telnet_close(&$socket) {
	fclose($socket);
	$socket = null;
} //end telnet_close().

function telnet_send(&$socket, $msg) {
	@fputs($socket, $msg."\n");
} //End telnet_send().

function telnet_read(&$socket) {
	
	$end = false;
	$response = array();
	$log = "";
	$msg = "";
	$limit = 5000;
	$count = 0;
	
	while(!$end) {
		$msg = @fgets($socket, 4096);
		$msg_full .= $msg;
		$log = $msg.'<br/>';
		$count++;
		
		if ($count > $limit) {
			return;
		} //End if.
		
		if (preg_match('/error .*$/', $msg)) {
			$end = true;
		} //End if - else.
				
	} //End while loop.
	
	$lines = explode("\n", $msg_full);
	
	foreach ($lines as $line) {
		$values = explode(' ', $line);
		foreach ($values as $v) {
			$temp = explode('=', $v);
			
			if (count($temp) != 2) {
				continue;
			} //End if.
			
			$temp[0] = str_replace(" ", '', $temp[0]);
			$response[trim($temp[0])] = $temp[1];
			
		} //End foreach().
	
	} //End foreach().
	
	return $response;
	
} //End function telnet_read().

function create_token($id, $username) {
	global $db, $pun_config;
	
	$socket;
	
	if (!telnet_open($pun_config['ts3_ip'], intval($pun_config['ts3_query_port']), intval($pun_config['ts3_timeout']), $socket)) {
		if (defined('PUN_DEBUG')) {
			message("Unable to open a connection to the Teamspeak3 server.<br/><br/>Please verify it is currently running and accepting connections.");
		} //End if.
		return false;
	} //End if.
	
	telnet_send($socket, "login ".$pun_config['ts3_user']." ".$pun_config['ts3_pass']);
	$response = telnet_read($socket);
	if ($response['id'] != 0) {
		if (defined('PUN_DEBUG')) {
			message("An error has occured while logging into the Teamspeak3 server.<br/><br/>".$response['msg']);
		} //End if.
		return false;
	} //End if.
	
	telnet_send($socket, "use sid=".$pun_config['ts3_sid']);
	$response = telnet_read($socket);
	if ($response['id'] != 0) {
		if (defined('PUN_DEBUG')) {
			message("An error has occured while selecting the <b>sid</b> of the Teamspeak3 server.<br/><br/>".$response['msg']);
		} //End if.
		return false;
	} //End if.
	
	$token = "tokenadd tokentype=0 tokenid1=".$pun_config['ts3_group_id'].
		" tokenid2=".$pun_config['ts3_channel_id'].
		" tokendescription=EveBB\screated\stoken\sfor\s".str_replace(' ', '\s', $username).
		" tokencustomset=ident=forum_id\svalue=".$id;
	
	telnet_send($socket, $token);
	$response = telnet_read($socket);
	if ($response['id'] != 0 || !isset($response['token'])) {
		if (defined('PUN_DEBUG')) {
			message("An error has occured while creating the token for <b>".$username."</b> on the Teamspeak3 server.<br/><br/>".$response['msg']);
		} //End if.
		return false;
	} //End if.
	
	$token = $response['token'];
	
	//Now we add/update this to the DB.
	if (!$db->insert_or_update(array('user_id' => $id, 'username' => $username, 'token' => $token),'user_id',$db->prefix.'teamspeak3')) {
		if (defined('PUN_DEBUG')) {
			message("An error has occured while creating the database information for <b>".$username."</b>.<br/><br/>Please ensure your database is correctly configured.");
		} //End if.
		return false;
	} //End if.

	return true;
} //End create_token().

function delete_token($id, $username, $cldbid = 0) {
	global $db, $pun_config;
	
	$socket;

	if (!$socket = telnet_open($pun_config['ts3_ip'], intval($pun_config['ts3_query_port']), intval($pun_config['ts3_timeout']), $socket)) {
		if (defined('PUN_DEBUG')) {
			message("Unable to open a connection to the Teamspeak3 server.<br/><br/>Please verify it is currently running and accepting connections.");
		} //End if.
		return false;
	} //End if.
	
	telnet_send($socket, "login ".$pun_config['ts3_user']." ".$pun_config['ts3_pass']);
	$response = telnet_read($socket);
	if ($response['id'] != 0) {
		if (defined('PUN_DEBUG')) {
			message("An error has occured while logging into the Teamspeak3 server.<br/><br/>".$response['msg']);
		} //End if.
		return false;
	} //End if.
	
	telnet_send($socket, "use sid=".$pun_config['ts3_sid']);
	$response = telnet_read($socket);
	if ($response['id'] != 0) {
		if (defined('PUN_DEBUG')) {
			message("An error has occured while selecting the <b>sid</b> of the Teamspeak3 server.<br/><br/>".$response['msg']);
		} //End if.
		return false;
	} //End if.
	
	if ($cldbid == 0) {
		telnet_send($socket, "customsearch ident=forum_id pattern=".$id);
		$response = telnet_read($socket);
		if ($response['id'] != 0 || !isset($response['cldbid'])) {
			if (defined('PUN_DEBUG')) {
				message("An error has occured while searching for the token beloning to <b>".$username."</b> on the Teamspeak3 server.<br/><br/>".$response['msg']);
			} //End if.
			return false;
		} //End if.
		
		$cldbid = $response['cldbid'];
	} //End if.
	
	telnet_send($socket, "clientdbdelete cldbid=".$cldbid);
	$response = telnet_read($socket);
	if ($response['id'] != 0) {
		if (defined('PUN_DEBUG')) {
			message("An error has occured while deleting <b>".$username."</b> from the Teamspeak3 server.<br/><br/>".$response['msg']);
		} //End if.
		return false;
	} //End if.
	
	$sql = "DELETE FROM ".$db->prefix."teamspeak3 WHERE id=".$id;
	if (!$db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			message("An error has occured while deleting the database information for <b>".$username."</b>.<br/><br/>Please ensure your database is correctly configured.");
		} //End if.
		return false;
	} //End if.
	
	return true;
	
} //End delete_token().

if ($pun_config['ts3_enabled'] == '1') {
	$_HOOKS['users'][] = new Teamspeak3_UsersHook();
	$_HOOKS['rules'][] = new Teamspeak3_RulesHook();
} //End if.

?>