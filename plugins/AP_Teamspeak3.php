<?php
/**
 *
 * telnet in.
 *
 * tokenadd tokentype=0 tokenid1=7 tokenid2=0 tokendescription=automatically\screated\stoken\sfor\s$USER tokencustomset=ident=forum_user\svalue=$USER\pident=forum_id\svalue=$USER_ID
 *
 * <a href="ts3server://voice.teamspeak.com?nickname=$USER&addbookmark=TS-Forum-Server&token=eKnFZQ9EK7G7MhtuQB6+N2B1PNZZ6OZL3ycDp2OW/">Click here to connect</a>
 *
 *
 *
This script will connect to a TS3-Server via query-port and display
all actualy valied tokens for a desired servergroup.

Written by Nils Cibula

Dieses Script verbindet über den Query-Port zu einem TS3-Server und zeigt alle
aktuell verfügbaren Tokens für eine ausgewählte Gruppe an

Geschrieben von Nils Cibula

// Fill in connection-information for your TS3-Server
// Ab hier die nötigen Informationen deines TS3-Servers eintragen

// IP-Adress of the TS3-Server
// IP-Adresse des TS3-Servers
$ip = 'XX.XX.XX.XX';

// query-port of the TS3-Server (default: 10011)
// Query-Port des TS3-Servers (Standard: 10011)
$t_port = '10011';

// ID of the virtual server
// Server-ID des virtuellen Servers
$sid = '1';

// Login-name for query
// Login-Name zur Abfrage
$Login_Name = 'serveradmin';

// password for query-user
// Passwort zur Abfrage
$Login_pwd = '********';

// ID of the group tokens will be displayed from
// ID der Gruppe dessen Tokens angezeigt werden sollen
$Group_id = '7';

// Message if there are no tokens availible
// Nachrit wenn keine Tokens verfügbar sind
$NoTokens = 'No Tokens generated, please see your serveradministrator!';

// Generate a Token if there is none (0=No, display message instead | 1=Yes
// Neues Token erstellen falls keines verfügbar (0 = Nein, zeige die Meldung stattdessen | 1 = Yes)
$GenerateToken = '1';

// Here you can enter Text to be displayed before and after each token
// (e.g. before="<li>" and after = "</li>" to display Tokens in an unordered list)
// Hier kann Text festgelegt werden, der vor und nach jedem Token ausgegeben wird
// (z.B. vorher="<li>" und nachher = "</li>" um die Tokens in einer Liste auszugeben
$TextBefore = '';
$TextAfter = '<br /><br />';


// Don't change anything from here on (Except you are knowing what you are doing)!
// Ab hier keine Änderungen mehr vornehmen (außer du weist was du tust)!
// ------------------------------------------------------------------------------------------------------------

$error = array();

function sendCmd($fp, $cmd){
    $msg = '';
    fputs($fp, $cmd);
    while(strpos($msg, 'msg=') === false){
        $msg .= fread($fp, 8096);
    }
    if(!strpos($msg, 'msg=ok')){
        return false;
    }else{
        return $msg;
    }
}

function readToken ($fp){
    global $Group_id;
    global $NoTokens;
    $FMToken[0] = $NoTokens;
    $cmd="tokenlist\n";
    $i=0;
    if(!($tokens = sendCmd($fp, $cmd))){
        $error[] = 'no tokens availible';
    }else{
        $zeichen = explode('|',$tokens);
        foreach ($zeichen as &$token) {
            $token = explode(' ',$token);
            if ($token[2]=='token_id1='.$Group_id){
                $ausgabe = explode('=',$token[0]);
                $FMToken[$i] = stripcslashes($ausgabe[1]);
                $i++;
            }
        }
    }
    return $FMToken;
}

function makeToken ($fp){
    global $Group_id;
    $cmd="tokenadd tokentype=0 tokenid1=".$Group_id." tokenid2=0\n";
    $newToken=sendCmd($fp, $cmd);
    return $newToken;
}

error_reporting(E_ALL);

$fp = @fsockopen($ip, $t_port, $errno, $errstr, 2);
if($fp){
    $cmd = "use sid=".$sid."\n";
    if(!($select = sendCmd($fp, $cmd))){
        echo ("Auf Server 1 geschaltet");
        $error[] = 'Wrong Server ID';
    }

    $cmd="login $Login_Name $Login_pwd\n";
    if(!($sinfo = sendCmd($fp, $cmd))){
        $error[] = 'Login Denied';
    }

    $FMToken = readToken($fp);
    if ($FMToken[0] === $NoTokens){
        if ($GenerateToken === '1'){
            $NewToken = makeToken($fp);
            $FMToken = readToken($fp);
        }
    }

    
}else{
    $error[] = 'Can not connect to the server';
}
 *
 *
 */