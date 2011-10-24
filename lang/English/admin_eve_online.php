<?php

// Language definitions used in EvE_Online Plugin
$lang_admin_eve_online = array(

'No text'					=>	'You didn\'t enter anything!',
'title'						=>	'Eve Online Settings',
'You said'				=>	'You said "%s". Great stuff.',
'Explanation 1'			=>	'This page is used to configure the various EvE related options on your new Eve-BB install!',
'Explanation 2'			=>	'Make sure you read the descriptions for each option carefully and remember to submit them to save them to the database.',
'banner form'			=>	'Banner Settings',
'Legend text'			=>	'Upload a banner for your forum',
'Text to show'			=>	'Select File',
'Show text button'	=>	'Upload File',
'Input content'			=>	'Banners must be 1000x150 and in .jpg or .png format.',
'corp_setting'			=> 'Eve Settings',
'corp_legend_text'	=> 'Corporation &amp; Alliance Settings',
'corp_name'				=> 'Corporation ID',
'corp_fetch'				=> 'Fetch Corporation',
'corp_input_content'	=> 'Put in the Corporation ID you wish to use for this forum.',
'api_legend_text'		=> 'API Settings',

'general_legend_text' => 'General Board Settings',

//Banner stuffs
'upload_redirect'		=> 'Banner uploaded!',
'Too large ini'			=>	'The selected file was too large to upload. The server didn\'t allow the upload.',
'Partial upload'			=>	'The selected file was only partially uploaded. Please try again.',
'No tmp directory'		=>	'PHP was unable to save the uploaded file to a temporary location.',
'No file'					=>	'You did not select a file for upload.',
'Bad type'				=>	'The file you tried to upload is not of an allowed type. Allowed types are gif, jpeg and png.',
'Too wide or high'		=>	'The file you tried to upload is wider and/or higher than the maximum allowed',
'Too large'				=>	'The file you tried to upload is larger than the maximum allowed',
'pixels'					=>	'pixels',
'bytes'					=>	'bytes',
'Move failed'			=>	'The server was unable to save the uploaded file. Please contact the forum administrator at',
'Unknown failure'		=>	'An unknown error occurred. Please try again.',
'File'							=>	'File',
'Upload'						=>	'Upload', // submit button

//Banner selection...
'banner_select_legend' => 'Select active banner',
'banner_select' => 'Select banner',
'banner_select_submit' => 'Save',
'banner_select_info' => 'Pick a banner from the drop down box to have it display on the main page.',
'banner_select_redirect' => 'Banner selection saved! Redirecting.',

//Group Rule stuff
'group_rule_title' 		=> 'Group Rules',
'group_rule_legend' 	=> 'Manage Group Rules',
'group_rule' 			=> 'New Rule',
'group_rule_add' 		=> 'Add Rule',
'group_rule_add_redirect' => 'Group rule added succesfully! Redirecting...',
'group_rule_info' 		=> 'A \'Group Rule\' is used to automatically assign new members to groups you\'ve created.<br/>
These rules are given a priority level so that you can decide which group should be the \'active\' group, that applies titles, name colouring and such.<br/>
<br/>
Priority ranges from \'0 - top priority\', to \'%s - lowest priority\'. <br/>
In the event that two rules have the same priority level, the active one will be decided by the order the Database presents them.<br/>
As such, it is recommended that you manually set priorities to unique values to insure you experience the behaviour you are after.<br/>
<br/>
You may update a rules priority by simply creating it again with a different priority - it will over write any existing rule with the new value.<br/>
<br/><a href="admin_eve_groups.php?action=refresh_rules">Click here to re-apply any rules.</a>',
'group_rule_members_from' => 'Member of: ',
'group_rule_members_to' => 'To group: ',
'group_rule_priority' => 'With Priority: ',
'group_rule_role' => 'Has Role: ',
'purge_group' => 'Purged User Group',
'purge_group_info' => 'Set the default group that purged users will end up in. Ideally set it to a group with very minimal access to the forum while still able to access their profile to update their characters.',
'delete_group_rule_legend' => 'Existing Rules',
'delete_group_rule' => 'Format displayed is: &lt;Role&gt; [Priority] Member of (corp/alliance) -&gt; (goes to) Group',
'group_rule_del_redirect' => 'Group rule deleted succesfully! Redirecting...',
'delete' => 'Delete',

//Allowed List stuff
'allowed_corp_legend' 	=> 'Allow new corporation',
'allowed_corp' 			=> 'Allow Corp',
'allowed_corp_add' 		=> 'Add Corp',
'allowed_corp_add_redirect' => 'Corp added succesfully! Redirecting...',
'allowed_corp_info' 		=> 'Allow additional corps to use your forum.',
'allowed_corp_id' => '<em>Please enter the Corporation ID you wish to add.</em>',
'allowed_title' 		=> 'Allowed Corporations &amp; Alliances',
'allowed_corp_title' 		=> 'Allowed Corporations',
'allowed_alliance_legend' 	=> 'Allow new alliance',
'allowed_alliance' 			=> 'Allow Alliance',
'allowed_alliance_add' 		=> 'Add Alliance',
'allowed_alliance_add_redirect' => 'Alliance added succesfully! Redirecting...',
'allowed_alliance_info' 		=> 'Allow additional alliances to use your forum.<br /><a href="admin_eve_alliance.php?action=refresh_alliance_list">Click here to refresh the Alliance List.</a>',
'delete_allowed_legend' => 'Delete allowed entity',
'delete_allowed' => 'Delete any alliance or corporation you no longer wish to have access to this board.',
'delete_allowed_corp_legend' => 'Delete allowed corporation',
'delete_allowed_corp' => 'Delete any corporation you no longer wish to have access to this board.',
'allowed_del_redirect' => 'Allowed entity deleted succesfully! Redirecting...',
'delete' => 'Delete',
'alliance_list_refresh_redirect' => 'Alliance list updated successfully! Redirecting...',
'allowed_alliance_redirect' => 'Allowed alliance added succesfully! Redirecting...',
'removed_alliance_redirect' => 'Alliance purged succesfully! Redirecting...',
'removed_corp_redirect' => 'Corp purged succesfully! Redirecting...',
'apply_rules' => 'Group rules have been reapplied.<br/><br/>See below for details.',

//Options
'update_settings_redirect' => 'Config updated! Redirecting...',
'o_eve_use_iga' => 'In Game Avatar', //in game avatar
'o_eve_use_iga_info' => 'Enable the replacement of the users avatar with their in-game avatar.<br/>This also makes sure their selected character is used, instead of their registration name.',
'o_eve_use_corp_name' => 'Use Corp Name',
'o_eve_use_corp_name_info' => 'Use the users corporation name in the users information on each post. (Under their avatar)',
'o_eve_restrict_reg_corp' => 'Restrict Registration',
'o_eve_restrict_reg_corp_info' => 'This will prevent anyone registering on these forums unless they are in an allowed corporation or alliance. Use wisely!',
'o_eve_use_ticker_corp' => 'Use Corp Ticker',
'o_eve_use_ticker_corp_info' => 'Use the users corporation ticker in the users information on each post.',
'o_eve_use_ally_name' => 'Use Alliance Name',
'o_eve_use_ally_name_info' => 'Use the users alliance name in the users information on each post. (Under their avatar)',
'o_eve_use_ticker_ally' => 'Use Alliance Ticker',
'o_eve_use_ticker_ally_info' => 'Use the users alliance ticker in the users information on each post.',
'o_eve_cache_char_sheet' => 'Character Sheet Checking',
'o_eve_cache_char_sheet_info' => 'How long between scheduled character sheet checking. (To check corp status, roles, etc.)',
'o_eve_cache_char_sheet_interval' => 'Char Sheet Interval',
'o_eve_cache_char_sheet_interval_info' => 'This determines how long each character sheet is considered valid.<br/>
The server will update one character sheet at a time, meaning you will need to adjust this depending on how active your forum is.<br/>
<br/>
Setting this below 4 Hours is not reccommended as each time this is called it adds ~2s to each page load time.',
'o_eve_rules_interval' => 'Group Rule Interval',
'o_eve_rules_interval_info' => 'This determines how long the server waits before re-checking the group rules you\'ve setup.',
'o_eve_auth_interval' => 'Char Auth Interval',
'o_eve_auth_interval_info' => 'This determines how long the server waits before re-checking character credentials.<br/>
Low load added, may be set to happen fairly often to catch the updates done by [Char Sheet Interval].',
'o_eve_use_cron' => 'Use Cron Job',
'o_eve_use_cron_info' => 'Enable the use a cron job for scheduled events, such as fetching character data.<br />Please see the README file for information in setting up a cron job.',
'o_eve_max_groups' => 'Max Group Rule Priorities',
'o_eve_max_groups_info' => 'Lets you adjust how many priorities are displayed in the drop down box on the Group Rules section. More is slower per load.',
'o_eve_use_banner' => 'Use Forum Banner',
'o_eve_use_banner_info' => 'Enable a banner for the top of your forum! Mmmm, pretty...',
'o_eve_use_banner_text' => 'Use Forum Banner Text',
'o_eve_use_banner_text_info' => 'Enable the text that sits ontop of your forum banner.<br />(Disable this if you have a really pretty banner)',
'o_eve_banner_size' => 'Max Banner File Size',
'o_eve_banner_size_info' => 'Set the maximum file size for the forums banner, in bytes.<br /> (Default: 819200, 800KB)',
'o_eve_banner_width' => 'Max Banner Width',
'o_eve_banner_width_info' => 'Set the maximum width for the forums banner. Setting this above 1000px is not reccomended unless you plan to edit evebb.css as well. (Line: 562)',
'o_eve_banner_height' => 'Max Banner Height',
'o_eve_banner_height_info' => 'Set the maximum height for the forums banner. Forum will adjust to the height, so go nuts.',
'o_eve_banner_dir' => 'Banner Directory',
'o_eve_banner_dir_info' => 'Set the base directory to store/look in for banners. Omit the trailing slash.<br /> (Example: img/banners)',
'o_hide_stats' => 'Hide Forum Details',
'o_hide_stats_info' => 'Hide detials about the forum from guest members.',
//New since 1.1.2+
'o_eve_use_image_server' => 'Use CCP Image Server',
'o_eve_use_image_server_info' => 'Use the CCP Image Server for user avatars instead of using local copies.',
'o_use_fopen' => 'Use fopen',
'o_use_fopen_info' => 'Force the use of fopen as opposed to cURL.<br/>Ideally, you should only enable this is you are having an issue with cURL.',
'o_eve_char_pic_size' => 'Character Avatar Size',
'o_eve_char_pic_size_info' => 'Choose between 64 x 64px avatars or the larger 128 x 128px versions.',
'o_eve_cak_mask' => 'Custom API Key Access Mask',
'o_eve_cak_mask_info' => 'Define the minimum level of access a CAK needs to be accepted on this forum.<br/>[33947656] is the minimum accepted mask.',
'o_eve_cak_type' => 'Custom API Key Type',
'o_eve_cak_type_info' => 'Set the minimum type of CAK you will accept.<br/>To expose all of an accounts characters, use Account type.',
'cak_type_char' => 'Character',
'cak_type_acc' => 'Account',
'bad_cak_mask' => 'The CAK mask you have entered does not meet the minimum requirements.<br/>Please go back and adjust it as required.',
);

?>
