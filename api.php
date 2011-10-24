<?php

define('EVE_ENABLED', 1);
define('PUN_DEBUG', 1);


define('PUN_ROOT', dirname(__FILE__).'/');

//Determine if we can/should use cURL where possible.
if (extension_loaded('curl') && $pun_config['o_use_fopen'] != '1') {
	define('EVEBB_CURL', 1);
} //End if.
require(PUN_ROOT.'include/request.php');
$pun_request = new Request();


$error = '<?xml version=\'1.0\' encoding=\'UTF-8\'?>
<result><error><![CDATA[%s]]></error></result>';

if (isset($_GET['char_list'])) {
	//keyid-vcode
	$url = "http://api.eve-online.com/account/Characters.xml.aspx";
	$data = explode('-',$_GET['char_list']);
	$vars = array(
				'keyID' => intval($data[0]),
				'vCode' => $data[1]
			);
			
	if (!$xml = $pun_request->post($url, $vars)) {
		if (defined('PUN_DEBUG')) {
			$err = error_get_last();
			echo sprintf($error, "Unable to fetch API data.<br/>Server Says: [".$err['type']."] ".$err['message']." in file <b>".$err['file']."</b> on line: <b>".$err['line']."</b>");
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
			
	if (!$xml = $pun_request->post($url, $vars)) {
		echo sprintf($error, "Unable to fetch API data.");
		exit;
	} //End if.
	
} else {
	$xml = sprintf($error, "No Action Specified.");
} //End if - else().

echo $xml;

?>