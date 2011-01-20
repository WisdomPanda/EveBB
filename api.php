<?php

define('EVE_ENABLED', 1);
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
		echo sprintf($error, "Unable to fetch API data.");
		exit;
	} //End if.
	
} else if (isset($_GET['corp_name'])) {
	$url = "http://api.eve-online.com/corp/CorporationSheet.xml.aspx";
	$vars = array(
			'corporationID' => intval($_GET['corpID'])
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