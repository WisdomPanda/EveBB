<?php
/**
 * 31/01/2011
 * corporation_old.php
 * Panda
 */

class Corporation {
	
	var $corporationID;
	var $corporationName;
	var $ticker;
	var $ceoID;
	var $ceoName;
	var $description;
	var $url;
	var $allianceID;
	var $taxRate;
	
	function load_corp($corpID) {
		global $db, $pun_request, $_LAST_ERROR;
		$_LAST_ERROR = 0;
		
		$url = "https://api.eveonline.com/corp/CorporationSheet.xml.aspx";
		
		if (!$xml = $pun_request->post($url, array('corporationID' => $corpID))) {
			$_LAST_ERROR = API_BAD_REQUEST;
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
		
		//Just the basics.
		$this->corporationID = $this->CORPORATIONID;
		$this->corporationName = $db->escape($this->CORPORATIONNAME);
		$this->ticker = $db->escape($this->TICKER);
		$this->ceoID = $this->CEOID;
		$this->ceoName = $db->escape($this->CEONAME);
		$this->description = $db->escape(stripslashes($this->DESCRIPTION));
		$this->url = $db->escape($this->URL);
		$this->allianceID = $this->ALLIANCEID;
		$this->taxRate = $this->TAXRATE;
		
		return true;
		
	} //End load_corp().
	
	function startElement($parser, $name, $attrs) {
		$this->current_tag = $name;
	} //startElement
		
	function characterData($parser, $data) {
		if (preg_match('/^[a-zA-Z0-9\.\\\'\/\" \-\:\<\>\(\)\,\=\~\!\@\#\$\%\^\&\*\_\+\[\]\{\}\;]+$/', $data)) {
			eval('$this->'.$this->current_tag.'.="'.addslashes($data).'";');
		} //End if.
	} //End characterData().
		
	function endElement($parser, $name) {
		$this->current_tag = '';
	} //End endElement.
	
} //End Corporation Sheet.
?>
