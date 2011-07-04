<?php
##
##
##  A few notes of interest for aspiring plugin authors:
##
##  1. If you want to display a message via the message() function, you
##     must do so before calling generate_admin_menu($plugin).
##
##  2. Plugins are loaded by admin_loader.php and must not be
##     terminated (e.g. by calling exit()). After the plugin script has
##     finished, the loader script displays the footer, so don't worry
##     about that. Please note that terminating a plugin by calling
##     message() or redirect() is fine though.
##
##  3. The action attribute of any and all <form> tags and the target
##     URL for the redirect() function must be set to the value of
##     $_SERVER['REQUEST_URI']. This URL can however be extended to
##     include extra variables (like the addition of &amp;foo=bar in
##     the form of this example plugin).
##
##  4. If your plugin is for administrators only, the filename must
##     have the prefix "AP_". If it is for both administrators and
##     moderators, use the prefix "AMP_". This example plugin has the
##     prefix "AMP_" and is therefore available for both admins and
##     moderators in the navigation menu.
##
##  5. Use _ instead of spaces in the file name.
##
##  6. Since plugin scripts are included from the PunBB script
##     admin_loader.php, you have access to all PunBB functions and
##     global variables (e.g. $db, $pun_config, $pun_user etc).
##
##  7. Do your best to keep the look and feel of your plugins' user
##     interface similar to the rest of the admin scripts. Feel free to
##     borrow markup and code from the admin scripts to use in your
##     plugins. If you create your own styles they need to be added to
##     the "base_admin" style sheet.
##
##  8. Consider releasing your plugins under the GNU General Public
##     License (or equivalent).
##
##

// Tell admin_loader.php that this is indeed a plugin and that it is loaded
define('PUN_PLUGIN_LOADED', 1);

require PUN_ROOT.'include/attach/attach_incl.php'; //Attachment Mod row, loads variables, functions and lang file


//
// If we have any input
//

if(isset($_POST['read_documentation'])){	// the user wants to read the documentation, so let him/her do so...
	generate_admin_menu($plugin);	// Display the admin navigation menu
?>
	<div class="block">
		<h2><span>Attachment Mod <?php echo $pun_config['attach_cur_version'] ?> - Documentation</span></h2>
		<div class="box">
			<div class="inbox">
				<h3 style="FONT-SIZE: 2.5em">Introduction</h3>
				<p>(My thoughts)</p>
				<p>Well I think I start to express my thoughts about this mod, why I made it etc.</p>
				<p>When I first found PunBB I thought it was near perfect, all I wanted was attachments, and subforums. I have learned to live without subforums (it has advantages I didn't think of at first), so I wrote an attachment mod to be able to cope with <em>my</em> demands on how <em>I</em> want it to function. I tried to focus primary on security with the first mod, not that much on the storage simplicity or speed. For ease in making security work, I just put the files in the database (no direct linking possible). I never intended the mod to store gigabytes of data, but now I have gone closer and closer to that, and I need another storage method. Also voices has been raised for file/FTP storage (FTP = File Transfer Protocol). I have not added FTP storage, but filestorage. This means that PHP must have access to and write permissions to the disk. FTP storage, <em>I personally</em> don't have a need for, so therefore I haven't put time on that. (I'm selfish enough to keep my wishes as the primary, hehehe) I'm not a programmer first and foremost, but a mechanical engineer student using PunBB for different things, so getting into protocols, filetransfers to other places etc. just seems like a timewaster for me. I rather spend that time finding bugs and/or releasing the mod earlier.</p>
				<p>Either way, I have tried to make this mod in such a way that it should be possible to use together with other mods (like creating a gallery that use attachments as a base). Hopefully it'll inspire someone else to write larger mods as well. (And please oversee all spelling/grammatical errors in this documentation, hehehe, well if you find any you can always mail me the correct text)</p>
				<p>/Frank Hagstr&ouml;m (frank.hagstrom@gmail.com)</p>
				<p></p>
				<p></p>
				<hr />
				<h3 style="FONT-SIZE: 2.5em">Table of contents</h3>
				<p>______________________________________________</p>
				<p>1 Usage</p>
				<p>&nbsp;1.1 Important to know</p>
				<p>&nbsp;1.2 Keeping backups</p>
				<p>&nbsp;1.3 Setting permissions</p>
				<p>&nbsp;1.4 Adding extra icons</p>
				<p>&nbsp;1.5 Making new subfolders</p>
				<p>&nbsp;1.6 Max allowed upload</p>
				<p>______________________________________________</p>
				<p>2 Database</p>
				<p>&nbsp;2.1 Attachment Mod Tables</p>
				<p>&nbsp;&nbsp;2.1.1 attach_2_files</p>
				<p>&nbsp;&nbsp;2.1.2 attach_2_rules</p>
				<p>&nbsp;2.2 Variables in config table</p>
				<p>______________________________________________</p>
				<p>3 Filesystem<p>
				<p>______________________________________________</p>
				<p>4 Functions and variables</p>
				<p>&nbsp;4.1 Functions in attach_func.php</p>
				<p>&nbsp;4.2 Constants in attach_incl.php</p>
				<p>______________________________________________</p>
				<p></p>
				<p></p>
				<hr />
				<h3 style="FONT-SIZE: 2.5em">1 Usage </h3>
				<p>Here's some general usage advice, you should at least read the first chapter before using the mod.</p>
				<p></p>
				<h3 style="FONT-SIZE: 2em">1.1 Important to know</h3>
				<p>Files are no longer saved within the database, but on the disk instead. PHP will use the database to get the correct name and location of the file, and send the information over to the client, if he/she is allowed to see it. The files are stored with random names, on both subfolder and filenames, just to make brute forcing to find files as hard as possible. But to be on the safe side, I suggest you place the basepath for the attachments <strong>in a place browsers aren't allowed to view the content</strong>. If you don't there's always a <strong>risk</strong> someone might bruteforce themselves to the files, so they can get to protected information.</p>
				<p>There's two parts that use random names, filenames, and subfolder names, the latter is possible to name manually, but it's adviced that you don't, as humans are quite predictable. Like if people are supposed to think of a number between 1 to 10 they often select 7, heh so do use the <strong>button that generate new subfolders</strong> if you have the need to create more subfolders.</p>
				<p></p>
				<h3 style="FONT-SIZE: 2em">1.2 Keeping backups</h3>
				<p>When you create backups on the files you never have to download any files more than once!</p>
				<p>Reason for this is that when an attachment gets deleted, the database entry is removed, and the file is shrunk to 0 bytes, but still left in the folder, so that no other attachment can get the same name. This means that you only have to download the new files when making backups, saving alot of bandwidth and time.</p>
				<p>Database tables and files should be made backups of at about the same time (one before the other), and preferabely in Maintenece mode, so the files and database match up.</p>
				<p></p>
				<h3 style="FONT-SIZE: 2em">1.3 Setting permissions</h3>
				<p>Permissions are stored per forum and per group basis, so to give 8 groups attachment access to 12 forums, you need to set 96 rules. Default is not to allow anything, so you only need to add forumrules to forums that the group will be allowed to up/down-load attachments. To the Moderator groups you can also decide if you want to give them delete possibilities (or, it's intended for moderators, but you can give other the possibility to do it as well, but one would need to get to the edit page to be able to delete it, so I doubt it will work either way)</p>
				<p>The permissions is set through the administration interface, it's not connected to the normal forums/categorys admin interface, but it has it's own interface in the plugin menu.</p>
				<p>I've tried to make the rule settings as easy as possible, so you can get an overview on who is allowed to do what on each forum and simple controls to add and remove rights.</p>
				<p></p>
				<h3 style="FONT-SIZE: 2em">1.4 Adding extra icons</h3>
				<p>It's fairly easy to add icons, now when you no longer need to edit the php file. There's a .psd file with the layered default icons, feel free to use that to create your own icons.</p>
				<p>1. Create the icons on your computer. Png (Portable Network Graphics) is a good format for the icons, it doesn't destroy the images like jpeg does, and it allows the use of transparency.</p>
				<p>2. Upload the icons to the folder where you want them to be (default is <em>forumfolder</em>/img/attach/), just as you did when uploading the forum software. Usually done with FTP.</p>
				<p>3. Open up the browser and go into "Administration", in the plugins menu, select "Attachment Mod 2.0". You now see another menu page, click the "Alter Settings" button.</p>
				<p>4. At "Icons used" there's two fileds, one hold extensions, the other the icon name. Either change what's already there, or add more extensions and icon names. Make sure that the number of extensions is the same as the number of icon names. Everything is separated with double quotation marks ("). As this sign isn't allowed in filenames.</p>
				<p>5. When ready press "Update settings"</p>
				<p>Voila, it should now be using these new icons. A tip is to attach some files with these extensions, so you're sure it's working, spelling errors occur quite often, at least for me, hehehe.</p>
				<p></p>
				<h3 style="FONT-SIZE: 2em">1.5 Making new subfolders</h3>
				<p>When you start to have quite a few files in the subfolder, the uploads might start to take longer, as chanses for collision during name generation might happen, and with many files getting collisions 100 times is probably noticable. If you start to get this upload lag effect it's adviced that you create a new subfolder. Uploads will then try to find unique names in this folder instead, note that the <em>subfolder entry in the settings</em> only affect the coming uploads. The attachments already uploaded will stay in their folders. Therefore, after creating a new subfolder, it's adviced that you make a backup, when you have done that, you no longer need to make a backup of that folder any more, as you already have the files. The database though should still be made backups of in it's full content.</p>
				<p>There's an option to let the Attachment Mod create the folders automatically, or manually enter a subfoldername to use. The <em>best thing</em> is to let the <em>Attachment Mod generate</em> the subfolder, as humans are too predictable. But if you're moving the forum from one host to another you might want to have the same foldername, therefore the option to enter the subfolder name manually is avaible. There's really no reason why one shouldn't make new folders once in a while, but the upload speed will tell you when it's time to create a new subfolder.</p>
				<p>The adminpages might later get a tool to check how long it takes to generate X new unique files. So one can get an idea if it's worth making.</p>
				<p>As one only can have one basefolder, there's probably a limit on like 64000 subfolders, so I guess it'll be possible to at least have a couple of hundred million files before there's a need for making more basefolders, therefore I haven't made any attempts in making it even more 'dynamic' (the modding needed isn't that hard though, so it's something that's possible to fix if someone would have this vast amount of attachments)</p>
				<p>If we assume the filesystem allows max 64000 files or folders, then the filesystem will allow up to 4,096,000,000 files and I feel that there should be some new huuuuuuuuge BB starting to use PunBB before this will be a limit</p>
				<p></p>
				<h3 style="FONT-SIZE: 2em">1.6 Max allowed upload size</h3>
				<p>There's three things that will affect how large files you may allow, everything are PHP settings (the php.ini file).</p>
				<p>The most important PHP setting is called "upload_max_filesize" and is default "2M". This value can either be per directory or per system. While you're in the php.ini file, make sure "file_uploads" is "1".</p>
				<p>To further complicate things, there's two more things in the php.ini file that needs consideration (and that may not be per folder, so it's global). The settings "post_max_size" must be greater than "upload_max_filesize", and "memory_limit" should be larger than "post_max_size". So it's not just to up one value to handle large files.
				<p>So you have figured out what restrictions PHP has, it's time to figure out what you can enter in the Attachment Mod Settings page. You can set the "Max filesize" value to the equivalent amount of bytes that the "upload_max_filesize" has. But make sure there's room for the text message and such, so that there's still some space left between "upload_max_filesize" and "post_max_size". (An example to translate the letter value php use to bytes: 2K = 2 * 1024 = 2048 bytes, 2M = 2 * 1024 * 1024 = 2097152 bytes)</p>
				<p></p>
				<p></p>
				<hr />
				<h3 style="FONT-SIZE: 2.5em">2 Database</h3>
				<p>The Attachment Mod use both it's own tables, as well as use the forum config table for options (these were in the first version of the mod in php files, now you don't have to edit files anymore).</p>
				<p></p>
				<h3 style="FONT-SIZE: 2em">2.1 Attachment Mod tables</h3>
				<p>There's two new tables added to the database, these are attach_2_files and attach_2_rules. The first table stored all file informaiton, like filename, who uploaded it, what post it belongs to and how many downloads it has had. The other table is the table keeping track of the rules. It links group and forum id's together, so you can have the same number of rules as groups for a forum.</p>
				<p></p>
				<h3 style="FONT-SIZE: 1.5em"><strong><em>2.1.1 attach_2_files</em></strong></h3>
				<p>Everything is written in order: Field(type) - What info it holds</p>
				<p>id(int(10)unsigned) - The unique id each attachment has<br />
				owner(int(10)unsigned) - The user id that uploaded the attachment<br />
				post_id(int(10)unsigned) - The post id that the upload belongs to, rules are taken from this<br />
				filename(varchar(255)) - The filename of the attachment, when viewing and downloading<br />
				extension(varchar(64)) - The extension the attachment has, used for icons<br />
				mime(varchar(64)) - The MIME information of the attachment, supplied as a header during download, browsers then handle different files better<br />
				location(text) - The path and filename of the attachment on the filesystem. PHP fetch this to be able to find what file to pass through to the downloader<br />
				size(int(10)unsigned) - The number of bytes the file has.<br />
				downloads(int(10)unsigned) - The number of times the file has been downloaded (increases just before starting to send data)</p>
				<p></p>
				<h3 style="FONT-SIZE: 1.5em"><strong><em>2.1.2 attach_2_rules</em></strong></h3>
				<p>Everything is written in order: Field(type) - What info it holds</p>
				<p>id(int(10)unsigned) - The unique id each rulecombination has<br />
				forum_id(int(10)unsigned) - The affected forum for this ruleset<br />
				group_id(int(10)unsigned) - The affected group for this ruleset<br />
				rules(int(10)unsigned) - The rules in integer form (security check checks if bits are set or not)<br />
				size(int(10)unsigned) - The max amount of bytes the attachment may have<br />
				per_post(tinyint(4)) - If allowed to attach more than one attachment to each post<br />
				file_ext(text) - If non empty these are the only extension that's allowed to upload, separated with double quotations marks ("). If empty only the always denied files will be refused.</p>
				<p></p>
				<h3 style="FONT-SIZE: 2em">2.2 Variables in config table</h3>
				<p>There's a number of variables stored in the config table. In the previous version of the Attachment Mod these were in a php file and one needed to edit that file. This is no longer needed and will probably be at least just as fast. The following variables are stored, and in the form: Variablename - Description</p>
				<p>attach_always_deny - Double quotation mark (") separated array with extensions always denied. Administrator override these, but everyone else cannot upload attachments with these extensions<br />
				attach_basefolder - Folder where the unique subfolders are located<br />
				attach_create_orphans - 1/0 depending on orphans will be created during thread delete or not<br />
				attach_cur_version - The current version of the Mod (might be good later, used on a few places)<br />
				attach_icon_extension - Double quotation mark (") separated array. Holds the extension for the array that figures out what icon to show<br />
				attach_icon_folder - The location the attachment icons are fetched from<br />
				attach_icon_name - Double quotation mark (") separated array. Holds the filename of the icon for the array that figures out what icon to show<br />
				attach_max_size - The 'hard' limit of maximum upload, no one is allowed to upload larger attachments than this, not even Administrator.<br />
				attach_subfolder - The currently used subfolder for new attachments.<br />
				attach_use_icon - 1/0 depending if the Administrator has chosen to allow icons to be displayed by the Attachment Mod or not. If it's displayed, users having images disabled in posts will not see the icon either, but everyone else will.</p>
				<p></p>
				<p></p>
				<hr />
				<h3 style="FONT-SIZE: 2.5em">3 Filesystem</h3>
				<p>Here's an explanation of the files used in the Attachment Mod</p>
				<p>Files in /<br />
				* attachment.php - This file handles the downloads of files, a couple of things is sent as GET variables, they are:<br />
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;item     - The id (database unique id of attachment), required<br />
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;download - if this is set, download of images, not image view optional<br />
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Furthermore it will also support resume, so that people will be able to download large files from modem's)</p>
				<p>Files in /attachments/<br />
				* attach_incl.php - This file is included always when handling attachments! Stores some variables.<br />
				* attach_func.php - This file is included by attach_incl.php, it has the functions used in the mod.</p>
				<p>Files in basefolder and subfolders<br />
				In all the basefolders and subfolders an index.html is stored, that shows and empty page. Also an .htaccess file is saved there (for Apache servers). This is so no one will be able to browse a folder.<br />
				In the subfolders the attachments will be saved, and they have a name with 32 characters and then an extension of ".attach", so a filename could be "ebf9028669710ed078fcd13f00fe5253.attach". As you see this is very hard to guess, but with brute forcing it's defenetly possible to find one, but it's safer than to save it with relation to post/forum/user, that's why it looks like this. Read 1.2 about backups, and you will see a good thing as well.</p>
				<p></p>
				<p></p>
				<hr />
				<h3 style="FONT-SIZE: 2.5em">4 Functions and variables</h3>
				<p></p>
				<h3 style="FONT-SIZE: 2em">4.1 Functions in attach_func.php</h3>
				<p>The attachment mod has a bunch of it's own functions, so their functionality will be explained here.</p>
				<p>* attach_rules(int rules, int check) - This function checks if the supplied rules will allow you to do what you supply in the check field, see constants for what you should supply in the check field. Returns true or false</p>
				<p>* attach_allow_upload(int rules, int max_size, string file_ext, int upload_size, string upload_name) - This function does a rulecheck to see if the user is allowed to upload the file.</p>
				<p>* attach_icon(string extension) - This function renders the text used if user has images toggled on. The image it displays depends on the extension, but if no match it'll display generic icon. Returns empty string or string with icon stuff</p>
				<p>* attach_fix_icon_array() - This function takes the settings for the icons and create an array for attach_icon to find the right icon.</p>
				<p>* attach_generate_pathname(string storagepath) - This function generate the unique pathname for the attachments to stay in. If storagepath isn't equal to an empty string, the pathname is also checked against current pathnames. If there is such a path, a new pathname is generated, until an unique name is found. Storagepath needs to end with a slash (/ or \ depending on OS). Returns string with pathname</p>
				<p>* attach_generate_filename(string storagepath, int messagelenght, int filesize) - This function generates unique filenames for the attachments, storagepath is required, and messagelenght and filesize is adviced to have, so files posted at the same time easier will have different id's Storagepath needs to end with a slash (/ or \ depending on OS). Returns string with complete filename (including pathname)</p>
				<p>* attach_create_attachment(string filename, string mime, int filesize, string tmp_name, int post id, int messagelenght) - This function takes alot of variables (mostly upload variables), and move the file to the correct place and then create a record in the database</p>
				<p>* attach_create_subfolder(string subfolder) - This function creates a new subfolder, copies an .htaccess and index.html in there, and updates the forum config if successful. If the subfolder already excist the function will only update the configuration to use this folder to create attachments. So if you have manually created a directory, make sure you have put an .htaccess and index.html file in there or taken security measures not to let people browse/access the folder directly.</p>
				<p>* attach_create_mime(string extension) - This function generates a mime, it's used if nothing is supplied when file is uploaded. Returns string with mime</p>
				<p>* attach_get_extension(string filename) - This function generates the extension, used for icons and mimes(if missing). Returns empty string or string with part of filename after last point (.)</p>
				<p>* attach_check_extension(string extension, string allowed_extensions) - This function does an extension check, if it's in the 'always deny' list, it's refused, and if the strign for allowed extensions is logner than 0 then it will check against that as well</p>
				<p>* attach_delete_attachment(int item) - This function deletes an attachment (resetting the filesize to 0 bytes, and removes the database record), uses it's own security check for increased security.</p>
				<p>* attach_delete_thread(int thread_id) - This function deletes all attachments in a thread.</p>
				<p></p>
				<h3 style="FONT-SIZE: 2em">4.2 Constants in attach_incl.php</h3>
				<p>The Constants defined by the Attachment Mod is used for the security checks, they are: (in the form NAME (value) - Description)</p>
				<p>ATTACH_DOWNLOAD (1, binary: 0001) - Bit set if group is allowed to download<br />
				ATTACH_UPLOAD (2, binary: 0010) - Bit set it group is allowed to upload<br />
				ATTACH_DELETE (4, binary: 0100) - Bit set if group is allowed to delete<br />
				ATTACH_OWNER_DELETE (8, binary: 1000) - Bit set if owner is allowed to delete</p>
				<p></p>
				<p><a href="javascript: history.go(-1)">Go back</a></p>
			</div>
		</div>
	</div>
<?php
}elseif(isset($_POST['list_attachments'])){
	if(isset($_POST['start']))
		$attach_limit_start = intval($_POST['start']);
	else
		$attach_limit_start = 0;
	if(isset($_POST['number']))
		$attach_limit_number = intval($_POST['number']);
	else
		$attach_limit_number = 50;
	if(isset($_POST['auto_increase']))
		$attach_auto_increase = (intval($_POST['auto_increase'])==1)?$attach_limit_start+$attach_limit_number:$attach_limit_start;
	else
		$attach_auto_increase = $attach_limit_start;
	if(isset($_POST['direction']))
		$attach_result_direction = (intval($_POST['direction'])==1)?'ASC':'DESC';
	else
		$attach_result_direction = 'ASC';
	if(isset($_POST['order']))
		switch (intval($_POST['order'])){
			case 0:
				$attach_result_order = 'id';
				break;
			case 1:
				$attach_result_order = 'downloads';
				break;
			case 2:
				$attach_result_order = 'size';
				break;
			case 3:
				$attach_result_order = 'downloads*size';
				break;
			default:
				$attach_result_order = 'id';
				break;
		}
	else
		$attach_result_order = 'id';

	generate_admin_menu($plugin);	// Display the admin navigation menu
?>
	<div class="blockform">
		<h2><span>Attachment Mod <?php echo $pun_config['attach_cur_version']; ?> - List attachments</span></h2>
		<div class="box">
			<div class="inbox">
				<div class="inform">
					<form name="list_attachments_form" id="example" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
					<fieldset>
						<legend>Search options</legend>
						<div class="infldset">
						<table class="aligntop" cellspacing="0">
							<tr>
								<th scope="row">Start at</th>
								<td>
									<span><input type="text" name="start" size="3" value="<?php echo $attach_auto_increase; ?>" tabindex="1" /> (Auto increase? <input type="radio" name="auto_increase" value="1" tabindex="2" <?php echo ($attach_auto_increase != $attach_limit_start)?'checked="checked" ':''; ?>/><strong>Yes</strong> <input type="radio" name="auto_increase" value="0" tabindex="3" <?php echo ($attach_auto_increase != $attach_limit_start)?'':'checked="checked" '; ?>/><strong>No</strong>)</span>
								</td>
							</tr>
							<tr>
								<th scope="row">Number to show</th>
								<td>
									<span><input type="text" name="number" size="3" value="<?php echo $attach_limit_number; ?>"  tabindex="4" /></span>
								</td>
							</tr>
							<tr>
								<th scope="row">Order</th>
								<td>
									<span><input type="radio" name="order" value="0" tabindex="5" <?php echo ($attach_result_order == 'id')?'checked="checked" ':''; ?>/>ID <input type="radio" name="order" value="1" tabindex="6" <?php echo ($attach_result_order == 'downloads')?'checked="checked" ':''; ?>/>Downloads <input type="radio" name="order" value="2" tabindex="7" <?php echo ($attach_result_order == 'size')?'checked="checked" ':''; ?>/>Size <input type="radio" name="order" value="3" tabindex="8" <?php echo ($attach_result_order == 'downloads*size')?'checked="checked" ':''; ?>/>Total transfer</span>
								</td>
							</tr>
							<tr>
								<th scope="row">Direction</th>
								<td>
									<span><input type="radio" name="direction" value="1" tabindex="9" <?php echo ($attach_result_direction == 'ASC')?'checked="checked" ':''; ?>/>Increasing <input type="radio" name="direction" value="0" tabindex="10" <?php echo ($attach_result_direction == 'DESC')?'checked="checked" ':''; ?>/>Decreasing</span>
								</td>
							</tr>
						</table>
						<div class="fsetsubmit"><input type="submit" name="list_attachments" value="List Attachments" tabindex="11" /></div>
						</div>
					</fieldset>
					</form>
				</div>
			</div>
		</div>
	</div>
	<div class="blockform">
		<h2 class="block2"><span>Attachment list</span></h2>
		<div class="box">
			<div class="fakeform">
<?php

	//search for all attachments ...
	$result = $db->query('SELECT af.id, af.owner, af.post_id, af.filename, af.extension, af.size, af.downloads, u.username FROM '.$db->prefix.'attach_2_files AS af LEFT JOIN '.$db->prefix.'users AS u ON u.id=af.owner ORDER BY '.$attach_result_order.' '.$attach_result_direction.' LIMIT '.$attach_limit_start.','.$attach_limit_number) or error('Unable to fetch attachments',__FILE__,__LINE__,$db->error());
	if ($db->num_rows($result))
	{
		
?>
				<div class="inform">
					<fieldset>
						<legend>Attachments</legend>
						<div class="infldset">
							<table cellspacing="0">
							<thead>
								<tr>
									<th class="tcl">Filename</th>
									<th class="tc2">Post ID</th>
									<th class="tc2">Filesize</th>
									<th class="tc2">Downloads</th>
									<th class="tc3">Total transfer</th>
									<th class="tcr">Actions</th>
								</tr>
							</thead>
							<tbody>
<?php
		while ($cur_attach = $db->fetch_assoc($result))
		{
?>
								<tr>
									<td class="tcl"><?php echo attach_icon($cur_attach['extension']).' <a href="attachment.php?item='.$cur_attach['id'].'">'.pun_htmlspecialchars($cur_attach['filename']) ?></a> by <a href="profile.php?id=<?php echo $cur_attach['owner'] ?>"><?php echo pun_htmlspecialchars($cur_attach['username']) ?></a></td>
									<td class="tc2"><a href="viewtopic.php?pid=<?php echo $cur_attach['post_id'].'#p'.$cur_attach['post_id'] ?>">#<?php echo $cur_attach['post_id'] ?></a></td>
									<td class="tc2"><?php echo format_bytes($cur_attach['size']) ?></td>
									<td class="tc2"><?php echo number_format($cur_attach['downloads']) ?></td>
									<td class="tc3"><?php echo format_bytes($cur_attach['size'] * $cur_attach['downloads']) ?></td>
									<td class="tcr">
										<form name="alter_attachment_id_<?php echo $cur_attach['id'] ?>" id="example" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
											<input type="Submit" name="delete_attachment" value="Delete" /><input type="hidden" name="attachment_id" value="<?php echo $cur_attach['id'] ?>" />
										</form>
									</td>
								</tr>
<?php
		}
		
?>
								</tbody>
							</table>
						</div>
					</fieldset>
				</div>
<?php
	}
?>
			</div>
		</div>
	</div>
<?php	
}elseif(isset($_POST['delete_orphan'])){
	//ok, delte this attachment
	if(attach_delete_attachment(intval($_POST['attachment_id'])))
		message('Orpahn attachment deleted.');
	else
		message('Error when deleting orphan. Orphan attachment not deleted.');

}
elseif (isset($_POST['delete_attachment']))
{
	//ok, delete this attachment
	if (attach_delete_attachment(intval($_POST['attachment_id'])))
		redirect($_SERVER['REQUEST_URI'], 'Attachment deleted.');
	else
		message('Error when deleting attachment. Attachment not deleted.');

}
elseif(isset($_POST['list_orphans']))
{
	//search for all attachments ...
	$result = $db->query('SELECT af.id, af.owner, af.post_id, af.filename, af.extension, af.size, af.downloads, u.username FROM `'.$db->prefix.'attach_2_files` AS af LEFT JOIN `'.$db->prefix.'posts` AS p ON p.id=af.post_id LEFT JOIN '.$db->prefix.'users AS u ON u.id=af.owner WHERE p.id IS NULL') or error('Unable to fetch attachments',__FILE__,__LINE__,$db->error());
	
	if (!$db->num_rows($result))
		message('No orphans found. Yipeee. :)');

	generate_admin_menu($plugin);	// Display the admin navigation menu

?>
	<div class="blockform">
		<h2><span>List orphan attachments</span></h2>
		<div class="box">
			<div class="fakeform">
				<div class="inform">
					<fieldset>
						<legend>Attachments</legend>
						<div class="infldset">
							<table cellspacing="0">
							<thead>
								<tr>
									<th class="tcl">Filename</th>
									<th class="tc2">Post ID</th>
									<th class="tc2">Filesize</th>
									<th class="tc2">Downloads</th>
									<th class="tc3">Total transfer</th>
									<th class="tcr">Actions</th>
								</tr>
							</thead>
							<tbody>
<?php
	while ($cur_attach = $db->fetch_assoc($result))
	{
?>
								<tr>
									<td class="tcl"><?php echo attach_icon($cur_attach['extension']).' <a href="attachment.php?item='.$cur_attach['id'].'">'.pun_htmlspecialchars($cur_attach['filename']) ?></a> by <a href="profile.php?id=<?php echo $cur_attach['owner'] ?>"><?php echo pun_htmlspecialchars($cur_attach['username']) ?></a></td>
									<td class="tc2"><a href="viewtopic.php?pid=<?php echo $cur_attach['post_id'].'#p'.$cur_attach['post_id'] ?>">#<?php echo $cur_attach['post_id'] ?></a></td>
									<td class="tc2"><?php echo format_bytes($cur_attach['size']) ?></td>
									<td class="tc2"><?php echo number_format($cur_attach['downloads']) ?></td>
									<td class="tc3"><?php echo format_bytes($cur_attach['size'] * $cur_attach['downloads']) ?></td>
									<td class="tcr">
										<form name="alter_attachment_id_<?php echo $cur_attach['id'] ?>" id="example" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
											<input type="Submit" name="delete_attachment" value="Delete" /><input type="hidden" name="attachment_id" value="<?php echo $cur_attach['id'] ?>" />
										</form>
									</td>
								</tr>
<?php
	}
		
?>
								</tbody>
							</table>
						</div>
					</fieldset>
				</div>
			</div>
		</div>
	</div>
<?php

}elseif(isset($_POST['delete_orphans'])){
	// search for all orphans, 
	$result_attach = $db->query('SELECT af.id FROM `'.$db->prefix.'attach_2_files` AS af LEFT JOIN `'.$db->prefix.'posts` AS p ON p.id=af.post_id WHERE p.id IS NULL') or error('Unable to search for orphans',__FILE__,__LINE__,$db->error());
	// if there is any orphans start deleting them one by one...
	if($db->num_rows($result_attach)>0){// we have orphan(s)
		$i=0;
		while(list($attach_id)=$db->fetch_row($result_attach)){
			attach_delete_attachment($attach_id);
			$i++;
		}
		message($i.' orphan(s) deleted. Shouldn\'t be any orphans left now');
	}else{// if there aren't any orphans, tell the user that...
		message('No orphans found. Yipeee. :)');
	}
	
}elseif(isset($_POST['edit_rules'])&&isset($_POST['forum'])){

	$attach_output ='';
	$attach_cur_f_id=intval($_POST['forum']);
	
	// first some stuff is things are updated, deleted or created ... after this the normal 'edit_rules' will show ... 
	
	if(isset($_POST['update_ruleset'])){
		// here the update will go ... to update an existing ruleset
		// calculate the rules
		$attach_cur_group_rules = 0;
		$attach_cur_group_rules += (isset($_POST['download']))?ATTACH_DOWNLOAD:0;
		$attach_cur_group_rules += (isset($_POST['upload']))?ATTACH_UPLOAD:0;
		$attach_cur_group_rules += (isset($_POST['owner_delete']))?ATTACH_OWNER_DELETE:0;
		$attach_cur_group_rules += (isset($_POST['delete']))?ATTACH_DELETE:0;
		$attach_cur_group_id = intval($_POST['edit_ruleset']);
		$attach_cur_group_size = ($pun_config['attach_max_size']>intval($_POST['size']))?intval($_POST['size']):$pun_config['attach_max_size'];
		$attach_cur_group_per_post = intval($_POST['per_post']);
		$attach_cur_group_file_ext = $db->escape($_POST['file_ext']);

		if($attach_cur_group_rules != 0)
			$result = $db->query('UPDATE '.$db->prefix.'attach_2_rules SET rules=\''.$attach_cur_group_rules.'\', size=\''.$attach_cur_group_size.'\', per_post=\''.$attach_cur_group_per_post.'\', file_ext=\''.$attach_cur_group_file_ext.'\' WHERE group_id=\''.$attach_cur_group_id.'\' AND forum_id=\''.$attach_cur_f_id.'\' LIMIT 1')or error('Unable to update ruleset for group',__FILE__,__LINE__,$db->error());
		else
			$result = $db->query('DELETE FROM '.$db->prefix.'attach_2_rules WHERE group_id=\''.$attach_cur_group_id.'\' AND forum_id=\''.$attach_cur_f_id.'\' LIMIT 1')or error('Unable to update/delete ruleset for group',__FILE__,__LINE__,$db->error());

	}elseif(isset($_POST['delete_ruleset'])){
		// here the deletes will go ... to delete an existing ruleset
		$attach_cur_group_id = intval($_POST['edit_ruleset']);
		
		$result = $db->query('DELETE FROM '.$db->prefix.'attach_2_rules WHERE group_id=\''.$attach_cur_group_id.'\' AND forum_id=\''.$attach_cur_f_id.'\' LIMIT 1')or error('Unable to delete ruleset for group',__FILE__,__LINE__,$db->error());
		
		
	}elseif(isset($_POST['create_ruleset'])){
		// here the creates will go ... to create a new ruleset
		$attach_cur_group_rules = 0;
		$attach_cur_group_rules += (isset($_POST['download']))?ATTACH_DOWNLOAD:0;
		$attach_cur_group_rules += (isset($_POST['upload']))?ATTACH_UPLOAD:0;
		$attach_cur_group_rules += (isset($_POST['owner_delete']))?ATTACH_OWNER_DELETE:0;
		$attach_cur_group_rules += (isset($_POST['delete']))?ATTACH_DELETE:0;
		$attach_cur_group_id = intval($_POST['newgroup']);
		$attach_cur_group_size = intval($_POST['size']);
		$attach_cur_group_per_post = intval($_POST['per_post']);
		$attach_cur_group_file_ext = $db->escape($_POST['file_ext']);

		if($attach_cur_group_rules != 0)
			$result = $db->query('INSERT INTO '.$db->prefix.'attach_2_rules (group_id, forum_id, rules, size, per_post, file_ext) VALUES (\''.$attach_cur_group_id.'\', \''.$attach_cur_f_id.'\', \''.$attach_cur_group_rules.'\', \''.$attach_cur_group_size.'\', \''.$attach_cur_group_per_post.'\', \''.$attach_cur_group_file_ext.'\')')or error('Unable to create ruleset',__FILE__,__LINE__,$db->error());
		else
			message('You need to allow the group to do anything to add them to the rules for the forum! No new ruleset created.');
		
	}
	elseif(isset($_POST['apply_ruleset']))
	{
		$attach_forums = (isset($_POST['forums'])) ? $_POST['forums'] : array();

		$result2 = $db->query('SELECT group_id, rules, size, per_post, file_ext FROM '.$db->prefix.'attach_2_rules WHERE forum_id='.$attach_cur_f_id.' ORDER BY group_id')or error('Unable to fetch rights for users in forum',__FILE__,__LINE__,$db->error());
		$attach_rules = array();
		while ($cur_rule = $db->fetch_assoc($result2))
			$attach_rules[] = $cur_rule;

		foreach ($attach_forums as $forum_id)
		{
			$result = $db->query('DELETE FROM '.$db->prefix.'attach_2_rules WHERE forum_id=\''.$forum_id.'\'')or error('Unable to delete ruleset for forum',__FILE__,__LINE__,$db->error());
			
			foreach ($attach_rules as $cur_rule)
				$result = $db->query('INSERT INTO '.$db->prefix.'attach_2_rules (group_id, forum_id, rules, size, per_post, file_ext) VALUES (\''.$cur_rule['group_id'].'\', \''.$forum_id.'\', \''.$cur_rule['rules'].'\', \''.$cur_rule['size'].'\', \''.$cur_rule['per_post'].'\', \''.$cur_rule['file_ext'].'\')') or error('Unable to create ruleset',__FILE__,__LINE__,$db->error());
		}
		redirect($_SERVER['REQUEST_URI'], 'Ruleset applied. Redirecting...');
	}
	// and now back to the normal 'edit rules'
	
	
	$attach_output ='';
	$attach_cur_f_id=intval($_POST['forum']);
	
	// generate an array with groupid => groupname (used for matching existing rules, but also for creating new ones...)
	$attach_grouparray = array();
	$result = $db->query('SELECT g_id, g_title FROM '.$db->prefix.'groups WHERE 1 ORDER BY g_id ASC')or error('Unable to fetch usergroups',__FILE__,__LINE__,$db->error());
	if($db->num_rows($result)!=0){
		while(list($key,$value) = $db->fetch_row($result)){
			$attach_grouparray[$key]=$value;	
		}
	}
	
	// fetch all the info of this forum
	$result = $db->query('SELECT forum_name FROM '.$db->prefix.'forums WHERE id='.$attach_cur_f_id.' LIMIT 1')or error('Unable to fetch forum',__FILE__,__LINE__,$db->error());
	if($db->num_rows($result)==0)
		error('No such forum found');
	list($attach_cur_f_name) = $db->fetch_row($result);
	
	// fetch all existing rules
	$attach_rightsarray = array();
	$attach_sizearray = array();
	$attach_per_postarray = array();
	$attach_file_extarray = array();
	$result_two = $db->query('SELECT group_id, rules, size, per_post, file_ext FROM '.$db->prefix.'attach_2_rules WHERE forum_id='.$attach_cur_f_id.' ORDER BY group_id')or error('Unable to fetch rights for users in forum',__FILE__,__LINE__,$db->error());
	if($db->num_rows($result_two)!=0){
		while(list($attach_cur_group_id,$attach_cur_group_rules,$attach_cur_group_size,$attach_cur_group_per_post,$attach_cur_group_file_ext) = $db->fetch_row($result_two)){
			$attach_rightsarray[$attach_cur_group_id] = $attach_cur_group_rules;
			$attach_sizearray[$attach_cur_group_id] = $attach_cur_group_size;
			$attach_per_postarray[$attach_cur_group_id] = $attach_cur_group_per_post;
			$attach_file_extarray[$attach_cur_group_id] = $attach_cur_group_file_ext;
		}
	}
	// create output for the existing ones
	if(count($attach_rightsarray)!=0){
		$attach_output .= '
		<h2 class="block2"><span>Existing rules for forum: '.$attach_cur_f_name.'</span></h2>
		<div class="box">
			<div id="example"> 
				<div class="inform">
';
		foreach ($attach_rightsarray as $key => $value){
			$attach_cur_group_rules  ='<input type="checkbox" name="download" value="1" ';
			$attach_cur_group_rules .= (attach_rules($value,ATTACH_DOWNLOAD))?'checked="checked" ':'';
			$attach_cur_group_rules .='/>Download <input type="checkbox" name="upload" value="1" ';
			$attach_cur_group_rules .= (attach_rules($value,ATTACH_UPLOAD))?'checked="checked" ':''; 
			$attach_cur_group_rules .='/>Upload <input type="checkbox" name="owner_delete" value="1" ';
			$attach_cur_group_rules .= (attach_rules($value,ATTACH_OWNER_DELETE))?'checked="checked" ':''; 
			$attach_cur_group_rules .='/>Owner Delete <input type="checkbox" name="delete" value="1" ';
			$attach_cur_group_rules .= (attach_rules($value,ATTACH_DELETE))?'checked="checked" ':''; 
			$attach_cur_group_rules .='/>Delete';
			
			$attach_output .= '
					<form id="example'.$key.'" name="example'.$key.'" method="post" action="'.$_SERVER['REQUEST_URI'].'">
						<fieldset>
							<legend>Group: ';
			$attach_output .= (array_key_exists($key,$attach_grouparray))? $attach_grouparray[$key]:'(<strong>'.$key.'</strong> Best is to delete this ruleset, no group has it!!!!)';
			$attach_output .= '</legend>
							<div class="infldset">
								<table class="aligntop" cellspacing="0">
									<tr>
										<th scope="row">Allow</th>
										<td>
											<span>'.$attach_cur_group_rules.'</span>
										</td>
									</tr>
									<tr>
										<th scope="row">Max Upload</th>
										<td>
											<span><input type="text" name="size" value="'.$attach_sizearray[$key].'" />bytes</span>
										</td>
									</tr>
									<tr>
										<th scope="row">Attachments per post</th>
										<td>
											<input type="text" name="per_post" value="'.$attach_per_postarray[$key].'" />
											<span>Here you can increase the allowed number of attachments per post. (To add more attachments to a post the user needs to edit the message.)</span>
										</td>
									</tr>
									<tr>
										<th scope="row">Allowed files</th>
										<td>
											<input type="text" name="file_ext" value="'.$attach_file_extarray[$key].'" size="80" />
											<span>If empty, allow all files except those to always deny.</span>
										</td>
									</tr>
								</table>
								<input type="hidden" name="forum" value="'.$attach_cur_f_id.'" />
								<input type="hidden" name="edit_rules" value="'.$attach_cur_f_id.'" />
								<input type="hidden" name="edit_ruleset" value="'.$key.'" />
								<div class="fsetsubmit"><input type="submit" name="update_ruleset" value="Update this ruleset" /> or 
								<input type="submit" name="delete_ruleset" value="Delete this ruleset" /></div>
							</div>
						</fieldset>
					</form>
';
		}
		$attach_output .= '
				</div>
			</div>
		</div>';
	}
	// create output for creating a new one
	
	if(count($attach_grouparray)>0){
		// generate the select statement
		$attach_group_select = '
											<select id="newgroup" name="newgroup">
';
		foreach($attach_grouparray as $key => $value){
			if($key!=1)$attach_group_select .= '												<option value="'.$key.'">'.$value.'</OPTION>
';
		}
		$attach_group_select .= '
											</select>
';
		// generate the whole baddabang ... 
		$attach_output .= '
		
		<h2 class="block2"><span>Create new ruleset for forum: '.$attach_cur_f_name.'</span></h2>
		<div class="box">
			<div id="example"> 
				<div class="inform">
					<form id="createnew" name="createnew" method="post" action="'.$_SERVER['REQUEST_URI'].'">
						<fieldset>
							<legend>Create new ruleset</legend>
							<div class="infldset">
								<table class="aligntop" cellspacing="0">
									<tr>
										<th scope="row">Group</th>
										<td>'.$attach_group_select.'</td>
									</tr>
									<tr>
										<th scope="row">Allow</th>
										<td>
											<span><input type="checkbox" name="download" value="1" />Download 
											<input type="checkbox" name="upload" value="1" />Upload 
											<input type="checkbox" name="owner_delete" value="1" />Owner Delete 
											<input type="checkbox" name="delete" value="1" />Delete</span>
										</td>
									</tr>
									<tr>
										<th scope="row">Max Upload</th>
										<td>
											<span><input type="text" name="size" value="100000" />bytes</span>
										</td>
									</tr>
									<tr>
										<th scope="row">Attachments per post</th>
										<td>
											<input type="text" name="per_post" value="1" />
											<span>Here you can increase the allowed number of attachments per post. (To add more attachments to a post the user needs to edit the message.)</span>
										</td>
									</tr>
									<tr>
										<th scope="row">Allowed files</th>
										<td>
											<input type="text" name="file_ext" value="" size="80" />
											<span>If empty, allow all files except those to always deny.</span>
										</td>
									</tr>
								</table>
								<input type="hidden" name="forum" value="'.$attach_cur_f_id.'" />
								<input type="hidden" name="edit_rules" value="'.$attach_cur_f_id.'" />
								<div class="fsetsubmit"><input type="submit" name="create_ruleset" value="Create new ruleset" /></div>
							</div>
						</fieldset>
					</form>
				</div>
			</div>
		</div>';
		
		$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id, f.forum_name, f.redirect_url FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id WHERE f.id<>'.$attach_cur_f_id.' ORDER BY c.disp_position, c.id, f.disp_position') or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

		if ($db->num_rows($result) > 0)
		{
			$attach_output .= '
		<h2 class="block2"><span>Apply forum ruleset: '.$attach_cur_f_name.'</span></h2>
		<div class="box">
			<div id="example"> 
				<div class="inform">
					<form id="createnew" name="createnew" method="post" action="'.$_SERVER['REQUEST_URI'].'">
						<fieldset>
							<legend>Apply above ruleset for other forums</legend>
							<div class="infldset">
								<table class="aligntop" cellspacing="0">
									<tr>
										<th scope="row">Forum list</th>
										<td>
											<select multiple="multiple" name="forums[]" size="6">';
									

			$cur_category = 0;
			while ($forum_list = $db->fetch_assoc($result))
			{
				if ($forum_list['cid'] != $cur_category) // A new category since last iteration?
				{
					if ($cur_category)
						$attach_output .= "\t\t\t\t\t\t".'</optgroup>'."\n";

					$attach_output .= "\t\t\t\t\t\t".'<optgroup label="'.pun_htmlspecialchars($forum_list['cat_name']).'">'."\n";
					$cur_category = $forum_list['cid'];
				}
			
				$attach_output .= "\t\t\t\t\t\t\t".'<option value="'.$forum_list['id'].'">'.pun_htmlspecialchars($forum_list['forum_name']).'</option>'."\n";
			}
			$attach_output .= '												</optgroup>
											</select>
										</td>
									</tr>
								</table>
								<input type="hidden" name="forum" value="'.$attach_cur_f_id.'" />
								<input type="hidden" name="edit_rules" value="'.$attach_cur_f_id.'" />
								<div class="fsetsubmit"><input type="submit" name="apply_ruleset" value="Apply ruleset" /></div>
							</div>
						</fieldset>
					</form>
				</div>
			</div>
		</div>
			';
		}
		
		
	}
	
	
	
	
	// output the shit
	generate_admin_menu($plugin);	// Display the admin navigation menu
?>
		<div class="plugin blockform">
		<h2><span>Attachment Mod <?php echo $pun_config['attach_cur_version']; ?> - Edit rules</span></h2>
		<div class="box">
			<div class="inbox">
				<p>You alter the rules per group basis, for each forum. Download, means that people in that group are allowed to download all attachments in that forum. Upload, means that the group is allowed to attach files to their posts. Owner delete, means that the owner of the file is allowed to delete the file from the post. Delete, means that the group will be allowed to delete all files (usefull for Moderators, but no one else)</p>
				<p>You can also set a max size per group, and a list of allowed file extensions. The max size cannot be set larger than the hard limit set in the settings (if it is, it'll get that value instead). The allowed file extensions is where you can limit what people is allowed to upload, if it's left empty the Attachment Mod will allow all files except those specified in the Mod Settings to always deny.</p>
			</div>
		</div>

		<?php echo $attach_output; ?>
		
	</div>
<?php	
	
	
	
	
	
}elseif(isset($_POST['list_rules'])){

	$attach_output ='';

	// generate an array with groupid => groupname, used when figuring out what the group is called ...
	$attach_grouparray = array();
	$attach_rightsarray = array();
	$result = $db->query('SELECT g_id, g_title FROM '.$db->prefix.'groups WHERE 1 ORDER BY g_id ASC')or error('Unable to fetch usergroups',__FILE__,__LINE__,$db->error());
	if($db->num_rows($result)!=0){
		while(list($key,$value) = $db->fetch_row($result)){
			$attach_grouparray[$key]=$value;	
		}
	}
	
	// select all the categorys and forums ...
	$result = $db->query('SELECT c.cat_name, f.id, f.forum_name FROM '.$db->prefix.'categories AS c, '.$db->prefix.'forums AS f WHERE c.id=f.cat_id ORDER BY c.disp_position, f.disp_position')or error('Unable to fetch categorys and forums',__FILE__,__LINE__,$db->error());
	if($db->num_rows($result)!=0){
		$attach_prev_cat_name = '';
		while(list($attach_cur_cat_name,$attach_cur_f_id,$attach_cur_f_name) = $db->fetch_row($result)){
			// if the category name has changed, and the last one isn't '' end the category and start a new, othervise just start one
			if($attach_cur_cat_name!=$attach_prev_cat_name){
				if($attach_prev_cat_name!=''){	// close the last one ...
					$attach_output .= '
				</div>
			</div>
		</div>	


';
				}
				//start new category
				$attach_output .= '
		<h2 class="block2"><span>Category: '.$attach_cur_cat_name.'</span></h2>
		<div class="box">
			<div id="example"> 
				<div class="inform">';
			}
			$attach_prev_cat_name = $attach_cur_cat_name;

			
			// empty the strings ...
			$attach_cur_forum_download = '';
			$attach_cur_forum_upload = '';
			$attach_cur_forum_delete = '';
			$attach_cur_forum_ownerdelete = '';
			unset($attach_rightsarray);			
			$attach_rightsarray = array();
			// select all the groups that has rights set in this forum...
			$result_two = $db->query('SELECT group_id, rules FROM '.$db->prefix.'attach_2_rules WHERE forum_id='.$attach_cur_f_id.' ORDER BY group_id')or error('Unable to fetch rights for users in forum',__FILE__,__LINE__,$db->error());
			if($db->num_rows($result_two)!=0){
				// clean up the array ... so we have an empty array to start with
				
				while(list($attach_cur_group_id,$attach_cur_group_rules) = $db->fetch_row($result_two)){
					$attach_rightsarray[$attach_cur_group_id] = $attach_cur_group_rules;
				}
				
				// check what they may access ...
				foreach ($attach_rightsarray as $key => $value)
				{
					if (attach_rules($value, ATTACH_DOWNLOAD))
						$attach_cur_forum_download .= array_key_exists($key,$attach_grouparray) ? ', '.$attach_grouparray[$key] : ', (<strong>'.$key.'</strong>)';
					if (attach_rules($value, ATTACH_UPLOAD))
						$attach_cur_forum_upload .= array_key_exists($key,$attach_grouparray) ? ', '.$attach_grouparray[$key] : ', (<strong>'.$key.'</strong>)';
					if (attach_rules($value, ATTACH_DELETE))
						$attach_cur_forum_delete .= array_key_exists($key,$attach_grouparray) ? ', '.$attach_grouparray[$key] : ', (<strong>'.$key.'</strong>)';
					if (attach_rules($value, ATTACH_OWNER_DELETE))
						$attach_cur_forum_ownerdelete .= array_key_exists($key,$attach_grouparray) ? ', '.$attach_grouparray[$key] : ', (<strong>'.$key.'</strong>)';
				}
			}
			// output the forum stuff...
			$attach_output .= '
					<form id="example2" method="post" action="'.$_SERVER['REQUEST_URI'].'">
						<fieldset>
							<legend>Forum: '.$attach_cur_f_name.'</legend>
							<div class="infldset">
								<table class="aligntop" cellspacing="0">
									<tr>
										<th scope="row">Download</th>
										<td>
											<span>'.ltrim($attach_cur_forum_download,', ').'</span>
										</td>
									</tr>
									<tr>
										<th scope="row">Upload</th>
										<td>
											<span>'.ltrim($attach_cur_forum_upload,', ').'</span>
										</td>
									</tr>
									<tr>
										<th scope="row">Owner delete</th>
										<td>
											<span>'.ltrim($attach_cur_forum_ownerdelete,', ').'</span>
										</td>
									</tr>
									<tr>
										<th scope="row">Delete</th>
										<td>
											<span>'.ltrim($attach_cur_forum_delete,', ').'</span>
										</td>
									</tr>
								</table>
								<input type="hidden" name="forum" value="'.$attach_cur_f_id.'" /><div class="fsetsubmit"><input type="submit" name="edit_rules" value="Edit rules for this forum" /></div>
							</div>
						</fieldset>
					</form>
';
		}
		// close the last category
		$attach_output .= '
				</div>
			</div>
		</div>	
	</div>


';
	}

	// well ... generate the page :D
	
	generate_admin_menu($plugin);	// Display the admin navigation menu

?>
		<div class="plugin blockform">
		<h2><span>Attachment Mod <?php echo $pun_config['attach_cur_version']; ?> - Administration Rules</span></h2>
		<div class="box">
			<div class="inbox">
				<p>This is where you select what rules should be applied on different groups on different forums.</p>
				<p>If a group isn't listed, they aren't allowed to do stuff.(Except Administrators that always may post)</p>
			</div>
		</div>
	
<?php	
	echo $attach_output;

	
}elseif(isset($_POST['optimize_tables'])){
	$result = $db->query('OPTIMIZE TABLE `'.$db->prefix.'attach_2_files`')or error('Unable to optimize table: attach_2_files',__FILE__,__LINE__,$db->error());
	$result = $db->query('OPTIMIZE TABLE `'.$db->prefix.'attach_2_rules`')or error('Unable to optimize table: attach_2_rules',__FILE__,__LINE__,$db->error());
	redirect($_SERVER['REQUEST_URI'], 'Attachment Mod '.$pun_config['attach_cur_version'].', Tables Optimized &hellip;');
	
}elseif(isset($_POST['update_settings'])){
	// rewrite stuff from POST variables
	$form['use_icon'] = intval($_POST['use_icon']);
	$form['icon_folder'] = $_POST['icon_folder']; //later strip out all < > | ? * " from the string, to try to up the safety
	$form['icon_extension'] = $_POST['icon_extension']; //later strip out all \ / < > | ? *  from the string, to try to up the safety
	$form['icon_name'] = $_POST['icon_name']; //later strip out all \ / < > | ? *  from the string, to try to up the safety
	$form['create_orphans'] = intval($_POST['create_orphans']);
	$form['always_deny'] = $_POST['always_deny']; //later strip out all \ / < > | ? *  from the string, to try to up the safety
	$form['max_size'] = intval($_POST['max_size']);
	$form['basefolder'] = $_POST['basefolder']; //later strip out all < > | ? * " from the string, to try to up the safety
	
	//insert it into the database
	//taken most from admin_options.php, small changes to cope with the attachment mod instead of forum options...
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

	// Regenerate the config cache
	require_once PUN_ROOT.'include/cache.php';
	generate_config_cache();
// end of stuff taken from admin_options
	redirect($_SERVER['REQUEST_URI'], 'Attachment Mod '.$pun_config['attach_cur_version'].' settings updated. Redirecting &hellip;');


}elseif(isset($_POST['generate_subfolder'])||isset($_POST['change_subfolder'])){
	// if the latter, we should use that instead for new folder
	if(isset($_POST['change_subfolder']))	// we want to use the entered subfolder
		$newname = $_POST['subfolder'];		// fiddle with security later... i.e. only allow 0-9 + a-z
	else 
		$newname = attach_generate_pathname($pun_config['attach_basefolder']);	// ok, we doesn't need to use a folder that has been created beforehand ...
		
	if(!attach_create_subfolder($newname))
		error('Unable to create new subfolder with name '.$newname,__FILE__,__LINE__);
	else
		redirect($_SERVER['REQUEST_URI'], 'Attachment Mod '.$pun_config['attach_cur_version'].' new subfolder created. Redirecting &hellip;');

		
}elseif(isset($_POST['alter_settings'])||isset($_GET['alter_settings'])){
	// Display the admin navigation menu
	generate_admin_menu($plugin);
?>
	<div class="plugin blockform">
		<h2><span>Attachment Mod <?php echo $pun_config['attach_cur_version']; ?> - Alter Settings</span></h2>
		<div class="box">
			<div class="inbox">
				<p>From this page you can more or less alter everything how the mod will behave. Please consult the documentation before changing the values here, as some changes might get undesired results.</p>
			</div>
		</div>
		
		<h2 class="block2"><span>Settings</span></h2>
		<div class="box">
			<form id="example" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
				<p class="submittop"><input type="submit" name="update_settings" value="Update settings" tabindex="1" /></p>
				<div class="inform">
					<fieldset>
						<legend>Attachment Icons</legend>
						<div class="infldset">
						<table class="aligntop" cellspacing="0">
							<tr>
								<th scope="row">Use icons</th>
								<td>
									<strong><input type="radio" name="use_icon" value="1" <?php if($pun_config['attach_use_icon']==1)echo 'checked="checked" '; ?>tabindex="2" />Yes <input type="radio" name="use_icon" value="0" <?php if($pun_config['attach_use_icon']==0)echo 'checked="checked" '; ?>tabindex="3" />No</strong>
									<span>If you want to globally disable the use of icons for the attachments. If it's set to No, no one will see icons, even if the users have selected to show images. Icons are not showed to people that have selected not to use images in posts.</span>
								</td>
							</tr>
							<tr>
								<th scope="row">Icon folder<div></div></th>
								<td>
									<input type="text" name="icon_folder" value="<?php echo $pun_config['attach_icon_folder']; ?>" tabindex="4" size="50" />
									<span>Set this to where the attachment mod stores the icons. The pathname should end with a slash.</span>
								</td>
							</tr>
							<tr>
								<th scope="row">Icons used<div></div></th>
								<td>
									<input type="text" name="icon_extension" value="<?php echo htmlspecialchars($pun_config['attach_icon_extension']); ?>" tabindex="5" size="50" />File extension<br />
									<input type="text" name="icon_name" value="<?php echo htmlspecialchars($pun_config['attach_icon_name']); ?>" tabindex="6" size="50" />Icon name
									<span>These arrays hold what file extension get what icon, the items are separated with double quotation marks (").</span>
								</td>
							</tr>
						</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend>Attachment Limitations and Storage</legend>
						<div class="infldset">
						<table class="aligntop" cellspacing="0">
							<tr>
								<th scope="row">Create orphans</th>
								<td>
									<strong><input type="radio" name="create_orphans" value="1" <?php if($pun_config['attach_create_orphans']=='1')echo 'checked="checked" '; ?>tabindex="7" />Yes <input type="radio" name="create_orphans" value="0" <?php if($pun_config['attach_create_orphans']=='0')echo 'checked="checked" '; ?>tabindex="8" />No</strong>
									<span>If you select to create orphans, then when a thread is deleted no attachments will get deleted. They will instead become orphans, with a possibility to transfer the attachments to new posts. This is done like this as if a person decides to delete his post, it might be ok to loose a couple of attachments, but if a whole thread gets deleted this would mean that people could have important attachments dissapear. I recommend this to be left on Yes (and then it will be faster to delete threads as well)</span>
								</td>
							</tr>
							<tr>
								<th scope="row">Always deny<div></div></th>
								<td>
									<input type="text" name="always_deny" value="<?php echo htmlspecialchars($pun_config['attach_always_deny']); ?>" tabindex="9" size="50" />
									<span>Files with these extensions will always be denied when people try to upload them, except for Administrators, who can override it. Separate items with double quotation marks (")</span>
								</td>
							</tr>
							<tr>
								<th scope="row">Max filesize<div></div></th>
								<td>
									<input type="text" name="max_size" value="<?php echo htmlspecialchars($pun_config['attach_max_size']); ?>" tabindex="10" size="10" />bytes
									<span>This is the 'hard' limit for the maximum allowed upload size, not even Administrators can override this. Read documentation to know how big you can allow this to be.</span>
								</td>
							</tr>
							<tr>
								<th scope="row">Attachments basefolder<div></div></th>
								<td>
									<input type="text" name="basefolder" value="<?php echo htmlspecialchars($pun_config['attach_basefolder']); ?>" tabindex="11" size="50" />
									<span>This is where the Mod will save the files, make sure PHP is allowed to create directories here, as the mod won't work if it isn't allowed to write to this folder. It's extremely important that users cannot browse this folder, as they could get hold of secure items if that would be the case.</span>
								</td>
							</tr>
						</table>
						</div>
					</fieldset>
				</div>
				<p class="submitend"><input type="submit" name="update_settings" value="Update settings" tabindex="12" /></p>
			</form>
		</div>

		
		<h2 class="block2"><span>Subfolders</span></h2>
		<div class="box">
			<form id="example" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
				<div class="inform">
					<fieldset>
						<legend>Subfolders</legend>
						<div class="infldset">
						<table class="aligntop" cellspacing="0">
							<tr>
								<th scope="row">Current subfolder<div></div></th>
								<td>
									<input type="text" name="subfolder" value="<?php echo htmlspecialchars($pun_config['attach_subfolder']); ?>" tabindex="13" size="40" maxlength="32" />
									<span>In this subfolder the Attachment Mod will save the files. Make sure you <strong>read the documentation <u>before</u></strong> changing this. <em>I suggest you use the generate button to generate new unique names</em> instead of writing your own. (This is simply because humans usually don't make that difficult names, humans are quite predictable)</span>
									<span><div class="fsetsubmit"><input type="submit" name="generate_subfolder" value="Generate new subfolder" tabindex="14" /> or <input type="submit" name="change_subfolder" value="Change subfolder" tabindex="15" /></div></span>
								</td>
							</tr>
						</table>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
	</div>
<?php 
}else{		// Nothing has been asked for, design the 'main page'

	// calculate some statistics
	$result = $db->query('SELECT COUNT(id) FROM '.$db->prefix.'attach_2_files WHERE 1')or error('Unable to count number of attachment files',__FILE__,__LINE__,$db->error());
	if($db->num_rows($result)!=0){
		list($attach_number_of_rows) = $db->fetch_row($result);
		if($attach_number_of_rows!=0){
			$attach_output = "Number of attachments: $attach_number_of_rows<br />\n						";
			// figure out the disk usage, taken from the mysql tables ...
			$result = $db->query('SELECT SUM(size),SUM(downloads),SUM(downloads*size) FROM `'.$db->prefix.'attach_2_files` WHERE 1')or error('Unable to summarize disk usage',__FILE__,__LINE__,$db->error());
			if($db->num_rows($result)!=0){
				list($attach_size,$attach_downloads,$attach_total_transfer) = $db->fetch_row($result);
				$attach_output .= 'Used diskspace: '.format_bytes($attach_size)."<br />\n						";
				$attach_output .= 'Total downloads: '.number_format($attach_downloads)." downloads<br />\n						";
				$attach_output .= 'Total transfer: '.format_bytes($attach_total_transfer)." transferred<br />\n						";
			}

			// select the most downloaded file
			$result = $db->query('SELECT id, owner, filename, size, downloads FROM '.$db->prefix.'attach_2_files WHERE 1 ORDER BY downloads DESC LIMIT 1')or error('Unable to fetch most downloaded attachment',__FILE__,__LINE__,$db->error());
			if($db->num_rows($result)!=0){
				list($attach_most_id,$attach_most_owner_id,$attach_most_filename,$attach_most_size,$attach_most_downloads) = $db->fetch_row($result);
				$result = $db->query('SELECT username FROM '.$db->prefix.'users WHERE id = '.$attach_most_owner_id.' LIMIT 1')or error('Unable to fetch name on user with most downloaded attachment',__FILE__,__LINE__,$db->error());
				if($db->num_rows($result)==1)
				list($attach_most_owner) = $db->fetch_row($result);
				else
				$attach_most_owner = 'Now a guest';
				$attach_output .= 'Most downloaded: '.number_format($attach_most_downloads).' downloads - <a href="attachment.php?item='.$attach_most_id.'">'.pun_htmlspecialchars($attach_most_filename).'</a> ('.format_bytes($attach_most_size).') posted by <a href="profile.php?section=admin&amp;id='.$attach_most_owner_id.'">'.pun_htmlspecialchars($attach_most_owner).'</a>';
			}else
			$attach_output .= 'Most downloaded: none';

			// select the attachment with largest total size (size*downloads)
			$result = $db->query('SELECT id, owner, filename, size, downloads FROM '.$db->prefix.'attach_2_files WHERE 1 ORDER BY downloads*size DESC LIMIT 1')or error('Unable to fetch downloaded attachment with most transfersize',__FILE__,__LINE__,$db->error());
			if($db->num_rows($result)!=0){
				list($attach_most_id,$attach_most_owner_id,$attach_most_filename,$attach_most_size,$attach_most_downloads) = $db->fetch_row($result);
				$result = $db->query('SELECT username FROM '.$db->prefix.'users WHERE id = '.$attach_most_owner_id.' LIMIT 1')or error('Unable to fetch name on user with largest total downloaded attachment',__FILE__,__LINE__,$db->error());
				if($db->num_rows($result)==1)
				list($attach_most_owner) = $db->fetch_row($result);
				else
				$attach_most_owner = 'Now a guest';
				$attach_output .= "<br />\n".'Largest total download: '.format_bytes($attach_most_downloads*$attach_most_size).' - <a href="attachment.php?item='.$attach_most_id.'">'.pun_htmlspecialchars($attach_most_filename).'</a> ('.format_bytes($attach_most_size).', '.number_format($attach_most_downloads).' downloads) posted by <a href="profile.php?section=admin&amp;id='.$attach_most_owner_id.'">'.pun_htmlspecialchars($attach_most_owner).'</a>';
			}else
			$attach_output .= "<br />\n".'Largest total download: none';
		}else
			$attach_output = 'No attachments<br>&nbsp;<br>&nbsp;'; // Ugly hack due to pageformatting goes funny without those extra rows...
	}



	// Display the admin navigation menu
	generate_admin_menu($plugin);
?>
	<div class="plugin blockform">
		<h2><span>Attachment Mod <?php echo $pun_config['attach_cur_version']; ?> - Administration Menu</span></h2>
		<div class="box">
			<div class="inbox">
				<p>From here you can set the settings for the attachment mod. But also perform maintenance tasks (note that some are recommended to be done in Maintenance mode!)</p>
				<p>Choose from the menu below what you want to do.</p>
			</div>
		</div>

		<h2 class="block2"><span>Menu</span></h2>
		<div class="box">
			<form id="example" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
				<div class="inform">
					<fieldset>
						<legend>Basic functions (no need for maintenance mode)</legend>
						<div class="infldset">
						<table class="aligntop" cellspacing="0">
							<tr>
								<th scope="row"><div><input type="submit" name="read_documentation" value="Read Documentation" tabindex="2" /></div></th>
								<td>
									<span>Read the documentation of this mod, how it works, what makes it work, security tips, usage guidelines etc.</span>
								</td>
							</tr>
							<tr>
								<th scope="row"><div><input type="submit" name="list_attachments" value="List Attachments" tabindex="3" /></div></th>
								<td>
									<span>Here you can see information for all attachments, and remove unwanted without going to the post.</span>
								</td>
							</tr>
							<tr>
								<th scope="row"><div><input type="submit" name="list_orphans" value="List Orphans" tabindex="4" /></div></th>
								<td>
									<span>When a complete thread gets deleted, the attachments won't be deleted, instead they turn up as orphans (has no parents, i.e. no post is the owner of this attachment). The reason why this is made is because loosing 1-10 attachments might not be that bad, but loosing hundreds might be.</span>
								</td>
							</tr>
							<tr>
								<th scope="row"><div><input type="submit" name="delete_orphans" value="Delete Orphans" tabindex="5" /></div></th>
								<td>
									<span>This will delete <strong>all</strong> orphan attachments.</span>
								</td>
							</tr>
							<tr>
								<th scope="row"><div><input type="submit" name="list_rules" value="List Rules" tabindex="6" /></div></th>
								<td>
									<span>In here you can see what permissions you have set for different groups, and you can assign groups access to attach files to their posts or change already existing rules. All settings are per group and per forum basis. No rules = no permissions.</span>
								</td>
							</tr>
						</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend>Advanced functions (You should probably be in maintenance mode)</legend>
						<div class="infldset">
						<table class="aligntop" cellspacing="0">
							<tr>
								<th scope="row"><div><input type="submit" name="optimize_tables" value="Optimize Attachments" tabindex="7" /></div></th>
								<td>
									<span>If alot of files have been removed, or lots of rules been removed, there are probably alot of overhead in the database, optimize removed unused space in the database tables.</span>
								</td>
							</tr>
							<tr>
								<th scope="row"><div><input type="submit" name="alter_settings" value="Alter Settings" tabindex="8" /></div></th>
								<td>
									<span>Here you can change almost all settings how the mod behaves, from adding new icons, to creating a new directory to store new attachments in.</span>
								</td>
							</tr>
						</table>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
		
		<h2 class="block2"><span>Statistics</span></h2>
		<div id="adstats" class="box">
			<div class="inbox">
				<dl>
					<dt>Mod Version</dt>
					<dd>Attachment Mod <?php echo $pun_config['attach_cur_version']; ?><br />
						&copy; Copyright 2003, 2004, 2005 Frank Hagstr&ouml;m
					</dd>
					<dt>Attachments<br />(According to files currently in database)</dt>
					<dd>
						<?php echo $attach_output;?>
					</dd>
				</dl>
			</div>
		</div>
	</div>
<?php
}


// Note that the script just ends here. The footer will be included by admin_loader.php.