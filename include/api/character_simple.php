<?php
/**
 * 31/01/2011
 * character_simple.php
 * Panda
 */

class Character {
	
	//Single value types.
	var $characterID;
	var $name;
	var $corporationID;
	var $corporationName;
	var $allianceID;
	var $allianceName;
	var $DoB;
	var $race;
	var $bloodLine;
	var $ancestry;
	var $gender;
	var $cloneName;
	var $cloneSkillPoints;
	var $balance;
	
	//Unused types, will be uncommented as they are used; default type is array.
	//public $implants = array();
	//public $attributes = array();
	//public $skills = array();
	//public $certs = array();
	var $corporationRoles;
	var $skillQueue;
	var $characterList;
	//public $corporationRolesAtHQ = array();
	//public $corporationRolesAtBase = array();
	//public $corporationRolesAtOther = array();
	//public $corporationTitles = array();
	
	function load_character(&$cak) {
		global $db, $pun_request, $_LAST_ERROR;
		$_LAST_ERROR = 0;
		
		//Is our CAK valid?
		if ($cak->validate(true) != CAK_OK) {
			$_LAST_ERROR = API_BAD_AUTH;
			return false;
		} //End if.
		
		$url = "http://api.eve-online.com/char/CharacterSheet.xml.aspx";
		$char_sheet;
		
		if (!$xml = $pun_request->post($url, $cak->get_auth())) {
			$_LAST_ERROR = API_BAD_REQUEST;
			return false;
		} //End if.
			
		if (!$char_sheet = simplexml_load_string($xml)) {
			if (defined('PUN_DEBUG')) {
				error("Unable to convert xml.".print_r(libxml_get_errors(), true), __FILE__, __LINE__, $db->error());
			} //End if.
			return false;
		} //End if.
		
		if (isset($char_sheet->error)) {
			
			$err = (int)$char_sheet->error['code'];
			
			if ($err >= 200 && $err < 300) {
				if ($err == 211) {
					$_LAST_ERROR = API_ACCOUNT_STATUS;
				} else {
					$_LAST_ERROR = API_BAD_AUTH;
				} //End if - else.
			} else {
				$_LAST_ERROR = API_SERVER_ERROR;
			} //End if - else.
			
			return false;
		} //End if.
			
		if (isset($char_sheet->html) || isset($char_sheet->body)) {
			$_LAST_ERROR = API_SERVER_DOWN;
			return false;
		} //End if.
		
		$this->corporationRoles = '0';
		
		if (bscale(0)) {
			foreach ($char_sheet->result->rowset as $rowset) {
				if ($rowset['name'] == 'corporationRoles') {
					foreach($rowset->row as $row) {
						$this->corporationRoles = badd($this->corporationRoles, $row['roleID']);
					} //End foreach().
				} //End if.
			} //End foreach().
		} //End if.
		
		$this->characterID = (int)$char_sheet->result->characterID;
		$this->name = $db->escape((string)$char_sheet->result->name);
		$this->corporationID = (int)$char_sheet->result->corporationID;
		$this->corporationName = $db->escape((string)$char_sheet->result->corporationName);
		$this->allianceID = (int)$char_sheet->result->allianceID;
		$this->allianceName = $db->escape((string)$char_sheet->result->allianceName);
		$this->DoB = $db->escape((string)$char_sheet->result->DoB);
		$this->race = $db->escape((string)$char_sheet->result->race);
		$this->bloodLine = $db->escape((string)$char_sheet->result->bloodLine);
		$this->ancestry = $db->escape((string)$char_sheet->result->ancestry);
		$this->gender = $db->escape((string)$char_sheet->result->gender);
		$this->cloneName = $db->escape((string)$char_sheet->result->cloneName);
		$this->cloneSkillPoints = (int)$char_sheet->result->cloneSkillPoints;
		$this->balance = (float)$char_sheet->result->balance;
		$_LAST_ERROR = 0;
		return (int)$char_sheet->result->characterID;
	} //End load_character().
		
	function load_skill_queue(&$cak) {
		global $db, $pun_request, $_LAST_ERROR;
		$_LAST_ERROR = 0;
		
		//If any of them are not set and if sheet is false...
		if ($cak->validate(true) != CAK_OK) {
			$_LAST_ERROR = API_BAD_AUTH;
			return false;
		} //End if.
		
		$url = "http://api.eve-online.com/char/SkillQueue.xml.aspx";
		$queue;
			
		if (!$xml = $pun_request->post($url, $cak->get_auth())) {
			$_LAST_ERROR = API_BAD_REQUEST;
			return false;
		} //End if.
			
		if (!$queue = simplexml_load_string($xml)) {
			if (defined('PUN_DEBUG')) {
				error("Unable to convert xml.".print_r(libxml_get_errors(), true), __FILE__, __LINE__, $db->error());
			} //End if.
			return false;
		} //End if.
		
		if (isset($queue->error)) {
			if (defined('PUN_DEBUG')) {
				error("API error while fetching character data.".$char_sheet->error, __FILE__, __LINE__, $db->error());
			} //End if.
			
			$err = (int)$queue->error['code'];
			
			if ($err >= 200 && $err < 300) {
				$_LAST_ERROR = API_BAD_AUTH;
			} else {
				$_LAST_ERROR = API_SERVER_ERROR;
			} //End if - else.
			
			return false;
		} //End if.
				
		if (isset($char_sheet->html) || isset($char_sheet->body)) {
			$_LAST_ERROR = API_SERVER_DOWN;
			return false;
		} //End if.
		
		$this->skillQueue = array();
		
		foreach ($queue->result->rowset->row as $row) {
			
			$this->skillQueue[] = array(
					'queuePosition' => $db->escape($row['queuePosition']),
					'typeID' => $db->escape($row['typeID']),
					'level' => $db->escape($row['level']),
					'startSP' => $db->escape($row['startSP']),
					'endSP' => $db->escape($row['endSP']),
					'startTime' => $db->escape($row['startTime']),
					'endTime' => $db->escape($row['endTime'])
				);
			
		} //End foreach().
		
		return true;
		
	} //End load_skill_queue().
	
	function get_list(&$cak) {
		global $db, $pun_request, $_LAST_ERROR;
		$_LAST_ERROR = 0;
		
		//If any of them are not set and if sheet is false...
		if ($cak->validate() != CAK_OK) {
			$_LAST_ERROR = API_BAD_AUTH;
			return false;
		} //End if.
		
		$url = "http://api.eve-online.com/account/Characters.xml.aspx";
		$characters;
			
		if (!$xml = $pun_request->post($url, $cak->get_auth())) {
			$_LAST_ERROR = API_BAD_REQUEST;
			return false;
		} //End if.
			
		if (!$characters = simplexml_load_string($xml)) {
			if (defined('PUN_DEBUG')) {
				error("Unable to convert xml.".print_r(libxml_get_errors(), true), __FILE__, __LINE__, $db->error());
			} //End if.
			return false;
		} //End if.
		
		if (isset($characters->error)) {
			if (defined('PUN_DEBUG')) {
				error("API error while fetching character list.".$char_sheet->error, __FILE__, __LINE__, $db->error());
			} //End if.
			
			$err = (int)$queue->error['code'];
			
			if ($err >= 200 && $err < 300) {
				$_LAST_ERROR = API_BAD_AUTH;
			} else {
				$_LAST_ERROR = API_SERVER_ERROR;
			} //End if - else.
			
			return false;
		} //End if.
				
		if (isset($char_sheet->html) || isset($char_sheet->body)) {
			$_LAST_ERROR = API_SERVER_DOWN;
			return false;
		} //End if.
		
		$this->characterList = array();
		
		foreach ($characters->result->rowset->row as $row) {
			
			$this->characterList[] = array(
					'name' => $db->escape($row['name']),
					'characterID' => $db->escape($row['characterID']),
					'corporationName' => $db->escape($row['corporationName']),
					'corporationID' => $db->escape($row['corporationID'])
				);
			
		} //End foreach().
		
		return true;
	} //End get_list().
	
} //End Character Class.

?>