<?php // Retrieving smilies set enabled
$config_content = trim(file_get_contents(PUN_ROOT.'plugins/ezbbc/config.php'));
$config_item = explode(";", $config_content);
$ezbbc_smilies_set = $config_item[3];
if ($ezbbc_smilies_set == 'ezbbc_smilies'):
$smilies = array(
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
	':cool:' => 'cool.png',
	//New smilies
	'O:)' => 'angel.png',
	'o:)' => 'angel.png',
	':angel:' => 'angel.png',
	'8.(' => 'cry.png',
	':cry:' => 'cry.png',
	']:D' => 'devil.png',
	':devil:' => 'devil.png',
	'8)' => 'glasses.png',
	':glasses:' => 'glasses.png',
	'{)' => 'kiss.png',
	':kiss:' => 'kiss.png',
	'8o' => 'monkey.png',
	':monkey:' => 'monkey.png',
	':8' => 'ops.png',
	':ops:' => 'ops.png');
else:
$smilies = array(
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
	':cool:' => 'cool.png');
endif;
?>
