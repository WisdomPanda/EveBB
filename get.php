<?php

$download_folder = 'downloads/';

if (!isset($_GET['file'])) {
	die("File not found.");
} //End if.

$file = $download_folder.$_GET['file'];
 
if(!file_exists($file)) {
    die('File not found');
} else {
	$db = mysql_connect('localhost', 'evebbcom_main', 'evebb101!');
	mysql_select_db('evebbcom_main');
	if ($db) {
		mysql_query("INSERT INTO main_downloads(ip, time, file) VALUES('".$_SERVER['REMOTE_ADDR']."', ".time().", '".$_GET['file']."');") or die(mysql_error());
		mysql_close();
	} //End if.
	
	$fsize = filesize($file);
	
	header("Cache-Control: public");
	header("Content-Description: File Transfer");
	header("Content-Disposition: attachment; filename=".$_GET['file']);
	header("Content-Type: application/zip");
	header("Content-length: $fsize");
    
	$file = fopen($file, 'r');
	
	while(!feof($file)) {
		$buffer = fread($file, 2048);
		echo $buffer;
	} //End while loop().
	
	fclose($file);
     
     exit;
} //End if.

exit;
 
 ?>