<?php
/*

also it is possible to make your php script resume downloads, to do this you need to check $_SERVER['HTTP_RANGE'] which may contain something like this
 "bytes=10-" - resume from position 10, and to end of file

when sending response it is also needed to send with headers
Accept-Ranges: bytes
Content-Length: {filesize}
Content-Range: bytes 10-{filesize-1}/{ffilesize}

hope its usefull



// translate file name properly for Internet Explorer.
if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE")){
  $fileName = preg_replace('/\./', '%2e', $fileName, substr_count($fileName, '.') - 1);
}




  header("Cache-Control: ");// leave blank to avoid IE errors
  header("Pragma: ");// leave blank to avoid IE errors
  header("Content-type: application/octet-stream");
  header("Content-Disposition: attachment; filename=\"".$fileName."\"");
  header("Content-length:".(string)(filesize($fileString)));
   sleep(1);
   fpassthru($fdl);


*/

/////////////////////////////////////////////////////////////////////////////////////////////////////


define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'include/attach/attach_incl.php'; //Attachment Mod row, loads variables, functions and lang file

if(!isset($_GET['item']))
	message('No file specified, so no download possible');

$attach_item = intval($_GET['item']);	// make it a bit more secure

//check that there is such an item
$result = $db->query('SELECT post_id, filename, extension, mime, location, size FROM '.$db->prefix.'attach_2_files WHERE id='.$attach_item.' LIMIT 1')or error('Unable to search for specified attachment',__FILE__,__LINE__,$db->error());
if($db->num_rows($result)!=1)
	message($lang_common['Bad request']);

list($attach_post_id,$attach_filename,$attach_extension,$attach_mime,$attach_location,$attach_size) = $db->fetch_row($result);

// fetch the rules for the forum where the attachment resides and check if one may download it...
$attach_allow_download=false;
$result = $db->query('SELECT ar.rules FROM '.$db->prefix.'attach_2_rules AS ar, '.$db->prefix.'topics AS t, '.$db->prefix.'posts AS p WHERE ar.group_id='.$pun_user['group_id'].' AND ar.forum_id=t.forum_id AND t.id=p.topic_id AND p.id='.$attach_post_id.' LIMIT 1')or error('Unable to get ruleset for attachment',__FILE__,__LINE__,$db->error());
if($db->num_rows($result)==1)
{
	list($attach_rules)=$db->fetch_row($result);
	$attach_allow_download = attach_rules($attach_rules,ATTACH_DOWNLOAD);
}
elseif($pun_user['g_id']==PUN_ADMIN)
	$attach_allow_download = true; 	//admins always allowed to download 

else
	$attach_allow_download = false;


// so if one isn't allowed to download, give them the no permission message...
if(!$attach_allow_download)
	message($lang_common['No permission']);
	
// ok, if you've got to here you may download the file ...
// later add possibility to resume files ... but not in Attachment Mod 2.0 ;-)
if (($attach_extension=='jpg' || $attach_extension=='jpeg' || $attach_extension=='gif' || $attach_extension=='png') && !isset($_GET['download'])) // show the imageview page
{
	$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_attach['Image view'], pun_htmlspecialchars($attach_filename));
	define('PUN_ALLOW_INDEX', 1);
	define('PUN_ACTIVE_PAGE', 'index');
	require 'header.php';
	?>
<div id="msg" class="block">
	<h2><span><?php echo $lang_attach['Image view'] ?></span></h2>

	<div class="box">
		<div class="inbox">
		<div class="imgbox"><div class="scrollbox"><a href="attachment.php?item=<?php echo $attach_item ?>&amp;download=1"><img src="attachment.php?item=<?php echo $attach_item ?>&amp;download=1" style="max-width: 100%" alt="<?php echo pun_htmlspecialchars($attach_filename) ?>" /></a></div></div><p>
		<?php echo $lang_attach['Download:']; ?> <a href="attachment.php?item=<?php echo $attach_item ?>&amp;download=1"><?php echo pun_htmlspecialchars($attach_filename) ?></a></p>
		<p><a href="javascript: history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</div>
	</div>
</div>	
	<?php	
	require 'footer.php';
	exit();
}else{ // put the file out for download
	// update number of downloads
	$result = $db->query('UPDATE '.$db->prefix.'attach_2_files SET downloads=downloads+1 WHERE id='.$attach_item) or error();

	// open a pointer to the file
	$fp = fopen($pun_config['attach_basefolder'].$attach_location, "rb");
	if(!$fp){
		message($lang_common['Bad request']);
	}else{
		$attach_filename=rawurlencode($attach_filename);	// fix filename (spaces may still mess things up, perhaps add a specific MSIE thing later, not sure though)

		// send some headers
		header('Content-Disposition: attachment; filename='.$attach_filename);
		if(strlen($attach_mime)!=0)
			header('Content-Type: ' . $attach_mime );
		else
			header('Content-type: application/octet-stream'); // a default mime is nothing is defined for the file
		
		header('Pragma: no-cache'); //hmm, I suppose this might be possible to skip, to save some bw, but I'm far from sure, so I let the 'no cache stuff' be...
		header('Expires: 0'); 
		header('Connection: close'); // Thanks to Dexus for figuring out this header (on some systems there was a delay for 5-7s for downloading)
		if($attach_size!=0)
			header('Content-Length: '.$attach_size);
		
		// and finally send the file, fpassthru might be replaced later, rumors say fpassthru use alot of memory...
		fpassthru($fp);
	}
}