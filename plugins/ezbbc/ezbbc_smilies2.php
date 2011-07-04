<?php
// Retrieving smilies set enabled
$config_content = trim(file_get_contents(PUN_ROOT.'plugins/ezbbc/config.php'));
$config_item = explode(";", $config_content);
$ezbbc_smilies_set = $config_item[3];
if ($ezbbc_smilies_set == 'ezbbc_smilies'):
$text = preg_replace("#(?<=[>\s])".preg_quote($smiley_text, '#')."(?=\W)#m", '<img src="'.pun_htmlspecialchars((function_exists('get_base_url') ? get_base_url(true) : $pun_config['o_base_url']).'/plugins/ezbbc/style/smilies/'.$smiley_img).'" alt="'.substr($smiley_img, 0, strrpos($smiley_img, '.')).'" />', $text);
else:
$text = preg_replace("#(?<=[>\s])".preg_quote($smiley_text, '#')."(?=\W)#m", '<img src="'.pun_htmlspecialchars((function_exists('get_base_url') ? get_base_url(true) : $pun_config['o_base_url']).'/img/smilies/'.$smiley_img).'" width="15" height="15" alt="'.substr($smiley_img, 0, strrpos($smiley_img, '.')).'" />', $text);
endif;
?>
