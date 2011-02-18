<?php
/**
 * 31/01/2011
 * character_old.php
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
	
	//Array types.
	//var $implants = array();
	//var $attributes = array();
	//var $skills = array();
	//var $certs = array();
	var $corporationRoles;
	var $skillQueue;
	//var $corporationRolesAtHQ = array();
	//var $corporationRolesAtBase = array();
	//var $corporationRolesAtOther = array();
	//var $corporationTitles = array();
	
	//Parsing types...
	var $current_tag = '';
	var $in_rowsets = false;
	var $in_roles = false;
		
	function load_character($auth) {
		global $db, $_LAST_ERROR;
		
		//If any of them are not set and if sheet is false...
		if (!isset($auth['apiKey']) || !isset($auth['userID']) || !isset($auth['characterID'])) {
			$_LAST_ERROR = API_BAD_AUTH;
			return false;
		} //End if.
		
		$url = "http://api.eve-online.com/char/CharacterSheet.xml.aspx";
		
		if (!$xml = post_request($url, $auth)) {
			$_LAST_ERROR = API_BAD_REQUEST;
			return false;
		} //End if.
		
		$this->corporationRoles = '0';
		bcscale(0);

		$xml_parser = xml_parser_create();
		xml_set_object($xml_parser, $this);
		xml_set_element_handler($xml_parser, "startElement", "endElement");
		xml_set_character_data_handler($xml_parser, "characterData");
		
		if (!xml_parse($xml_parser, $xml, true)) {
			error(sprintf("XML error: %s at line %d",
			xml_error_string(xml_get_error_code($xml_parser)),
			xml_get_current_line_number($xml_parser)), __FILE__, __LINE__);
		} //End if.
		xml_parser_free($xml_parser);
		
		$this->characterID = $this->CHARACTERID;
		$this->name = $db->escape($this->NAME);
		$this->corporationID = $this->CORPORATIONID;
		$this->corporationName = $db->escape($this->CORPORATIONNAME);
		$this->allianceID = $this->ALLIANCEID;
		$this->allianceName = $db->escape($this->ALLIANCENAME);
		$this->DoB = $db->escape($this->DOB);
		$this->race = $db->escape($this->RACE);
		$this->bloodLine = $db->escape($this->BLOODLINE);
		$this->ancestry = $db->escape($this->ANCESTRY);
		$this->gender = $db->escape($this->GENDER);
		$this->cloneName = $db->escape($this->CLONENAME);
		$this->cloneSkillPoints = $this->CLONESKILLPOINTS;
		$this->balance = $this->BALANCE;
		$_LAST_ERROR = 0;
		return $this->characterID;
	} //End load_character().
	
	function load_skill_queue($auth) {
		global $db, $_LAST_ERROR;
		
		//If any of them are not set and if sheet is false...
		if (!isset($auth['apiKey']) || !isset($auth['userID']) || !isset($auth['characterID'])) {
			$_LAST_ERROR = API_BAD_AUTH;
			return false;
		} //End if.
		
		$url = "http://api.eve-online.com/char/SkillQueue.xml.aspx";
		
		if (!$xml = post_request($url, $auth)) {
			$_LAST_ERROR = API_BAD_REQUEST;
			return false;
		} //End if.
		
		$this->skillQueue = array();

		$xml_parser = xml_parser_create();
		xml_set_object($xml_parser, $this);
		xml_set_element_handler($xml_parser, "startElement", "endElement");
		xml_set_character_data_handler($xml_parser, "characterData");
		
		if (!xml_parse($xml_parser, $xml, true)) {
			error(sprintf("XML error: %s at line %d",
			xml_error_string(xml_get_error_code($xml_parser)),
			xml_get_current_line_number($xml_parser)), __FILE__, __LINE__);
		} //End if.
		xml_parser_free($xml_parser);
		
		$_LAST_ERROR = 0;
		return true;
	} //End load_skill_queue().
		
	function startElement($parser, $name, $attrs) {
		$this->current_tag = $name;
		
		if ($name == 'ROWSET') {
			$this->in_rowset = true;
			
			if ($attrs['NAME'] == 'corporationRoles') {
				$this->in_roles = true;
			} else if ($attrs['NAME'] == 'skillqueue') {
				$this->in_queue = true;
			} //End if - else if.
			
			return;
		} //End if.
		
		if ($name == 'ROW') {
			if ($this->in_roles) {
				$this->corporationRoles = bcadd($this->corporationRoles, $attrs['ROLEID']);
			} else if ($this->in_queue) {
				$this->skillQueue[] = array(
						'queuePosition' => $attrs['QUEUEPOSITION'],
						'typeID' => $attrs['TYPEID'],
						'level' => $attrs['LEVEL'],
						'startSP' => $attrs['STARTSP'],
						'endSP' => $attrs['ENDSP'],
						'startTime' => $attrs['STARTTIME'],
						'endTime' => $attrs['ENDTIME']
					);
			} //End if - else if.
		} //End if.
		
	} //startElement
		
	function characterData($parser, $data) {
		if ($this->in_rowset) {
			return; //We don't care about the characterData while in a rowset.
		} //End if.
		
		if (preg_match('/^[a-zA-Z0-9\.\\\'\/\" \-\:]+$/', $data)) {
			eval('$this->'.$this->current_tag.'="'.addslashes($data).'";');
		} //End if.
	} //End characterData().
		
	function endElement($parser, $name) {
		$this->current_tag = '';
		if ($name == 'ROWSET') {
			$this->in_rowset = false;
			$this->in_roles = false;
			$this->in_queue = false;
		} //End if.
	} //End endElement.
	
} //End Character Class.
?>