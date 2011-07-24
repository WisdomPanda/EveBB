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
			return false; //Not enabled, do nothing.
		} //End if.
		
		//Are they in the group we require?
		$groups = array();
		
		//Bit of a time saver first.
		if ($user['group_id'] != $pun_config['ts3_auth_group']) {
			$sql = "SELECT group_id FROM ".$db->prefix."groups_users WHERE user_id=".$user['id']." AND group_id=".$pun_config['ts3_auth_group'];
			
			if (!$result = $db->query($sql)) {
				if (defined('PUN_DEBUG')) {
					error("Unable to fetch group info.", __FILE__, __LINE__, $db->error());
				} //End if.
				return; //Keep it silent.
			} //End if.
			
			if ($db->num_rows($result) == 0) {
				//No auths.
				return;
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
		
		//Flag them for a token.
		$sql = "INSERT INTO ".$db->prefix."teamspeak3(user_id, username, token) VALUES(".$user['id'].", 'queued', '0')";
		$db->query($sql);
	} //End authed_row().
	
	function restrict_user($user) {
		global $pun_config;
		
		if ($pun_config['ts3_enabled'] != '1') {
			return; //Not enabled, do nothing.
		} //End if.
		
		//Flag them for delete.
		$sql = "UPDATE ".$db->prefix."teamspeak3 SET token='d' WHERE user_id=".$user['id'].";";
		$db->query($sql);
		
		return false;
	} //End restrict_user().
	
} //End Teamspeak3_RulesHook class.

function ts3_cron_task(&$log) {
	global $db, $pun_config;
	
	if ($pun_config['ts3_enabled'] != '1') {
		$log .= 'TS3 not enabled.';
		return; //Not enabled, do nothing.
	} //End if.
	
	$log .= "Starting token evaluation...<br/>\n";
	
	//Let's first get the list of people to get tokens
	
	$sql = "
		SELECT
			ts3.*,
			sc.*,
			c.*,
			corp.*,
			u.*,
			g.*,
			ally.*
		FROM
			".$db->prefix."teamspeak3 AS ts3
		INNER JOIN
			".$db->prefix."api_selected_char AS sc
		ON
			ts3.user_id=sc.user_id
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
			corp.corporationID=c.corp_id
		LEFT JOIN
			".$db->prefix."api_alliance_list AS ally
		ON
			corp.allianceID=ally.allianceID
		WHERE
			ts3.token='0';";
	if (!$create_result = $db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to get create list.", __FILE__, __LINE__, $db->error());
		} //End if.
		$log .= "Unable to get create list.<br/>\n";
		return false;
	} //End if.
	
	$log .= "Creation list gathered...<br/>\n";
	
	//Ok, that's done, now lets get the ones that will have a token removed...
	
	$sql = "
		SELECT
			ts3.*
		FROM
			".$db->prefix."teamspeak3 AS ts3
		WHERE
			ts3.token='d';";
	if (!$delete_result = $db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to get delete list.", __FILE__, __LINE__, $db->error());
		} //End if.
		$log .= "Unable to get delete list.<br/>\n";
		return false;
	} //End if.
	
	
	$log .= "Delete list gathered...<br/>\n";
	
	//Now, return silently if either don't require any action...
	if ($db->num_rows($create_result) == 0 && $db->num_rows($delete_result) == 0) {
		$log .= "No action required, stopping process.<br/>\n";
		return false;
	} //End if.
	
	$log .= "Establishing TS3 connection...<br/>\n";
	
	//Someone needs something, on we go!
	
	//This is our persistant ts3 socket. This will stop us from flooding the server with connections.
	$socket = null;
	
	if (!ts3_telnet_open($pun_config['ts3_ip'], intval($pun_config['ts3_query_port']), intval($pun_config['ts3_timeout']), $socket)) {
		$log .= "Unable to open a connection to the Teamspeak3 server.<br/><br/>Please verify it is currently running and accepting connections.";
		ts3_telnet_close($socket);
		return false;
	} //End if.
	
	ts3_telnet_send($socket, "login ".$pun_config['ts3_user']." ".$pun_config['ts3_pass']);
	$response = ts3_telnet_read($socket);
	if ($response['id'] != 0) {
		$log .= "An error has occured while logging into the Teamspeak3 server.<br/><br/>".$response['msg'];
		ts3_telnet_close($socket);
		return false;
	} //End if.
	
	ts3_telnet_send($socket, "use sid=".$pun_config['ts3_sid']);
	$response = ts3_telnet_read($socket);
	if ($response['id'] != 0) {
		$log .= "An error has occured while selecting the ServerID of the Teamspeak3 server.<br/><br/>".$response['msg'];
		ts3_telnet_close($socket);
		return false;
	} //End if.

	$log .= "Connection established.<br/>\n";
	$log .= "Issuing tokens...<br/>\n";
	
	while ($user  = $db->fetch_assoc($create_result)) {
		//Are they in the group we require?
		$groups = array();
		
		$log .= "[".$user['username']."]: Checking group assignments...<br/>\n";
		
		//Bit of a time saver first.
		if ($user['group_id'] != $pun_config['ts3_auth_group']) {
			$sql = "SELECT group_id FROM ".$db->prefix."groups_users WHERE user_id=".$user['id']." AND group_id=".$pun_config['ts3_auth_group'];
			
			if (!$result = $db->query($sql)) {
				$log .= "Unable to get user [".$user['id']."] group list.<br/>\n";
				return; //Keep it silent.
			} //End if.
			
			if ($db->num_rows($result) == 0) {
				//No auths.
				$db->query("DELETE FROM ".$db->prefix."teamspeak3 WHERE user_id=".$user['id']);
				$log .= "Unauthed user foudn to be flagged for token, removing..<br/>\n";
				continue;
			} //End if.
			
		} //End if.
	
		$log .= "[".$user['username']."]: Group checks out.<br/>\n";
		$log .= "[".$user['username']."]: Checking for existing tokens...<br/>\n";
		
		//The user checks out. Check to make sure they exist in the DB, and if they don't, we'll add them.
		$sql = "
			SELECT
				ts3.token,
				ts3.username AS nickname
			FROM
				".$db->prefix."teamspeak3 AS ts3
			WHERE
				ts3.user_id=".$user['id']." AND token!='0' AND token!='d'";

		if (!$result = $db->query($sql)) {
			$log .= "[".$user['username']."]: Unable to get token list.<br/>\n";
			return; //Keep it silent.
		} //End if.
		
		if ($db->num_rows($result) > 0) {
			//They already exist.
			$log .= "[".$user['username']."]: User already has a token.<br/>\n";
			return;
		} //End if.
		
		$log .= "[".$user['username']."]: No tokens found.<br/>\n";
		$log .= "[".$user['username']."]: Issuing creation command...<br/>\n";
		
		//Lets build them a token!
		$username = $user['ticker'].'-'.$user['character_name'];
		if (strlen($user['allianceid']) > 0) {
			$username = $user['shortname'].'-'.$username;
		} //End if.
		
		if (!ts3_create_token($user['id'], $username, $socket)) {
			$log .= "[".$user['username']."]:  Token creation failed. On fail we shut down the connection, so the process has been stopped.<br/>\n";
			return;
		} //End if.
		
		$log .= "[".$user['username']."]: Token created.<br/>\n";
	} //End while loop().
	
	//Now, onto the delete corner!
	while ($user = $db->fetch_assoc($delete_result)) {
		//The user has been moved to the naughty corner, lets strip their TS roles as well.
		ts3_delete_token($user['user_id'], $user['username'], 0, $socket); //username is for debugging.
	} //End while loop().
	
	return true;
	
} //End ts3_cron_task().

function ts3_telnet_open($ip, $port, $timeout, &$socket) {
	$socket = @fsockopen($ip, $port, $errno, $errstr, $timeout);
	
	if (!$socket) {
		if (defined('PUN_DEBUG')) {
			error("[$errno] $errstr", __FILE__, __LINE__);
		} //End if.
		return false;
	} //End if.
	
	//Clears MotD
	fgets($socket);
	fgets($socket);
	
	return true;
	
} //End telnet_open().

function ts3_telnet_close(&$socket) {
	fputs($socket, "quit\n");
	fclose($socket);
	$socket = null;
} //End telnet_close().

function ts3_telnet_send(&$socket, $msg) {
	fputs($socket, $msg."\n");
} //End telnet_send().

function ts3_telnet_read(&$socket) {
	
	$end = false;
	$response = array();
	$log = "";
	$msg = "";
	$msg_full = "";
	$limit = 5000;
	$count = 0;
	
	while(!$end) {
		$msg = @fgets($socket);
		$msg_full .= $msg;
		$log = $msg.'<br/>';
		$count++;
		
		if ($count > $limit) {
			$response['id'] = -1;
			$response['msg'] = $msg_full;
			$response['error'] = 'An internal error has occured, resulting in a loop going for longer than it should have.<br/><br/>';
			return $response;
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
			
			$response[trim($temp[0])] = $temp[1];
			
		} //End foreach().
	
	} //End foreach().
	
	return $response;
	
} //End function telnet_read().

function ts3_telnet_read_blob(&$socket) {
	
	$end = false;
	$response = array();
	$log = "";
	$msg = "";
	$msg_full = "";
	$limit = 5000;
	$count = 0;
	
	while(!$end) {
		$msg = @fgets($socket);
		$msg_full .= $msg;
		$log = $msg.'<br/>';
		$count++;
		
		if ($count > $limit) {
			$response['id'] = -1;
			$response['msg'] = $msg_full;
			$response['error'] = 'An internal error has occured, resulting in a loop going for longer than it should have.<br/><br/>';
			return $response;
		} //End if.
		
		if (preg_match('/error .*$/', $msg)) {
			$end = true;
		} //End if - else.
				
	} //End while loop.
	
	$lines = explode("\n", $msg_full);
	
	if (count($lines) < 2) {
		$response['id'] = -1;
		$response['msg'] = 'Incorrect number of results recived.<br/><br/>';
		return $response;
	} //End if.
	
	if (preg_match('/^error .*$/', $lines[1])) {
		$response['id'] = -1;
		$response['msg'] = 'Error encountered: '.$lines[1];
		return $response;
	} //End if.
	
	$lines = explode('|', $lines[0]);
	$response_blob = array();
	$response_blob['msg'] = 'ok';
	$response_blob['id'] = 0;
	
	foreach ($lines as $line) {
		
		$values = explode(' ', $line);
		foreach ($values as $v) {
			$temp = explode('=', $v);
			
			if (count($temp) != 2) {
				continue;
			} //End if.
			
			$response[trim($temp[0])] = $temp[1];
			
		} //End foreach().
		
		$response_blob['tokens'][] = $response;
	
	} //End foreach().
	
	return $response_blob;
	
} //End function telnet_read().

function ts3_test_connection(&$msg, &$log) {
	global $db, $pun_config;
	$log = 'Trying to connect to server... ';
	
	$socket;

	if (!ts3_telnet_open($pun_config['ts3_ip'], intval($pun_config['ts3_query_port']), intval($pun_config['ts3_timeout']), $socket)) {
		$msg = "Unable to open a connection to the Teamspeak3 server.<br/><br/>Please verify it is currently running and accepting connections.";
		ts3_telnet_close($socket);
		return false;
	} //End if.
	
	$log .= "Done.\n";
	$log .= "Sending login information... ";
	
	ts3_telnet_send($socket, "login ".$pun_config['ts3_user']." ".$pun_config['ts3_pass']);
	$response = ts3_telnet_read($socket);
	if ($response['id'] != 0) {
		$msg = "An error has occured while logging into the Teamspeak3 server.<br/><br/>".$response['error'].
				'Server: '.$pun_config['ts3_ip'].':'.intval($pun_config['ts3_query_port']).', '.intval($pun_config['ts3_timeout']).'<br/><br/>'.
				'Command sent: '."login ".$pun_config['ts3_user']." ".$pun_config['ts3_pass'].'<br/><br/>'.
				'Received: '.$response['msg'];
		ts3_telnet_close($socket);
		return false;
	} //End if.
	
	$log .= "Done.\n";
	$log .= "Selecting server... ";
	
	ts3_telnet_send($socket, "use sid=".$pun_config['ts3_sid']);
	$response = ts3_telnet_read($socket);
	if ($response['id'] != 0) {
		$msg = "An error has occured while selecting the <b>sid</b> of the Teamspeak3 server.<br/><br/>".$response['msg'];
		ts3_telnet_close($socket);
		return false;
	} //End if.
	
	$log .= "Done.\n";
	$log .= "Listing tokens... ";
	
	ts3_telnet_send($socket, "privilegekeylist");
	
	$response = ts3_telnet_read_blob($socket);
	if ($response['id'] !=  0) {
		$msg = "An error has occured while listing the tokens of the Teamspeak3 server.<br/><br/>".$response['msg'];
		ts3_telnet_close($socket);
		return false;
	} //End if.
	
	if (count($response['tokens']) == 0) {
		$log .= " [0] tokens found.";
		ts3_telnet_close($socket);
		return true;
	} //End if.
	
	$log .= " [".count($response['tokens'])."] tokens found.\n";
	$log .= "Listing token descriptions; \n";
	
	foreach($response['tokens'] as $token) {
		$log .= str_replace('\s', ' ', $token['token_description'])."\n";
	} //End foreach().
	
	ts3_telnet_close($socket);
	
	$msg = 'EveBB has successfully establish a connection to your Teamspeak3 server!';
	
	return true;
} //End test_connection().

function ts3_create_token($id, $username, &$socket) {
	global $db, $pun_config;
	
	$persist = false;
	
	if ($socket == null) {
	
		if (!ts3_telnet_open($pun_config['ts3_ip'], intval($pun_config['ts3_query_port']), intval($pun_config['ts3_timeout']), $socket)) {
			if (defined('PUN_DEBUG')) {
				message("Unable to open a connection to the Teamspeak3 server.<br/><br/>Please verify it is currently running and accepting connections.");
			} //End if.
			ts3_telnet_close($socket);
			return false;
		} //End if.
		
		ts3_telnet_send($socket, "login ".$pun_config['ts3_user']." ".$pun_config['ts3_pass']);
		$response = ts3_telnet_read($socket);
		if ($response['id'] != 0) {
			if (defined('PUN_DEBUG')) {
				message("An error has occured while logging into the Teamspeak3 server.<br/><br/>".$response['msg']);
			} //End if.
			ts3_telnet_close($socket);
			return false;
		} //End if.
	
		ts3_telnet_send($socket, "use sid=".$pun_config['ts3_sid']);
		$response = ts3_telnet_read($socket);
		if ($response['id'] != 0) {
			if (defined('PUN_DEBUG')) {
				message("An error has occured while selecting the <b>sid</b> of the Teamspeak3 server.<br/><br/>".$response['msg']);
			} //End if.
			telnet_close($socket);
			return false;
		} //End if.
	
	} else {
		$persist = true;
	} //End if - else.
	
	$username = str_replace(' ', '\s', addslashes($username));
	
	$token = "tokenadd tokentype=0 tokenid1=".$pun_config['ts3_group_id'].
		" tokenid2=".$pun_config['ts3_channel_id'].
		" tokendescription=EveBB\screated\stoken\sfor\s".$username.
		" tokencustomset=ident=forum_id\svalue=".$id;
	
	ts3_telnet_send($socket, $token);
	$response = ts3_telnet_read($socket);
	if ($response['id'] != 0 || !isset($response['token'])) {
		if (defined('PUN_DEBUG')) {
			message("An error has occured while creating the token for <b>".$username."</b> on the Teamspeak3 server.<br/><br/>".$response['msg']);
		} //End if.
		ts3_telnet_close($socket);
		return false;
	} //End if.
	
	$token = $response['token'];
	
	if (!$persist) {
		ts3_telnet_close($socket);
	} //End if.
	
	//Now we add/update this to the DB.
	if (!$db->insert_or_update(array('user_id' => $id, 'username' => $username, 'token' => $token),'user_id',$db->prefix.'teamspeak3')) {
		if (defined('PUN_DEBUG')) {
			message("An error has occured while creating the database information for <b>".$username."</b>.<br/><br/>Please ensure your database is correctly configured.");
		} //End if.
		return false;
	} //End if.

	return true;
} //End create_token().

function ts3_delete_token($id, $username, $cldbid, &$socket) {
	global $db, $pun_config;
	
	$persist = false;
	
	if ($socket == null) {
	
		$socket;

		if (!ts3_telnet_open($pun_config['ts3_ip'], intval($pun_config['ts3_query_port']), intval($pun_config['ts3_timeout']), $socket)) {
			if (defined('PUN_DEBUG')) {
				message("Unable to open a connection to the Teamspeak3 server.<br/><br/>Please verify it is currently running and accepting connections.");
			} //End if.
			ts3_telnet_close($socket);
			return false;
		} //End if.
		
		ts3_telnet_send($socket, "login ".$pun_config['ts3_user']." ".$pun_config['ts3_pass']);
		$response = ts3_telnet_read($socket);
		if ($response['id'] != 0) {
			if (defined('PUN_DEBUG')) {
				message("An error has occured while logging into the Teamspeak3 server.<br/><br/>".$response['msg']);
			} //End if.
			ts3_telnet_close($socket);
			return false;
		} //End if.
	
		ts3_telnet_send($socket, "use sid=".$pun_config['ts3_sid']);
		$response = ts3_telnet_read($socket);
		if ($response['id'] != 0) {
			if (defined('PUN_DEBUG')) {
				message("An error has occured while selecting the <b>sid</b> of the Teamspeak3 server.<br/><br/>".$response['msg']);
			} //End if.
			ts3_telnet_close($socket);
			return false;
		} //End if.
	
	} else {
		$persist = true;
	} //End if - else.
	
	if ($cldbid == 0) {
		ts3_telnet_send($socket, "customsearch ident=forum_id pattern=".$id);
		$response = ts3_telnet_read($socket);
		if ($response['id'] != 0) {
			if (defined('PUN_DEBUG')) {
				message("An error has occured while searching for the token beloning to <b>".$username."</b> on the Teamspeak3 server.<br/><br/>".$response['msg']);
			} //End if.
		ts3_telnet_close($socket);
			return false;
		} //End if.
		
		if (isset($response['cldbid'])) {
			$cldbid = $response['cldbid'];
		} else {
			$cldbid = 0;
		} //End if - else.
	} //End if.
	
	if ($cldbid > 0) {
		ts3_telnet_send($socket, "clientdbdelete cldbid=".$cldbid);
		$response = ts3_telnet_read($socket);
		if ($response['id'] != 0) {
			if (defined('PUN_DEBUG')) {
				message("An error has occured while deleting <b>".$username."</b> from the Teamspeak3 server.<br/><br/>".$response['msg']);
			} //End if.
			ts3_telnet_close($socket);
			return false;
		} //End if.
	} //End if.
	
	if (!$persist) {
		ts3_telnet_close($socket);
	} //End if.
	
	$sql = "DELETE FROM ".$db->prefix."teamspeak3 WHERE id=".$id;
	if (!$db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			message("An error has occured while deleting the database information for <b>".$username."</b>.<br/><br/>Please ensure your database is correctly configured.");
		} //End if.
		return false;
	} //End if.
	
	clean_tokens(true);
	
	return true;
	
} //End delete_token().

function ts3_clean_tokens($return = false) {
	global $db, $pun_config;
	
	$sql = "SELECT * FROM ".$db->prefix."teamspeak3";
	if (!$result = $db->query($sql)) {
		if (defined('PUN_DEBUG')) {
			error("Unable to fetch ts3 info.", __FILE__, __LINE__, $db->error());
		} //End if.
		//This is critical and triggered by the user, usually, so display a message.
		message("Unable to fetch ts3 info from the database.<br/><br/>Please make sure the database is setup correctly and that the table exists.");
	} //End if.
	
	$tokens = array();
	
	if ($db->num_rows($result) > 0) {
		//We'll keep it in memory as opposed to making a stupid amount of queries to the database.
		while ($row = $db->fetch_assoc($result)) {
			$tokens[] = $row['token'];
		} //End while loop().
	} //End if.
	
	$socket;

	if (!ts3_telnet_open($pun_config['ts3_ip'], intval($pun_config['ts3_query_port']), intval($pun_config['ts3_timeout']), $socket)) {
		if (defined('PUN_DEBUG')) {
			message("Unable to open a connection to the Teamspeak3 server.<br/><br/>Please verify it is currently running and accepting connections.");
		} //End if.
		return false;
	} //End if.
	
	ts3_telnet_send($socket, "login ".$pun_config['ts3_user']." ".$pun_config['ts3_pass']);
	$response = ts3_telnet_read($socket);
	if ($response['id'] != 0) {
		if (defined('PUN_DEBUG')) {
			message("An error has occured while logging into the Teamspeak3 server.<br/><br/>".$response['error'].
				'Server: '.$pun_config['ts3_ip'].':'.intval($pun_config['ts3_query_port']).', '.intval($pun_config['ts3_timeout']).'<br/><br/>'.
				'Command sent: '."login ".$pun_config['ts3_user']." ".$pun_config['ts3_pass'].'<br/><br/>'.
				'Received: '.$response['msg']);
		} //End if.
		return false;
	} //End if.
	
	ts3_telnet_send($socket, "use sid=".$pun_config['ts3_sid']);
	$response = ts3_telnet_read($socket);
	if ($response['id'] != 0) {
		if (defined('PUN_DEBUG')) {
			message("An error has occured while selecting the <b>sid</b> of the Teamspeak3 server.<br/><br/>".$response['msg']);
		} //End if.
		return false;
	} //End if.
	
	ts3_telnet_send($socket, "privilegekeylist");
	
	$response = ts3_telnet_read_blob($socket);
	if ($response['id'] !=  0) {
		if (defined('PUN_DEBUG')) {
			message("An error has occured while listing the tokens of the Teamspeak3 server.<br/><br/>".$response['msg']);
		} //End if.
		return false;
	} //End if.
	
	if (count($response['tokens']) == 0) {
		return true;
	} //End if.
	
	foreach($response['tokens'] as $token) {
		if ($token['token_id1'] == $pun_config['ts3_group_id'] && !in_array($token['token'], $tokens)) {
			ts3_telnet_send($socket, "privilegekeydelete token=".$token['token']);
			$response = ts3_telnet_read($socket);
			if ($response['id'] !=  0) {
				$log .= 'Unable to remove token ['.$token['token'].'] with description '.str_replace('\s', ' ', $token['token_description']).'<br/>';
			} else {
				$log .= 'Removed token ['.$token['token'].'].<br/>';
			} //End if - else.
		} else {
			$log .= 'Skipping token ['.$token['token'].'] because tokenid1='.$token['token_id1'].'.<br/>';
		} //End if - else.
	} //End foreach().
	
	ts3_telnet_close($socket);
	
	if ($return) {
		return true;
	} //End if.
	
	message('Clean up of keys complete.<br/>
		<br/>
		<div class="codebox"><pre class="vscroll"><code>'.str_replace("<br/>", "\n", $log).'</code></pre></div>');
	
} //End clean_tokens().

if ($pun_config['ts3_enabled'] == '1') {
	$_HOOKS['users'][] = new Teamspeak3_UsersHook();
	$_HOOKS['rules'][] = new Teamspeak3_RulesHook();
} //End if.

?>
