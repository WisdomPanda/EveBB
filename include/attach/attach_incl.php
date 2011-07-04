<?php

// These constants are used when doing security checks

define('ATTACH_DOWNLOAD',		1);
define('ATTACH_UPLOAD',			2);
define('ATTACH_DELETE',			4);
define('ATTACH_OWNER_DELETE',	8);

$attach_icons = array();

require PUN_ROOT.'include/attach/attach_func.php';	// require the file with all nifty functions
	
// require the attachment language file
if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/attach.php'))
	require PUN_ROOT.'lang/'.$pun_user['language'].'/attach.php'; 
else
	require PUN_ROOT.'lang/English/attach.php';
