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
			
	try {
		$xml = post_request($url, $vars);
		echo $xml;
	} catch (Exception $e) {
		echo sprintf($error, "Unable to fetch API data.");
	} //End try - catch().
} else if (isset($_GET['corp_name'])) {
	$url = "http://api.eve-online.com/corp/CorporationSheet.xml.aspx";
	$vars = array(
			'corporationID' => intval($_GET['corpID'])
			);
		
	try {
		$xml = post_request($url, $vars);
		echo $xml;
	} catch (Exception $e) {
		echo sprintf($error, "Unable to fetch API data.");
	} //End try - catch().
} else {
	echo sprintf($error, "No Action Specified.");
} //End if - else().

?>