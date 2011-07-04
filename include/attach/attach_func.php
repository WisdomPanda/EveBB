<?php
/* Functions in this file
working:
bool 	attach_rules(int rules, int check)
bool	attach_allow_upload(int rules, int max_file_size, string allowed_file_ext, int uploaded_size, string uploaded_file_name)
string 	attach_icon(string extension)
string	attach_generate_pathname(string storagepath)
string	attach_generate_filename(string storagepath, int messagelenght, int filesize)
string	attach_create_mime(string extension)
string  attach_get_extension(string filename)
bool	attach_check_extension(string filename, string allowed)
void    attach_fix_icon_array(void)
bool    attach_delete_attachment(int item)
bool	attach_create_subfolder(string subfolder)
int		attach_create_attachment(string filename, string mime, int filesize, string tmp_name, int post id, int messagelenght)
void	attach_delete_thread(int threadid)
void	attach_delete_post(int postid)

todo:

*/


function attach_rules($rules=0, $check=1){ 	//binary check if check is in rules
	if($rules & $check && $rules != 0)return true;
	else return false;
}

function attach_allow_upload($rules=0,$max_size=0,$file_ext='',$upload_size=10,$upload_name=''){
	global $pun_user,$pun_config;
	$attach_allowed = false;
	
	//check so that the user is allowed to upload
	if(attach_rules($rules,ATTACH_UPLOAD)||$pun_user['g_id']==PUN_ADMIN)
		$attach_allowed=true;
	else 
		$attach_allowed=false;

	// check for file extension
	if(($attach_allowed && attach_check_extension($upload_name,$file_ext))||$pun_user['g_id']==PUN_ADMIN)
		$attach_allowed=true;
	else 
		$attach_allowed=false;

	// check for filesize (the only thing Administrators cannot override, so it needs to be last)
	if((($attach_allowed && $upload_size <= $max_size)||($pun_user['g_id']==PUN_ADMIN)) && $upload_size <= $pun_config['attach_max_size'])
		$attach_allowed=true;
	else 
		$attach_allowed=false;
		
	return $attach_allowed;
}


function attach_icon($extension){
	global $pun_config, $attach_icons, $pun_user;	//fetch some global stuff needed
	if(count($attach_icons) == 0 && strlen($pun_config['attach_icon_extension']) != 0)attach_fix_icon_array();
	$icon_url = $pun_config['attach_icon_folder'].'unknown.png';	// default icon, if none found in the attachment icon list
	if($pun_user['show_img'] == 0||$pun_config['attach_use_icon']== 0)return '';	// user doesn't want to see images.
	if(array_key_exists($extension,$attach_icons))$icon_url = $pun_config['attach_icon_folder'].$attach_icons[$extension];	// if extension exist, assign that one to the url instead of the default
	return '<img src="'.$icon_url.'" width="15" height="15" alt="'.pun_htmlspecialchars($extension).'" />';	// return the image stuff...
}



function attach_fix_icon_array(){
	global $pun_config, $attach_icons;
	$icon_extension = explode('"',$pun_config['attach_icon_extension']);
	$icon_name = explode('"',$pun_config['attach_icon_name']);
	for($i = 0; $i < count($icon_extension); $i++){
		$attach_icons[$icon_extension[$i]]=$icon_name[$i];
	}
}



function attach_generate_pathname($storagepath=''){
	if(strlen($storagepath)!=0){
		//we have to check so that path doesn't exist already...
		$not_unique=true;
		while($not_unique){
			$newdir = attach_generate_pathname();
			if(!is_dir($storagepath.$newdir))return $newdir;
		}
	}else
		return substr(md5(time().'Salt keyword, replace if you want to'),0,32);
}



function attach_generate_filename($storagepath, $messagelenght=0, $filesize=0){
	$not_unique=true;
	while($not_unique){
		$newfile = md5(attach_generate_pathname().$messagelenght.$filesize.'Some more salt keyworbs, change if you want to').'.attach';
		if(!is_file($storagepath.$newfile))return $newfile;
	}	
}

function attach_create_attachment($name='', $mime='', $size=0, $tmp_name='', $post_id=0, $messagelenght=0){
		global $db, $pun_user, $pun_config;

		// fetch an unique name for the file
		$unique_name = attach_generate_filename($pun_config['attach_basefolder'].$pun_config['attach_subfolder'].'/',$messagelenght,$size);

		// move the uploaded file from temp to the attachment folder and rename the file to the unique name
		if(!move_uploaded_file($tmp_name,$pun_config['attach_basefolder'].$pun_config['attach_subfolder'].'/'.$unique_name))
			error('Unable to move file from: '.$tmp_name.' to '.$pun_config['attach_basefolder'].$pun_config['attach_subfolder'].'/'.$unique_name.'',__FILE__,__LINE__);
			//return false;
			
		if(strlen($mime)==0)
			$mime = attach_create_mime(attach_find_extention($name));
		
		// update the database with this info
		$result = $db->query('INSERT INTO '.$db->prefix.'attach_2_files (owner,post_id,filename,extension,mime,location,size) VALUES ('.$pun_user['id'].', '.$post_id.', \''.$db->escape($name).'\', \''.$db->escape(attach_get_extension($name)).'\', \''.$db->escape($mime).'\', \''.$db->escape($pun_config['attach_subfolder'].'/'.$unique_name).'\', '.$size.')')or error('Unable to insert attachment record into database.',__FILE__,__LINE__,$db->error());
		return true;
}

function attach_create_subfolder($newfolder=''){
	global $db,$pun_config,$pun_user;
	
	if(strlen($newfolder)==0||$pun_user['g_id']!=PUN_ADMIN)
		return false;
		
	// check to see if that folder is there already, then just update the config ...
	if(!is_dir($pun_config['attach_basefolder'].$newfolder)){
		// if the folder doesn't excist, try to create it
		if(!mkdir($pun_config['attach_basefolder'].$newfolder,0755))
			error('Unable to create new subfolder with name \''.$pun_config['attach_basefolder'].$newfolder.'\' with mode 0755',__FILE__,__LINE__);
		// create a .htaccess and index.html file in the new subfolder
		if(!copy($pun_config['attach_basefolder'].'.htaccess', $pun_config['attach_basefolder'].$newfolder.'/.htaccess'))
			error('Unable to copy .htaccess file to new subfolder with name \''.$pun_config['attach_basefolder'].$newfolder.'\'',__FILE__,__LINE__);
		if(!copy($pun_config['attach_basefolder'].'index.html', $pun_config['attach_basefolder'].$newfolder.'/index.html'))
			error('Unable to copy index.html file to new subfolder with name \''.$pun_config['attach_basefolder'].$newfolder.'\'',__FILE__,__LINE__);
		// if the folder was created continue
	}
	$form = array ('subfolder' => $newfolder);
	// update the config, if the subfolder has changed
	while (list($key, $input) = @each($form))
	{
		// Only update values that have changed
		if ($pun_config['attach_'.$key] != $input)
		{
			if ($input != '' || is_int($input))
				$value = '\''.$db->escape($input).'\'';
			else
				$value = 'NULL';

			$db->query('UPDATE '.$db->prefix.'config SET conf_value='.$value.' WHERE conf_name=\'attach_'.$key.'\'') or error('Unable to update attachment mod config', __FILE__, __LINE__, $db->error());
		}
	}
	require_once PUN_ROOT.'include/cache.php';
	generate_config_cache();
	// return true if everything has gone as planned, return false if the new folder could not be created (rights etc?)
	return true;
}



function attach_create_mime($extension=''){
	$mimecodes = array (						// mimes taken from microsoft ... those that don't need external programs to work
												// abit unsure on some file extentions, but they aren't used so much anyhow :P
						//fileext.				mimetype
						'diff'			=> 		'text/x-diff',
						'patch'			=> 		'text/x-diff',
						'rtf' 			=>		'text/richtext',
						'html'			=>		'text/html',
						'htm'			=>		'text/html',
						'aiff'			=>		'audio/x-aiff',
						'iff'			=>		'audio/x-aiff',
						'basic'			=>		'audio/basic',  // no idea about extention
						'wav'			=>		'audio/wav',
						'gif'			=>		'image/gif',
						'jpg'			=>		'image/jpeg',
						'jpeg'			=>		'image/pjpeg',
						'tif'			=>		'image/tiff',
						'png'			=>		'image/x-png',
						'xbm'			=>		'image/x-xbitmap',  // no idea about extention
						'bmp'			=>		'image/bmp',
						'xjg'			=>		'image/x-jg',  // no idea about extention
						'emf'			=>		'image/x-emf',  // no idea about extention
						'wmf'			=>		'image/x-wmf',  // no idea about extention
						'avi'			=>		'video/avi',
						'mpg'			=>		'video/mpeg',
						'mpeg'			=>		'video/mpeg',
						'ps'			=>		'application/postscript',
						'b64'			=>		'application/base64',  // no idea about extention
						'macbinhex'		=>		'application/macbinhex40',  // no idea about extention
						'pdf'			=>		'application/pdf',
						'xzip'			=>		'application/x-compressed',  // no idea about extention
						'zip'			=>		'application/x-zip-compressed',
						'gzip'			=>		'application/x-gzip-compressed',
						'java'			=>		'application/java',
						'msdownload'	=>		'application/x-msdownload'  // no idea about extention
						);

	foreach ($mimecodes as $type => $mime ){
		if($extention==$type)
			return $mime;
	}
	return 'application/octet-stream';	// default, if not defined above...
}


function attach_get_extension($filename=''){
	if(strlen($filename)==0)return '';
	return strtolower(ltrim(strrchr($filename,"."),"."));
}


function attach_check_extension($filename='', $allowed_extensions=''){
	global $pun_config;
	$cur_file_extension = attach_get_extension($filename);
	
	$allowed_extensions = (strlen($allowed_extensions)!=0)?explode('"',$allowed_extensions):array();
	$denied_extensions = (strlen($pun_config['attach_always_deny'])!=0)?explode('"',$pun_config['attach_always_deny']):array();

	//if it's denied, return false
	foreach($denied_extensions AS $key => $value){
		//if the extension is there, return false
		if($value==$cur_file_extension)return false;
	}
	
	//if it's not there, check against the allowed, if there are any entered...
	if(count($allowed_extensions)!=0){
		foreach($allowed_extensions AS $key => $value){
			//if the extension is there, return true
			if($value==$cur_file_extension)return true;
		}
		return false;
	}
	//ok, nothing there...so then it's ok to take it on...
	return true;
}


function attach_delete_attachment($item=0){
	global $db, $pun_user, $pun_config;
	// check if the user may delete it ... can be overridden, but that's only if create orphans are off...
	$attach_allowed_delete = false;

	if($pun_user['g_id']==PUN_ADMIN)
		$attach_allowed_delete = true;
	else{
		// make a database query to check if the user is allowed to delete it, and if the user is the owner.
		$result = $db->query('SELECT af.owner, ar.rules FROM '.$db->prefix.'attach_2_files AS af, '.$db->prefix.'attach_2_rules AS ar, '.$db->prefix.'posts AS p, '.$db->prefix.'topics AS t WHERE af.id = \''.$item.'\' AND p.id = af.post_id AND t.id=p.topic_id AND ar.forum_id = t.forum_id AND ar.group_id='.$pun_user['g_id'].' LIMIT 1')or error('Unable to fetch owner and attachment rules',__FILE__,__LINE__,$db->error());
		if($db->num_rows($result)==1){
			list($attach_owner, $attach_rules) = $db->fetch_row($result);
			// depending on if the user doing this is the owner or another (moderator?) ,different rulechecks...
			$attach_rule_to_check = ($attach_owner == $pun_user['id']) ? ATTACH_OWNER_DELETE : ATTACH_DELETE;
			$attach_allowed_delete = attach_rules($attach_rules,$attach_rule_to_check);
		}
		if($pun_config['attach_create_orphans']=='0' && (basename($_SERVER['SCRIPT_NAME'])=='delete.php'||basename($_SERVER['SCRIPT_NAME'])=='moderate.php')){
			$attach_allowed_delete = true;
			// this thing overrides if no orphans should be created, and thus the user must always be able to delete all attachments in threads or posts when using delete.php or moderate.php
		}
	}
	
	if($attach_allowed_delete){
		// fetch the info for the file
		$result = $db->query('SELECT af.location FROM '.$db->prefix.'attach_2_files AS af WHERE af.id='.intval($item).' LIMIT 1')or error('Unable to load file info',__FILE__,__LINE__,$db->error());
		if($db->num_rows($result)==1)
			list($attach_location) = $db->fetch_row($result);		
		// first empty the file
		$fp = fopen($pun_config['attach_basefolder'].$attach_location,'wb'); //wb = write, reset file to 0 bytes if existing, and b is just for windows, to tell it's binary mode...is ignored on other OS:es
		if (!$fp)
			error('Error creating filepointer for file to delete/reset size, for attachment with id: "'.$item.'"',__FILE__,__LINE__);
		fclose($fp); // file should now be 0 bytes, 'w' will place the pointer at start, and trunate the file... and I don't put anything in there...
		// if successful, remove the database entry
		$result = $db->query('DELETE FROM '.$db->prefix.'attach_2_files WHERE id='.intval($item).' LIMIT 1')or error('Unable to delete attachment record in database',__FILE__,__LINE__,$db->error());
		return true;
	}else
		error('Error deleting attachment, not allowed to delete attachment (item_id=\''.$item.'\')',__FILE__,__LINE__);
}

function attach_delete_thread($id=0){
	global $db,$pun_config;
	$ok=true;
	// check if orphans should be made or not
	if($pun_config['attach_create_orphans']!=1){
		//if delete, fetch all post id's of the posts in this thread
		$result_attach = $db->query('SELECT af.id FROM '.$db->prefix.'attach_2_files AS af, '.$db->prefix.'posts AS p WHERE af.post_id=p.id AND p.topic_id='.intval($id)) or error('Error when searching for all attachments in this thread',__FILE__,__LINE__);
		if($db->num_rows($result_attach)>0){
			//fetch all attachment id's
			while((list($attach_id)=$db->fetch_row($result_attach))&&$ok){
				// loop though and delete attachment after attachment (this can take a loooong time)
				$ok = attach_delete_attachment($attach_id);
				// break if attachment failed (then rest will become orphans, but better than a zillion of errors...
			}
		}
	}
}


function attach_delete_post($id=0){
	global $db;
	$ok =true;
	// check for all attachments, 
	$result = $db->query('SELECT af.id FROM '.$db->prefix.'attach_2_files AS af WHERE af.post_id='.intval($id)) or error('Error when searching for all attachments in this post',__FILE__,__LINE__);
	
	if($db->num_rows($result)>0){
		// try to delete each post (I'm using the 'override check' in attach_delete_attachment, so nothing is needed here...)
		while((list($attach_id) = $db->fetch_row($result))&&$ok){
			$ok = attach_delete_attachment($attach_id);  // if one fails, let the other be ... or chanses are that they will fail aswell causing lot of trouble, they becomes orphans instead... 
		}
	}
}


function file_upload_error_message($error_code) 
{
	switch ($error_code) 
	{ 
		case UPLOAD_ERR_INI_SIZE: 
			return 'The uploaded file exceeds the upload_max_filesize directive in php.ini'; 
		case UPLOAD_ERR_FORM_SIZE: 
			return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'; 
		case UPLOAD_ERR_PARTIAL: 
			return 'The uploaded file was only partially uploaded'; 
		case UPLOAD_ERR_NO_FILE: 
			return 'No file was uploaded'; 
		case UPLOAD_ERR_NO_TMP_DIR: 
			return 'Missing a temporary folder'; 
		case UPLOAD_ERR_CANT_WRITE: 
			return 'Failed to write file to disk'; 
		case UPLOAD_ERR_EXTENSION: 
			return 'File upload stopped by extension'; 
		default: 
			return 'Unknown upload error'; 
	} 
}

function format_bytes($size) 
{
	$units = array('B', 'KB', 'MB', 'GB', 'TB');
	for ($i = 0; $size >= 1024 && $i < 4; $i++)
		$size /= 1024;
	return round($size, 1).' '.$units[$i];
}