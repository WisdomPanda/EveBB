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
	fgets($socket);
	fgets($socket);
	
	return true;
	
} //End telnet_open().

function telnet_close(&$socket) {
	fputs($socket, "quit\n");
	fclose($socket);
	$socket = null;
} //End telnet_close().

function telnet_send(&$socket, $msg) {
	fputs($socket, $msg."\n");
} //End telnet_send().

function telnet_read(&$socket) {
	
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

function telnet_read_blob(&$socket) {
	
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

function test_connection(&$msg, &$log) {
	global $db, $pun_config;
	$log = 'Trying to connect to server... ';
	
	$socket;

	if (!telnet_open($pun_config['ts3_ip'], intval($pun_config['ts3_query_port']), intval($pun_config['ts3_timeout']), $socket)) {
		$msg = "Unable to open a connection to the Teamspeak3 server.<br/><br/>Please verify it is currently running and accepting connections.";
		telnet_close($socket);
		return false;
	} //End if.
	
	$log .= "Done.\n";
	$log .= "Sending login information... ";
	
	telnet_send($socket, "login ".$pun_config['ts3_user']." ".$pun_config['ts3_pass']);
	$response = telnet_read($socket);
	if ($response['id'] != 0) {
		$msg = "An error has occured while logging into the Teamspeak3 server.<br/><br/>".$response['error'].
				'Server: '.$pun_config['ts3_ip'].':'.intval($pun_config['ts3_query_port']).', '.intval($pun_config['ts3_timeout']).'<br/><br/>'.
				'Command sent: '."login ".$pun_config['ts3_user']." ".$pun_config['ts3_pass'].'<br/><br/>'.
				'Received: '.$response['msg'];
		telnet_close($socket);
		return false;
	} //End if.
	
	$log .= "Done.\n";
	$log .= "Selecting server... ";
	
	telnet_send($socket, "use sid=".$pun_config['ts3_sid']);
	$response = telnet_read($socket);
	if ($response['id'] != 0) {
		$msg = "An error has occured while selecting the <b>sid</b> of the Teamspeak3 server.<br/><br/>".$response['msg'];
		telnet_close($socket);
		return false;
	} //End if.
	
	$log .= "Done.\n";
	$log .= "Listing tokens... ";
	
	telnet_send($socket, "privilegekeylist");
	
	$response = telnet_read_blob($socket);
	if ($response['id'] !=  0) {
		$msg = "An error has occured while listing the tokens of the Teamspeak3 server.<br/><br/>".$response['msg'];
		telnet_close($socket);
		return false;
	} //End if.
	
	if (count($response['tokens']) == 0) {
		$log .= " [0] tokens found.";
		telnet_close($socket);
		return true;
	} //End if.
	
	$log .= " [".count($response['tokens'])."] tokens found.\n";
	$log .= "Listing token descriptions; \n";
	
	foreach($response['tokens'] as $token) {
		$log .= str_replace('\s', ' ', $token['token_description'])."\n";
	} //End foreach().
	
	telnet_close($socket);
	
	$msg = 'EveBB has successfully establish a connection to your Teamspeak3 server!';
	
	return true;
} //End test_connection().

function create_token($id, $username) {
	global $db, $pun_config;
	
	$socket;
	
	if (!telnet_open($pun_config['ts3_ip'], intval($pun_config['ts3_query_port']), intval($pun_config['ts3_timeout']), $socket)) {
		if (defined('PUN_DEBUG')) {
			message("Unable to open a connection to the Teamspeak3 server.<br/><br/>Please verify it is currently running and accepting connections.");
		} //End if.
		telnet_close($socket);
		return false;
	} //End if.
	
	telnet_send($socket, "login ".$pun_config['ts3_user']." ".$pun_config['ts3_pass']);
	$response = telnet_read($socket);
	if ($response['id'] != 0) {
		if (defined('PUN_DEBUG')) {
			message("An error has occured while logging into the Teamspeak3 server.<br/><br/>".$response['msg']);
		} //End if.
		telnet_close($socket);
		return false;
	} //End if.
	
	telnet_send($socket, "use sid=".$pun_config['ts3_sid']);
	$response = telnet_read($socket);
	if ($response['id'] != 0) {
		if (defined('PUN_DEBUG')) {
			message("An error has occured while selecting the <b>sid</b> of the Teamspeak3 server.<br/><br/>".$response['msg']);
		} //End if.
		telnet_close($socket);
		return false;
	} //End if.
	
	$token = "tokenadd tokentype=0 tokenid1=".$pun_config['ts3_group_id'].
		" tokenid2=".$pun_config['ts3_channel_id'].
		" tokendescription=EveBB\screated\stoken\sfor\s".str_replace(' ', '\s', addslashes($username)).
		" tokencustomset=ident=forum_id\svalue=".$id;
	
	telnet_send($socket, $token);
	$response = telnet_read($socket);
	if ($response['id'] != 0 || !isset($response['token'])) {
		if (defined('PUN_DEBUG')) {
			message("An error has occured while creating the token for <b>".$username."</b> on the Teamspeak3 server.<br/><br/>".$response['msg']);
		} //End if.
		telnet_close($socket);
		return false;
	} //End if.
	
	$token = $response['token'];
	
	telnet_close($socket);
	
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

	if (!telnet_open($pun_config['ts3_ip'], intval($pun_config['ts3_query_port']), intval($pun_config['ts3_timeout']), $socket)) {
		if (defined('PUN_DEBUG')) {
			message("Unable to open a connection to the Teamspeak3 server.<br/><br/>Please verify it is currently running and accepting connections.");
		} //End if.
		telnet_close($socket);
		return false;
	} //End if.
	
	telnet_send($socket, "login ".$pun_config['ts3_user']." ".$pun_config['ts3_pass']);
	$response = telnet_read($socket);
	if ($response['id'] != 0) {
		if (defined('PUN_DEBUG')) {
			message("An error has occured while logging into the Teamspeak3 server.<br/><br/>".$response['msg']);
		} //End if.
		telnet_close($socket);
		return false;
	} //End if.
	
	telnet_send($socket, "use sid=".$pun_config['ts3_sid']);
	$response = telnet_read($socket);
	if ($response['id'] != 0) {
		if (defined('PUN_DEBUG')) {
			message("An error has occured while selecting the <b>sid</b> of the Teamspeak3 server.<br/><br/>".$response['msg']);
		} //End if.
		telnet_close($socket);
		return false;
	} //End if.
	
	if ($cldbid == 0) {
		telnet_send($socket, "customsearch ident=forum_id pattern=".$id);
		$response = telnet_read($socket);
		if ($response['id'] != 0) {
			if (defined('PUN_DEBUG')) {
				message("An error has occured while searching for the token beloning to <b>".$username."</b> on the Teamspeak3 server.<br/><br/>".$response['msg']);
			} //End if.
		telnet_close($socket);
			return false;
		} //End if.
		
		if (isset($response['cldbid'])) {
			$cldbid = $response['cldbid'];
		} else {
			$cldbid = 0;
		} //End if - else.
	} //End if.
	
	if ($cldbid > 0) {
		telnet_send($socket, "clientdbdelete cldbid=".$cldbid);
		$response = telnet_read($socket);
		if ($response['id'] != 0) {
			if (defined('PUN_DEBUG')) {
				message("An error has occured while deleting <b>".$username."</b> from the Teamspeak3 server.<br/><br/>".$response['msg']);
			} //End if.
			telnet_close($socket);
			return false;
		} //End if.
	} //End if.
	
	telnet_close($socket);
	
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

function clean_tokens($return = false) {
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
			message("An error has occured while logging into the Teamspeak3 server.<br/><br/>".$response['error'].
				'Server: '.$pun_config['ts3_ip'].':'.intval($pun_config['ts3_query_port']).', '.intval($pun_config['ts3_timeout']).'<br/><br/>'.
				'Command sent: '."login ".$pun_config['ts3_user']." ".$pun_config['ts3_pass'].'<br/><br/>'.
				'Received: '.$response['msg']);
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
	
	telnet_send($socket, "privilegekeylist");
	
	$response = telnet_read_blob($socket);
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
			telnet_send($socket, "privilegekeydelete token=".$token['token']);
			$response = telnet_read($socket);
			if ($response['id'] !=  0) {
				$log .= 'Unable to remove token ['.$token['token'].'] with description '.str_replace('\s', ' ', $token['token_description']).'<br/>';
			} else {
				$log .= 'Removed token ['.$token['token'].'].<br/>';
			} //End if - else.
		} else {
			$log .= 'Skipping token ['.$token['token'].'] because tokenid1='.$token['token_id1'].'.<br/>';
		} //End if - else.
	} //End foreach().
	
	telnet_close($socket);
	
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
