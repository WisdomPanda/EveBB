<?php
/**
 * 31/01/2011
 * corporation_simple.php
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
		
		$url = "http://api.eve-online.com/corp/CorporationSheet.xml.aspx";
		$corp_sheet;
		
		if (!$xml = $pun_request->post($url, array('corporationID' => $corpID))) {
			$_LAST_ERROR = API_BAD_REQUEST;
			return false;
		} //End if.
			
		if (!$corp_sheet = simplexml_load_string($xml)) {
			if (defined('PUN_DEBUG')) {
				error(print_r(libxml_get_errors(), true), __FILE__, __LINE__, $db->error());
			} //End if.
			return false;
		} //End if.
			
		if (isset($corp_sheet->error)) {
			if (defined('PUN_DEBUG')) {
				error($corp_sheet->error, __FILE__, __LINE__, $db->error());
			} //End if.
			$_LAST_ERROR = API_SERVER_ERROR;
			return false;
		} //End if.
		
		//Just the basics.
		$this->corporationID = (int)$corp_sheet->result->corporationID;
		$this->corporationName = $db->escape((string)$corp_sheet->result->corporationName);
		$this->ticker = $db->escape((string)$corp_sheet->result->ticker);
		$this->ceoID = (int)$corp_sheet->result->ceoID;
		$this->ceoName = $db->escape((string)$corp_sheet->result->ceoName);
		$this->description = $db->escape((string)$corp_sheet->result->description);
		$this->url = $db->escape((string)$corp_sheet->result->url);
		$this->allianceID = (int)$corp_sheet->result->allianceID;
		$this->taxRate = (float)$corp_sheet->result->taxRate;
		
		return true;
		
	} //End load_corp().
	
} //End Corporation Sheet.

?>