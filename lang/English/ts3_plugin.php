<?php
/**
 * 01/07/2011
 * ts3_plugin.php
 * Panda
 */
// Language definitions used in ts3 Plugin
$lang_ts3_plugin = array(

//General stuff.
'title1' => 'EveBB - Teamspeak 3 Plugin',
'title2' => 'Teamspeak3 Settings',
'info1' => 'This page will help you configure the options for the EveBB Teamspeak 3 Plugin.',
'info2' => 'In order to use the plugin, you must first enabled it. <br/>
Once enabled, it will produce tokens for every user who meets the requirements and provide a link in their profile to connect to your server using that token.<br/>
<br/>
To use this plugin to the best effect, as a means of securing a private server,
please read <a href="http://forum.teamspeak.com/showthread.php/56436-Restricted-Server-Access-Explained">this</a> and configure your server accordingly.',
'legend1' => 'Server Settings',
'save' => 'Save Settings',
'update_settings_redirect' => 'Settings have been saved! Redirecting...',

//Titles
'ts3_enabled' => 'Enable TS3 Support',
'ts3_ip' => 'Server Address',
'ts3_port' => 'Connection Port',
'ts3_query_port' => 'Query Port',
'ts3_timeout' => 'Connection Timeout',
'ts3_user' => 'ServerQuery User',
'ts3_pass' => 'ServerQuery Password',
'ts3_sid' => 'Server SID',
'ts3_group_id' => 'User Group ID',
'ts3_channel_id' => 'Channel ID',
'ts3_server_name' => 'Server Name',
'ts3_auth_group' => 'Auth Group',

//Info
'ts3_enabled_info' => 'Enable the TS3 plugin to generate and maintain tokens.',
'ts3_ip_info' => 'Hostname or IP of your TS3 server.',
'ts3_port_info' => 'The port you use to connect to voice comms.',
'ts3_query_port_info' => 'ServerQuery port used by your server.',
'ts3_timeout_info' => 'Timeout when trying to connect to the TS3 server, measured in seconds.',
'ts3_user_info' => 'ServerQuery User, typically "serveradmin"',
'ts3_pass_info' => 'ServerQuery Password, you should have been given this during install.',
'ts3_sid_info' => 'The SID to use. Normally set to 0, only useful in instances where you have more than one server being run.',
'ts3_group_id_info' => 'The Group ID of the TS3 group you want users to become a part of.<br/>
This should be a basic access group, without any special rights other than being able to login. <br/>
(See above link for details)',
'ts3_channel_id_info' => 'Their default Channel ID, safe to leave it at 0.',
'ts3_server_name_info' => 'Name of your server, does not need to be accurate, it is used to create bookmark links.',
'ts3_auth_group_info' => 'Only the users in this group will have tokens created.<br/>Use the Group Rules to facilitate easy admin!'

);
?>