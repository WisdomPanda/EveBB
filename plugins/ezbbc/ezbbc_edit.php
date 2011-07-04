<?php
// Retieving admin datas
define('PUN_ADMIN_CONSOLE', 1);

define('PUN_ROOT', '../../');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'include/common_admin.php';

if (!$pun_user['is_admmod'])
	message($lang_common['No permission']);

// Language file load
$ezbbc_language_folder = file_exists(PUN_ROOT.'plugins/ezbbc/lang/'.$admin_language.'/ezbbc_plugin.php') ? $admin_language : 'English';    
require PUN_ROOT.'plugins/ezbbc/lang/'.$ezbbc_language_folder.'/ezbbc_plugin.php';

// Retrieving the css file and defining some variables
$style_folder = $_GET['style_folder'];
$css_content = file_get_contents(PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'/ezbbc.css');
$edition_ok = '';

// If validation button has been cliked
if (isset($_POST['validation'])) {
        $new_css_content = $_POST['new_css_content'];
        $old_css_content = $_POST['old_css_content'];
        if ($new_css_content != $old_css_content) {
                $fp = fopen(PUN_ROOT.'plugins/ezbbc/style/'.$style_folder.'/ezbbc.css', 'wb');
                fwrite($fp, $css_content);
                fclose($fp);
                $css_content = $new_css_content;
                $edition_ok = true;
        } else {
                $css_content = $old_css_content;
                $edition_ok = false;
        }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="<?php echo PUN_ROOT.'style/'.$pun_user['style'].'.css' ?>" />
<title><?php echo $style_folder ?> - <?php echo $lang_ezbbc['Edit css'] ?></title>
<?php if ($edition_ok !== ''): ?>
<!-- JS to notify if the edition worked or not by changing background color of the form -->
<script type="text/javascript">
/* <![CDATA[ */
window.onload = function() {
        <?php if ($edition_ok === true): ?>
	var colors = ['#205F00', '#296F07', '#357F0F', '#418F1A', '#4F9F27', '#5FAF36', '#6FBF47', '#81CF5A', '#94DF6F', '#A9EF86', '#BFFF9F', 'transparent']; // Green: edition worked
	<?php elseif ($edition_ok === false): ?>
	var colors = ['#8F0A00', '#9F1409', '#AF2015', '#BF2E23', '#CF3E33', '#DF5045', '#EF6459', '#EF6459', '#FFA69F', '#FFD2CF', '#FFF0EF', 'transparent']; // Red: edition didn't work
	<?php endif; ?>
	textarea = document.getElementById('cssedit');
	(function colorChange() {
		textarea.style.backgroundColor = colors.shift();
		colors.length && setTimeout(colorChange, 50);
	})();
};
/* ]]> */
</script>
<?php endif; ?>
</head>
<body>
<div id="punedit">
<div class="pun">
<div class="punwrap">
<div id="brdmain">
<div id="editform">
<form id="edit" style="margin: 10px;" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
        <fieldset>
        <textarea id="cssedit" name="new_css_content" rows="19" cols="68"><?php echo $css_content ?></textarea>
        <input type="hidden" name="old_css_content" value="<?php echo $css_content ?>" />
        <p><input type="submit" name="validation" value="<?php echo $lang_ezbbc['OK'] ?>" /></p>
        </fieldset>
</form>
</div>
</div>
</div>
</div>
</div>
</body>
</html>
