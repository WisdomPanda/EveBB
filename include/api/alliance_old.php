<?php
/**
 * 31/01/2011
 * alliance_old.php
 * Panda
 */

class Alliance {
	
	var  $in_alliance = false;
	var  $in_corps = false;
	var  $current_tag;
	var  $current_alliance;
	
	/**
	 * Because of the size of the list, we get this function to handle the DB side of things, otherwise it'd have a (more) massive memory footprint.
	 */
	function update_list() {
		
		global $db, $pun_request, $_LAST_ERROR;
		$_LAST_ERROR = 0;
		
		$url = 'http://api.eve-online.com/eve/AllianceList.xml.aspx';
		
		if (!$xml = $pun_request->post($url)) {
			$_LAST_ERROR = API_BAD_REQUEST;
			return false;
		} //End if.
		
		if (!$db->truncate_table('api_alliance_corps')) {
			if (defined('PUN_DEBUG')) {
					error("Unable to delete corps.", __FILE__, __LINE__, $db->error());
				} //End if.
			return false;
		} //End if.
		
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
		
		return true;
	} //End load_list().
		
	function startElement($parser, $name, $attrs) {
		global $db;
		$this->current_tag = $name;
		
		if ($name == 'ROWSET') {
			if (!$this->in_alliance) {
				$this->in_alliance = true;
			} else {
				$this->in_corps = true;
			} //End if - else.
			return;
		} //End if.
		
		if ($name == 'ROW') {
			if ($this->in_corps) {
				$sql = "INSERT INTO ".$db->prefix."api_alliance_corps(allianceID, corporationID, startDate) VALUES(".$this->current_alliance.", ".$attrs['CORPORATIONID'].", '".$attrs['STARTDATE']."')";
				if (!$db->query($sql)) {
					if (defined('PUN_DEBUG')) {
						error("Unable to insert corp.<br/>".$sql."<br/>".print_r($corp, true)."<br/>", __FILE__, __LINE__, $db->error());
					} //End if.
					return false;
				} //End if.
			} else {
				$this->current_alliance = $attrs['ALLIANCEID'];
				$fields = array(
						'allianceID' => $this->current_alliance,
						'name' => $db->escape($attrs['NAME']),
						'shortName' => $db->escape($attrs['SHORTNAME']),
						'memberCount' => $attrs['MEMBERCOUNT'],
						'executorCorpID' => $attrs['EXECUTORCORPID'],
						'startDate' => $attrs['STARTDATE']
					);
				
				if (!$db->insert_or_update($fields, 'allianceID', $db->prefix.'api_alliance_list')) {
					if (defined('PUN_DEBUG')) {
							error((string)$list->error."<br/>".$sql."<br/>".print_r($row, true)."<br/>", __FILE__, __LINE__, $db->error());
						} //End if.
					return false;
				} //End if.
			} //End if - else.
		} //End if.
		
	} //startElement
		
	function characterData($parser, $data) {
		return; //No character data is delt with in alliance list XML.
	} //End characterData().
		
	function endElement($parser, $name) {
		$this->current_tag = '';
		if ($name == 'ROWSET') {
			if ($this->in_corps) {
				$this->in_corps = false;
			} else {
				$this->in_alliance = false;
			} //End if - else.
		} //End if.
	} //End endElement.
	
} //End Alliance class.

?>