<?php
/**
 * 31/01/2011
 * alliance_simple.php
 * Panda
 */

class Alliance {
	
	/**
	 * Because of the size of the list, we get this function to handle the DB side of things, otherwise it'd have a massive memory footprint.
	 */
	function update_list() {
		
		global $db;
		
		$url = 'http://api.eve-online.com/eve/AllianceList.xml.aspx';
		
		if (!$xml = post_request($url)) {
			return false;
		} //End if.
		
		if (!$list = simplexml_load_string($xml)) {
			if (defined('PUN_DEBUG')) {
				error(print_r(libxml_get_errors(), true), __FILE__, __LINE__, $db->error());
			} //End if.
			return false;
		} //End if.
		
		if (isset($list->error)) {
			if (defined('PUN_DEBUG')) {
				error($list->error, __FILE__, __LINE__, $db->error());
			} //End if.
			return false;
		} //End if.
		
		if (!$db->truncate_table('api_alliance_corps')) {
			if (defined('PUN_DEBUG')) {
					error("Unable to delete corps.", __FILE__, __LINE__, $db->error());
				} //End if.
			return false;
		} //End if.
		
		foreach ($list->result->rowset->row as $row) {
			//Need to use this in corp loop, so type cast it here so it's only type cast once.
			$id = (string)$row['allianceID'];
			$fields = array(
					'allianceID' => (string)$row['allianceID'],
					'name' => $db->escape((string)$row['name']),
					'shortName' => $db->escape((string)$row['shortName']),
					'memberCount' => (int)$row['memberCount'],
					'executorCorpID' => (int)$row['executorCorpID'],
					'startDate' => (string)$row['startDate']
				);
		
			if (!$db->insert_or_update($fields, 'allianceID', $db->prefix.'api_alliance_list')) {
				if (defined('PUN_DEBUG')) {
						error((string)$list->error."<br/>".$sql."<br/>".print_r($row, true)."<br/>", __FILE__, __LINE__, $db->error());
					} //End if.
				return false;
			} //End if.
		
			//Now we've inserted the alliance, we update the member corps.
			foreach ($row->rowset->row as $corp) {
				$sql = "INSERT INTO ".$db->prefix."api_alliance_corps(allianceID, corporationID, startDate) VALUES(".$id.", ".(string)$corp['corporationID'].", '".(string)$corp['startDate']."')";
				
				if (!$db->query($sql)) {
					if (defined('PUN_DEBUG')) {
						error("Unable to insert corp.<br/>".$sql."<br/>".print_r($corp, true)."<br/>", __FILE__, __LINE__, $db->error());
					} //End if.
					return false;
				} //End if.
				
			} //End foreach().
			
		} //End foreach().
		
		return true;
	} //End load_list().
	
} //End Alliance class.

?>