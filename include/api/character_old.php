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
	var $characterList;
	//var $corporationRolesAtHQ = array();
	//var $corporationRolesAtBase = array();
	//var $corporationRolesAtOther = array();
	//var $corporationTitles = array();
	
	//Parsing types...
	var $current_tag = '';
	var $in_rowsets = false;
	var $in_roles = false;
	var $in_characters = false;
		
	function load_character(&$cak) {
		global $db, $pun_request, $_LAST_ERROR;
		$_LAST_ERROR = 0;
		
		//Is our CAK valid?
		if ($cak->validate(true) != CAK_OK) {
			$_LAST_ERROR = API_BAD_AUTH;
			return false;
		} //End if.
		
		$url = "https://api.eveonline.com/char/CharacterSheet.xml.aspx";
		$char_sheet;
		
		if (!$xml = $pun_request->post($url, $cak->get_auth())) {
			$_LAST_ERROR = API_BAD_REQUEST;
			return false;
		} //End if.
		
		$this->corporationRoles = '0';
		$use_roles = @bscale(0);

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
		
		if (!$use_roles) {
			$this->corporationRoles = '0';
		} //End if.
		
		return $this->characterID;
	} //End load_character().
	
	function load_skill_queue(&$cak) {
		global $db, $pun_request, $_LAST_ERROR;
		$_LAST_ERROR = 0;
		
		//If any of them are not set and if sheet is false...
		if ($cak->validate(true) != CAK_OK) {
			$_LAST_ERROR = API_BAD_AUTH;
			return false;
		} //End if.
		
		$url = "https://api.eveonline.com/char/SkillQueue.xml.aspx";
		$queue;
			
		if (!$xml = $pun_request->post($url, $cak->get_auth())) {
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
	
	function get_list(&$cak) {
		global $db, $pun_request, $_LAST_ERROR;
		$_LAST_ERROR = 0;
		
		//If any of them are not set and if sheet is false...
		if ($cak->validate() != CAK_OK) {
			$_LAST_ERROR = API_BAD_AUTH;
			return false;
		} //End if.
		
		$url = "https://api.eveonline.com/account/Characters.xml.aspx";
		$characters;
			
		if (!$xml = $pun_request->post($url, $cak->get_auth())) {
			$_LAST_ERROR = API_BAD_REQUEST;
			return false;
		} //End if.
		
		$this->characterList = array();

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
		
		if ($_LAST_ERROR > 0) {
			return false;
		} //End if.
		
		$_LAST_ERROR = 0;
		return true;
	} //End load_skill_queue().
		
	function startElement($parser, $name, $attrs) {
		$this->current_tag = $name;
		
		if ($name == 'error') {
			global $_LAST_ERROR;
			$_LAST_ERROR = intval($attrs['CODE']);
		} //End if.
		
		if ($name == 'ROWSET') {
			$this->in_rowset = true;
			
			if ($attrs['NAME'] == 'corporationRoles') {
				$this->in_roles = true;
			} else if ($attrs['NAME'] == 'skillqueue') {
				$this->in_queue = true;
			} else if ($attrs['NAME'] == 'characters') {
				$this->in_characters = true;
			} //End if - else if.
			
			return;
		} //End if.
		
		if ($name == 'ROW') {
			if ($this->in_roles) {
				$this->corporationRoles = badd($this->corporationRoles, $attrs['ROLEID']);
			} else if ($this->in_queue) {
				$this->skillQueue[] = array(
						'queuePosition' => $db->escape($attrs['QUEUEPOSITION']),
						'typeID' => $db->escape($attrs['TYPEID']),
						'level' => $db->escape($attrs['LEVEL']),
						'startSP' => $db->escape($attrs['STARTSP']),
						'endSP' => $db->escape($attrs['ENDSP']),
						'startTime' => $db->escape($attrs['STARTTIME']),
						'endTime' => $db->escape($attrs['ENDTIME'])
					);
			} else if ($this->in_characters) {
				$this->characterList[] = array(
						'name' => $db->escape($attrs['NAME']),
						'characterID' => $db->escape($attrs['CHARACTERID']),
						'corporationName' => $db->escape($attrs['CORPORATIONNAME']),
						'corporationID' => $db->escape($attrs['CORPORATIONID'])
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
			$this->in_characters = false;
		} //End if.
	} //End endElement.
	
} //End Character Class.
?>
