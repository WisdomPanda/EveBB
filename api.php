<?php

define('EVE_ENABLED', 1);
define('PUN_DEBUG', 1);
require("include/eve_functions.php");


$error = '<?xml version=\'1.0\' encoding=\'UTF-8\'?>
<result><error><![CDATA[%s]]></error></result>';

if (isset($_GET['char_list'])) {
	//user_id-apiKey
	$url = "http://api.eve-online.com/account/Characters.xml.aspx";
	$data = explode('-',$_GET['char_list']);
	$vars = array(
				'userID' => intval($data[0]),
				'apiKey' => $data[1]
			);
			
	if (!$xml = post_request($url, $vars)) {
		if (defined('PUN_DEBUG')) {
			$err = error_get_last();
			echo sprintf($error, "Unable to fetch API data.<br/>PHP Says: [".$err['type']."] ".$err['message']." in file <b>".$err['file']."</b> on line: <b>".$err['line']."</b>");
			exit;
		} //End if.
		echo sprintf($error, "Unable to fetch API data.");
		exit;
	} //End if.
	
} else if (isset($_GET['corp_name'])) {
	$url = "http://api.eve-online.com/corp/CorporationSheet.xml.aspx";
	$vars = array(
			'corporationID' => $_GET['corp_name']
			);
			
	if (!$xml = post_request($url, $vars)) {
		echo sprintf($error, "Unable to fetch API data.");
		exit;
	} //End if.
	
} else {
	$xml = sprintf($error, "No Action Specified.");
} //End if - else().

echo $xml;

?>