<?php

/**
 * Copyright (C) 2008-2010 Jojaba
 * see CREDITS file to learn more about this page
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// Tell admin_loader.php that this is indeed a plugin and that it is loaded
define('PUN_PLUGIN_LOADED', 1);

/* ******************************** */
/* Core of the EZBBC Toolbar Plugin */
/* ******************************** */

// Language file load
$ezbbc_language_folder = file_exists(PUN_ROOT.'plugins/ezbbc/lang/'.$admin_language.'/ezbbc_plugin.php') ? $admin_language : 'English';
require PUN_ROOT.'plugins/ezbbc/lang/'.$ezbbc_language_folder.'/ezbbc_plugin.php';

// Getting the config data
$plugin_version = "1.3.1";
$config_content = trim(file_get_contents(PUN_ROOT.'plugins/ezbbc/config.php'));
$config_item = explode(";", $config_content);
$ezbbc_install = $config_item[0];
$ezbbc_status = $config_item[1];
$ezbbc_style_folder = $config_item[2];
$ezbbc_smilies_set = $config_item[3];


if ($ezbbc_install != 0) {
        $first_install = false;
        $ezbbc_install_date = date($lang_ezbbc['Date format'], $config_item[0]);
}
else {
        $first_install = true;
}
if ($ezbbc_status == 0) {
        $ezbbc_plugin_status = '<span style="color: red; font-weight: bold;">'.$lang_ezbbc['Plugin disabled'].'</span>';
} else {
        // Looking first if all is really installed and updated
        $header_file_content = file_get_contents(PUN_ROOT.'header.php');
        $parser_file_content = file_get_contents(PUN_ROOT.'include/parser.php');
        // looking if the right code is in all modified files
	if (strpos($header_file_content, "<?php require PUN_ROOT.'plugins/ezbbc/ezbbc_head.php'; ?>") === false || strpos($parser_file_content, "require PUN_ROOT.'plugins/ezbbc/ezbbc_smilies1.php';") === false || strpos($parser_file_content, "require PUN_ROOT.'plugins/ezbbc/ezbbc_smilies2.php';") === false) {
	        $ezbbc_plugin_status = '<span style="color: orange; font-weight: bold;">'.$lang_ezbbc['Plugin wrong installation'].'</span>';
	}
	else {
	        $ezbbc_plugin_status = '<span style="color: green; font-weight: bold;">'.$lang_ezbbc['Plugin in action'].'</span>';
	}
}

/* If the change style button was clicked */
/* ************************************** */
if (isset($_POST['style_change'])) {
                $new_style = $_POST['ezbbc_style'];
                $new_smilies_set = $_POST['ezbbc_smilies_set'];
                $ezbbc_style_folder = $new_style;
                $ezbbc_smilies_set = $new_smilies_set;
                // Changing config data
                $config_content = trim(file_get_contents(PUN_ROOT.'plugins/ezbbc/config.php'));
                $config_item = explode(";", $config_content);
                $ezbbc_install = $config_item[0];
                $ezbbc_status = $config_item[1];
                $config_new_content = $ezbbc_install.';'.$ezbbc_status.';'.$new_style.';'.$new_smilies_set;
                $fp = fopen(PUN_ROOT.'plugins/ezbbc/config.php', 'wb');
                fwrite($fp, $config_new_content);
                fclose($fp);
                // Message to display
                 $ezbbc_style_changed = '<span style="color: green;">'.$lang_ezbbc['Style changed'].'</span>';
}

/* If the install button was clicked or the plugin was newly installed */
/* ******************************************************************* */
if (isset($_POST['enable']) || $first_install){
        /* Trying to set right permissions for files to be writeable */
        /* This doesn't consider what settings are on the server. Bad idea.
        @chmod (PUN_ROOT.'header.php', 0640);
        @chmod (PUN_ROOT.'include/parser.php', 0640);
        @chmod (PUN_ROOT.'plugins/ezbbc/config.php', 0640);*/
        
        /* Looking if the files are writable */
        if (is_writable(PUN_ROOT.'header.php') && is_writable(PUN_ROOT.'include/parser.php') && is_writable(PUN_ROOT.'plugins/ezbbc/config.php')):
    
	/* Getting the content of the header.php file */
	$file_content = file_get_contents(PUN_ROOT.'header.php');
	if (strpos($file_content, "<?php require PUN_ROOT.'plugins/ezbbc/ezbbc_head.php'; ?>") === false) {
	        //Inserting the EZBBC code by replacing an existing line
	        $search = '</title>';
	        $insert = "<?php require PUN_ROOT.'plugins/ezbbc/ezbbc_head.php'; ?>";
	        $replacement = $search."\n".$insert;
	        $file_content = str_replace ($search, $replacement, $file_content);
	        $fp = fopen (PUN_ROOT.'header.php', 'wb');
	        fwrite ($fp, $file_content);
	        fclose ($fp);
	}
		
	/* Getting the content of the include/parser.php file and processing replacment */
	$file_content = file_get_contents(PUN_ROOT.'include/parser.php');
	if (strpos($file_content, "require PUN_ROOT.'plugins/ezbbc/ezbbc_smilies1.php';") === false) {
	        //Inserting the EZBBC code by replacing several existing lines
                $search = '~\$smilies\s*=\s*array\(.*?\);~si';
                $replacement = "require PUN_ROOT.'plugins/ezbbc/ezbbc_smilies1.php';";
                $file_content = preg_replace ($search, $replacement, $file_content);
                $fp = fopen (PUN_ROOT.'include/parser.php', 'wb');
                fwrite ($fp, $file_content);
                fclose ($fp);
	}
	if (strpos($file_content, "require PUN_ROOT.'plugins/ezbbc/ezbbc_smilies2.php';") === false) {
	        //Inserting the EZBBC code by replacing several existing lines
                $search = '~\$text.*/img/smilies/.*\$text\);~';
                $replacement =  "require PUN_ROOT.'plugins/ezbbc/ezbbc_smilies2.php';";
                $file_content = preg_replace ($search, $replacement, $file_content);
                $fp = fopen (PUN_ROOT.'include/parser.php', 'wb');
                fwrite ($fp, $file_content);
                fclose ($fp);
	}
        
	/* Updating config and display datas */
	if ($first_install) {
			$ezbbc_install = time();
			$ezbbc_install_date = date($lang_ezbbc['Date format'], $ezbbc_install);
	}
        
	// Adding new data to config file
        $config_new_content = $ezbbc_install.';1;'.$ezbbc_style_folder.';'.$ezbbc_smilies_set;
        $fp = fopen(PUN_ROOT.'plugins/ezbbc/config.php', 'wb');
	fwrite($fp, $config_new_content);
	fclose($fp);
	// New status message
	$ezbbc_plugin_status = '<span style="color: green; font-weight: bold;">'.$lang_ezbbc['Plugin in action'].'</span>';
else:
        $ezbbc_plugin_status = '<span style="color: red; font-weight: bold;">'.$lang_ezbbc['Not writable'].'</span>';
endif;
}

/* If the remove button was clicked */
/* ******************************** */
if (isset($_POST['disable'])){
        /* Trying to set right permissions for files to be writeable
        @chmod (PUN_ROOT.'header.php', 0640);
        @chmod (PUN_ROOT.'include/parser.php', 0640);
        @chmod (PUN_ROOT.'plugins/ezbbc/config.php', 0640);*/

        /* First looking if the files are writable */
        if (is_writable(PUN_ROOT.'header.php') && is_writable(PUN_ROOT.'include/parser.php') && is_writable(PUN_ROOT.'plugins/ezbbc/config.php')):
    
	/* Getting the content of the header.php file */
	$file_content = file_get_contents(PUN_ROOT.'header.php');
	//Searching for ezbbc code and replacing it with nothing
	$search = "\n<?php require PUN_ROOT.'plugins/ezbbc/ezbbc_head.php'; ?>";
	$replacement = '';
	$file_content = str_replace ($search, $replacement, $file_content);
	$fp = fopen (PUN_ROOT.'header.php', 'wb');
	fwrite ($fp, $file_content);
	fclose ($fp);
	
	/* Getting the content of the include/parser.php file */
	$file_content = file_get_contents(PUN_ROOT.'include/parser.php');
	//Searching for ezbbc code and replacing it with nothing
	$search = array("require PUN_ROOT.'plugins/ezbbc/ezbbc_smilies1.php';","require PUN_ROOT.'plugins/ezbbc/ezbbc_smilies2.php';");
	$replacement = array("\$smilies = array(
	':)' => 'smile.png',
	'=)' => 'smile.png',
	':|' => 'neutral.png',
	'=|' => 'neutral.png',
	':(' => 'sad.png',
	'=(' => 'sad.png',
	':D' => 'big_smile.png',
	'=D' => 'big_smile.png',
	':o' => 'yikes.png',
	':O' => 'yikes.png',
	';)' => 'wink.png',
	':/' => 'hmm.png',
	':P' => 'tongue.png',
	':p' => 'tongue.png',
	':lol:' => 'lol.png',
	':mad:' => 'mad.png',
	':rolleyes:' => 'roll.png',
	':cool:' => 'cool.png');",
	'$text = preg_replace("#(?<=[>\s])".preg_quote($smiley_text, \'#\')."(?=\W)#m", \'<img src="\'.pun_htmlspecialchars((function_exists(\'get_base_url\') ? get_base_url(true) : $pun_config[\'o_base_url\']).\'/img/smilies/\'.$smiley_img).\'" width="15" height="15" alt="\'.substr($smiley_img, 0, strrpos($smiley_img, \'.\')).\'" />\', $text);');
	$file_content = str_replace ($search, $replacement, $file_content);
	$fp = fopen (PUN_ROOT.'include/parser.php', 'wb');
	fwrite ($fp, $file_content);
	fclose ($fp);
	
	// Adding new data to config file
	$config_new_content = $ezbbc_install.';0;'.$ezbbc_style_folder.';'.$ezbbc_smilies_set;
	$fp = fopen(PUN_ROOT.'plugins/ezbbc/config.php', 'wb');
	fwrite($fp, $config_new_content);
	fclose($fp);
	// New status message
	$ezbbc_plugin_status = '<span style="color: red; font-weight: bold;">'.$lang_ezbbc['Plugin disabled'].'</span>';
else:
        $ezbbc_plugin_status = '<span style="color: red; font-weight: bold;">'.$lang_ezbbc['Not writable'].'</span>';
endif;
}

/* If the Rename button was clicked */
/* ******************************** */
if (isset($_POST['folder_count'])){
        $folder_count = $_POST['folder_count'];
        for ($i=0; $i<$folder_count; $i++) {
                if (isset($_POST['vrename'.$i])){
                        $folder_name = $_POST['folder_name'.$i];
                        $new_name = $_POST['rename'.$i];
                        if ($new_name != '') {
                                // Converting special characters
                                $new_name = htmlentities($new_name, ENT_QUOTES, 'utf-8');
                                $search = array('&Agrave;', '&agrave;', '&Aacute;', '&aacute;', '&Acirc;', '&acirc;', '&Atilde;', '&atilde;', '&Auml;', '&auml;', '&Aring;', '&aring;', '&AElig;', '&aelig;', '&Ccedil;', '&ccedil;', '&ETH;', '&eth;', '&Egrave;', '&egrave;', '&Eacute;', '&eacute;', '&Ecirc;', '&ecirc;', '&Euml;', '&euml;', '&Igrave;', '&igrave;', '&Iacute;', '&iacute;', '&Icirc;', '&icirc;', '&Iuml;', '&iuml;', '&Ntilde;', '&ntilde;', '&Ograve;', '&ograve;', '&Oacute;', '&oacute;', '&Ocirc;', '&ocirc;', '&Otilde;', '&otilde;', '&Ouml;', '&ouml;', '&Oslash;', '&oslash;', '&OElig;', '&oelig;', '&szlig;', '&THORN;', '&thorn;', '&Ugrave;', '&ugrave;', '&Uacute;', '&uacute;', '&Ucirc;', '&ucirc;', '&Uuml;', '&uuml;', '&Yacute;', '&yacute;', '&Yuml;', '&yuml;', ' ');
                                $replace = array('a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'ae', 'c', 'c', 'd', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'i', 'i', 'i', 'i', 'n', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'oe', 'oe', 'ss', 'p', 'p', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'y', 'y', 'y', 'y', '_');
                                $new_name = str_replace($search, $replace, $new_name);
                                @rename (PUN_ROOT.'plugins/ezbbc/style/'.$folder_name, PUN_ROOT.'plugins/ezbbc/style/'.$new_name);
                        }
                }
        }
}

/* If the Copy button was clicked */
/* ******************************** */
if (isset($_GET['copy'])) {
                $style_folder = $_GET['style_folder'];
                // Creating the new folders
                mkdir(PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'-copy');
                mkdir(PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'-copy/images');
                // Copying the files in the images folder
                $images = opendir(PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'/images/');
		while(false !== ($image = readdir($images))) {
		        if ($image != '.' && $image != '..') {
		                copy (PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'/images/'.$image, PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'-copy/images/'.$image);
		        }
		}
		closedir($images);
		// Copying the css and the html file in the new style folder
		copy (PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'/ezbbc.css', PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'-copy/ezbbc.css');
		copy (PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'/index.html', PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'-copy/index.html');
		header('Location: admin_loader.php?plugin=AP_EZBBC_Toolbar.php');
}

/* If the Remove button was clicked */
/* ******************************** */
if (isset($_GET['remove'])) {
                $style_folder = $_GET['style_folder'];
                // Retrieving and removing the files in images folder
                $images = opendir(PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'/images/');
		while(false !== ($image = readdir($images))) {
		        if ($image != '.' && $image != '..') {
		                @chmod (PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'/images/'.$image, 0777);
		                @unlink (PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'/images/'.$image);
		        }
		}
		closedir($images);
		// Removing images folder
		@rmdir(PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'/images/');
		// Removing css file and html file
		@unlink (PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'/ezbbc.css');
		@unlink (PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'/index.html');
                // Removing the style folder
                @rmdir(PUN_ROOT.'plugins/ezbbc/style/'.$style_folder);
                header('Location: admin_loader.php?plugin=AP_EZBBC_Toolbar.php');
}
		                 

/* Display the admin navigation menu */
/* ********************************* */
	generate_admin_menu($plugin);

/* Display the EZBBC Tolbar admin page */
/* ************************************** */
?>
	<div id="ezbbc" class="plugin blockform">
		<h2><span><?php echo $lang_ezbbc['Plugin title'] ?></span></h2>
		<h3><span><?php echo $lang_ezbbc['Description title'] ?></span></h3>
		<div class="box">
		<?php //Retrieving language file folder
		$ezbbc_lang_folder = file_exists (PUN_ROOT.'plugins/ezbbc/lang/'.$ezbbc_language_folder.'/help.php') ? $ezbbc_language_folder : 'English';
		$help_file_path = 'plugins/ezbbc/lang/'.$ezbbc_lang_folder.'/help.php';
		?>
		<p>
		<?php echo ($lang_ezbbc['Explanation']) ?><br />
		<img src="plugins/ezbbc/style/<?php echo $ezbbc_style_folder ?>/images/help.png" alt="<?php echo $lang_ezbbc['Toolbar help'] ?>" /> <a class="toolbar_help" href="<?php echo $help_file_path ?>" title="<?php echo $lang_ezbbc['Toolbar help'] ?>" onclick="window.open(this.href, 'Toolbar_help', 'height=400, width=750, top=50, left=50, toolbar=yes, menubar=yes, location=no, resizable=yes, scrollbars=yes, status=no'); return false;"><?php echo $lang_ezbbc['Toolbar help'] ?></a>
		</p>
		</div>

		<h3><span><?php echo $lang_ezbbc['Form title'] ?></span></h3>
		<div class="box">
			<form id="ezbbcform" method="post" action="<?php echo pun_htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
				<div class="inform">
				        
					<fieldset>
						<legend><?php echo $lang_ezbbc['Legend status'] ?></legend>
						<div class="infldset">
						<ul>
						        <li><?php echo $lang_ezbbc['Plugin version'].' '.$plugin_version ?></li>
							<li><?php echo $lang_ezbbc['Installation date'] ?> <?php echo $ezbbc_install_date ?></li>
							<li><?php echo $lang_ezbbc['Available languages'] ?>
							<?php //retrieving the language folder name and flags
							$lang_folders = opendir(PUN_ROOT.'plugins/ezbbc/lang');
							while(false !== ($lang_folder = readdir($lang_folders))) {
							        if ($lang_folder != '.' && $lang_folder != '..' && is_dir('plugins/ezbbc/lang/'.$lang_folder)) {
							                $lang_flag_path = file_exists(PUN_ROOT.'plugins/ezbbc/style/admin/flags/'.strtolower($lang_folder).'.png') ? 'plugins/ezbbc/style/admin/flags/'.strtolower($lang_folder).'.png' : 'plugins/ezbbc/style/admin/flags/no_flag.png';
							                $lang_folder = ($lang_folder == $ezbbc_lang_folder) ? '<strong>'.$lang_folder.'</strong>' : $lang_folder;
							                echo '<img src="'.$lang_flag_path.'" alt="'.$lang_folder.' flag" /> '.$lang_folder.' ';
							        }
							}
							closedir($lang_folders);
							?>
							</li>
							<li><?php echo $lang_ezbbc['Plugin status'] ?> <?php echo $ezbbc_plugin_status ?></li>
						</ul>
						<p><input type="submit" name="enable" value="<?php echo $lang_ezbbc['Enable'] ?>" /><input type="submit" name="disable" value="<?php echo $lang_ezbbc['Disable'] ?>" /></p>
						</div>
					</fieldset>
					
					<fieldset>
						<legend><?php echo $lang_ezbbc['Legend style'] ?></legend>
						<div class="infldset">
						<p>
						 <input type="submit" name="style_change" value="<?php echo $lang_ezbbc['Change style'] ?>" />
						 </p>
						<?php //Displaying the current style
						$smilies_style = ($ezbbc_smilies_set == "ezbbc_smilies") ? $lang_ezbbc['EZBBC smilies'] : $lang_ezbbc['Default smilies'];
						echo '<p style="text-align: center; border: #DDD 1px solid; background: #FFF;">'.$lang_ezbbc['Current style'].' <span style="color: green; font-weight: bold;">'.$ezbbc_style_folder.'</span> ['.$lang_ezbbc['Buttons'].'] - <span style="color: green;font-weight: bold;">'.$smilies_style.'</span></p>';
						?>
						<h4 style="padding-bottom: 0; border-bottom: #DDD 2px solid;"><?php echo $lang_ezbbc['Buttons'] ?></h4>
						<script type="text/javascript" src="plugins/ezbbc/ezbbctoolbar.js"></script>
						<?php
						$style_folders = opendir(PUN_ROOT.'plugins/ezbbc/style');
						while(false !== ($style_folder = readdir($style_folders))) {
						        if ($style_folder != '.' && $style_folder != '..' && file_exists(PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'/ezbbc.css')) {
						                $unsorted_style_folders[] = $style_folder;
						        }
						}
						closedir($style_folders);
                                                // Sorting sttyle folder names
                                                natcasesort($unsorted_style_folders);
                                                $sorted_style_folders = $unsorted_style_folders;
                                                $folder_count = count($sorted_style_folders);
                                                $i = 0;
                                                foreach ($sorted_style_folders as $style_folder) {
                                                                @chmod (PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'/ezbbc.css', 0664);
                                                                // Selection of several data to display the style folder
                                                                $radio_value = ($style_folder == $ezbbc_style_folder)?'<input type="radio" value="'.$style_folder.'" name="ezbbc_style" checked="checked" /><strong>'.$style_folder.'</strong>':'<input type="radio" value="'.$style_folder.'" name="ezbbc_style" /><span style="color: grey;">'.$style_folder.'</span>';
                                                                $edit_css = (is_writable(PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'/ezbbc.css'))?' <a href="plugins/ezbbc/ezbbc_edit.php?style_folder='.$style_folder.'" onclick="window.open(this.href, \'CSS Edition\', \'height=520, width=660, top=80, left=80, toolbar=no, menubar=no, location=no, resizable=yes, scrollbars=no, status=no\'); return false;" title="'.$lang_ezbbc['Edit css'].'"><img src="plugins/ezbbc/style/admin/buttons/edit.png" alt="'.$lang_ezbbc['Edit css'].'" /></a>':'';
								$rename_folder = ((substr(decoct(@fileperms(PUN_ROOT.'plugins/ezbbc/style/')),2) == 777) || (@chmod (PUN_ROOT.'plugins/ezbbc/style/', 0777)) && ((substr(decoct(@fileperms(PUN_ROOT.'plugins/ezbbc/style/'.$style_folder)),2) == 777) || (@chmod (PUN_ROOT.'plugins/ezbbc/style/'.$style_folder, 0777))) && ($style_folder != $ezbbc_style_folder))?'<span id="rfield_'.$style_folder.'" style="display: none;"> <input type="text" name="rename'.$i.'" value="'.$style_folder.'" /> <input type="hidden" name="folder_name'.$i.'" value="'.$style_folder.'" /><input type="submit" name="vrename'.$i.'" value="'.$lang_ezbbc['OK'].'" /></span> <a href="#rfield_'.$style_folder.'" onclick="visibility(\'rfield_'.$style_folder.'\'); return false;" title="'.$lang_ezbbc['Rename'].'"><img src="plugins/ezbbc/style/admin/buttons/rename.png" alt="'.$lang_ezbbc['Rename'].'" /></a>':'';
								$copy_folder = ((substr(decoct(@fileperms(PUN_ROOT.'plugins/ezbbc/style/')),2) == 777) || (@chmod (PUN_ROOT.'plugins/ezbbc/style/', 0777)))?' <a href="admin_loader.php?plugin=AP_EZBBC_Toolbar.php&amp;style_folder='.$style_folder.'&amp;copy=yes" title="'.$lang_ezbbc['Copy'].'"><img src="plugins/ezbbc/style/admin/buttons/copy.png" alt="'.$lang_ezbbc['Copy'].'" /></a>':'';
								$remove_folder = (((substr(decoct(@fileperms(PUN_ROOT.'plugins/ezbbc/style/')),2) == 777) || (@chmod (PUN_ROOT.'plugins/ezbbc/style/', 0777))) && ((substr(decoct(@fileperms(PUN_ROOT.'plugins/ezbbc/style/'.$style_folder)),2) == 777) || (@chmod (PUN_ROOT.'plugins/ezbbc/style/'.$style_folder, 0777))) && ((substr(decoct(@fileperms(PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'/images/')),2) == 777) || (@chmod (PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'/images/', 0777))) && ((substr(decoct(@fileperms(PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'/index.html')),2) == 777) || (@chmod (PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'/index.html', 0777))) && ((substr(decoct(@fileperms(PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'/ezbbc.css')),2) == 777) || (@chmod (PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'/ezbbc.css', 0777))) && ($style_folder != $ezbbc_style_folder))?' <a href="admin_loader.php?plugin=AP_EZBBC_Toolbar.php&amp;style_folder='.$style_folder.'&amp;remove=yes" onclick="return window.confirm(\''.$lang_ezbbc['Remove confirm'].'\')" title="'.$lang_ezbbc['Remove'].'"><img src="plugins/ezbbc/style/admin/buttons/remove.png" alt="'.$lang_ezbbc['Remove'].'" /></a>':'';
								$preview_screenshot = (file_exists(PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'/images/preview.png'))?'<img src="plugins/ezbbc/style/'.$style_folder.'/images/preview.png" alt="'.$lang_ezbbc['Toolbar preview'].'" style="border: #DDD 1px groove;"/>':$lang_ezbbc['No preview'];
								echo '<dl>'."\n";
								echo '<dt>'.$radio_value.$rename_folder.$edit_css.$copy_folder.$remove_folder.'</dt>'."\n";
						                echo '<dd>'.$preview_screenshot.'</dd>'."\n";
						                echo '</dl>'."\n";
						                $i++;
						        }
						 echo '<input type="hidden" name="folder_count" value="'.$folder_count.'" />'
						?>
						
						<h4 style="padding-bottom: 0; border-bottom: #DDD 2px solid;"><?php echo $lang_ezbbc['Smilies'] ?></h4>
						<?php
						// Retrieving the smilies icons and defining the image list for each set
						//Default FluxBB smilies
						$default_smilies_images = '';
						$icons = opendir(PUN_ROOT.'img/smilies');
						while(false !== ($icon = readdir($icons))) {
						        if ($icon != '.' && $icon != '..' && substr($icon, -3) == 'png') {
						        $icon_path = 'img/smilies/'.$icon;
						        $default_smilies_images .= '<img src="'.$icon_path.'" alt="'.$lang_ezbbc['Smiley'].'" /> ';
						        }
						}
						closedir($icons);
						//EZBBC smilies
						$ezbbc_smilies_images = '';
						$icons = opendir(PUN_ROOT.'plugins/ezbbc/style/smilies');
						while(false !== ($icon = readdir($icons))) {
						        if ($icon != '.' && $icon != '..' && substr($icon, -3) == 'png') {
						                $icon_path = 'plugins/ezbbc/style/smilies/'.$icon;
						                $ezbbc_smilies_images .= '<img src="'.$icon_path.'" alt="'.$lang_ezbbc['Smiley'].'" /> ';
						        }
						}
						closedir($icons);
						//Displaying the two sets
						 if ($ezbbc_smilies_set == "fluxbb_default_smilies") {
						         echo '<dl>'."\n";
                                                         echo '<dt><input type="radio" value="fluxbb_default_smilies" name="ezbbc_smilies_set" checked="checked" /><strong>'.$lang_ezbbc['Default smilies'].'</strong></dt>'."\n";
                                                         echo '<dd>'.$default_smilies_images.'</dd>'."\n";
                                                         echo '<dt><input type="radio" value="ezbbc_smilies" name="ezbbc_smilies_set" /><strong>'.$lang_ezbbc['EZBBC smilies'].'</strong></dt>'."\n";
                                                         echo '<dd>'.$ezbbc_smilies_images.'</dd>'."\n";
                                                         echo '</dl>'."\n";
                                                 } else {
						         echo '<dl>'."\n";
                                                         echo '<dt><input type="radio" value="fluxbb_default_smilies" name="ezbbc_smilies_set"  /><strong>'.$lang_ezbbc['Default smilies'].'</strong></dt>'."\n";
                                                         echo '<dd>'.$default_smilies_images.'</dd>'."\n";
                                                         echo '<dt><input type="radio" value="ezbbc_smilies" name="ezbbc_smilies_set" checked="checked" /><strong>'.$lang_ezbbc['EZBBC smilies'].'</strong></dt>'."\n";
                                                         echo '<dd>'.$ezbbc_smilies_images.'</dd>'."\n";
                                                         echo '</dl>'."\n";
                                                 }
						?>
						<?php //Displaying the current style
						$smilies_style = ($ezbbc_smilies_set == "ezbbc_smilies") ? $lang_ezbbc['EZBBC smilies'] : $lang_ezbbc['Default smilies'];
						echo '<p style="text-align: center; border: #DDD 1px solid; background: #FFF;">'.$lang_ezbbc['Current style'].' <span style="color: green; font-weight: bold;">'.$ezbbc_style_folder.'</span> ['.$lang_ezbbc['Buttons'].'] - <span style="color: green;font-weight: bold;">'.$smilies_style.'</span></p>';
						?>
						 <p>
						 <input type="submit" name="style_change" value="<?php echo $lang_ezbbc['Change style'] ?>" />
						 </p>
						 </div>
					</fieldset>
				</div>
			</form>
		</div>
	</div>
<?php

// Note that the script just ends here. The footer will be included by admin_loader.php
