<?php
/**
 * 31/01/2011
 * character_simple.php
 * Panda
 */

class Character {
	
		//Single value types.
		public $characterID;
		public $name;
		public $corporationID;
		public $corporationName;
		public $allianceID;
		public $allianceName;
		public $DoB;
		public $race;
		public $bloodLine;
		public $ancestry;
		public $gender;
		public $cloneName;
		public $cloneSkillPoints;
		public $balance;
		
		//Unused types, will be uncommented as they are used; default type is array.
		//public $implants = array();
		//public $attributes = array();
		//public $skills = array();
		//public $certs = array();
		public $corporationRoles;
		//public $corporationRolesAtHQ = array();
		//public $corporationRolesAtBase = array();
		//public $corporationRolesAtOther = array();
		//public $corporationTitles = array();
		
		public function load_character($auth, &$error = 0) {
			global $db;
			$error = 0;
			
			//If any of them are not set and if sheet is false...
			if (!isset($auth['apiKey']) || !isset($auth['userID']) || !isset($auth['characterID'])) {
				$error = API_BAD_AUTH;
				return false;
			} //End if.
			
			$url = "http://api.eve-online.com/char/CharacterSheet.xml.aspx";
			$char_sheet;
			
			if (!$xml = post_request($url, $auth)) {
				$error = API_BAD_REQUEST;
				return false;
			} //End if.
				
			if (!$char_sheet = simplexml_load_string($xml)) {
				if (defined('PUN_DEBUG')) {
					error("Unable to convert xml.".print_r(libxml_get_errors(), true), __FILE__, __LINE__, $db->error());
				} //End if.
				return false;
			} //End if.
			
			if (isset($char_sheet->error)) {
				if (defined('PUN_DEBUG')) {
					error("API error while fetching character data.".$char_sheet->error, __FILE__, __LINE__, $db->error());
				} //End if.
				
				$err = (int)$char_sheet->error['code'];
				
				if ($err >= 200 && $err < 300) {
					$error = API_BAD_AUTH;
				} else {
					$error = API_SERVER_ERROR;
				} //End if - else.
				
				return false;
			} //End if.
				
			if (isset($char_sheet->html) || isset($char_sheet->body)) {
				$error = API_SERVER_DOWN;
				return false;
			} //End if.
			
			$this->corporationRoles = '0';
			bcscale(0);
			
			foreach ($char_sheet->result->rowset as $rowset) {
				if ($rowset['name'] == 'corporationRoles') {
				
					foreach($rowset->row as $row) {
						$this->corporationRoles = bcadd($this->corporationRoles, $row['roleID']);
					} //End foreach().
				} //End if.

			} //End foreach().
			
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
			
			return (int)$char_sheet->result->characterID;
		} //End load_character().
	
} //End Character Class.

?>