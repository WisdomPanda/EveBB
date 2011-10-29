<?php

/**
 * Minimum CAK mask.
 */
define('CAK_MASK', '33947656');

/**
 * Default 'OK' status.
 */
define('CAK_OK', 0);

/**
 * Validation Error Codes.
 */
define('CAK_NOT_INIT', 1);
define('CAK_VCODE_LEN', 2);
define('CAK_ID_NOT_NUM', 3);
define('CAK_BAD_VCODE', 4);
define('CAK_NULL_CHAR', 5);

/**
 * Mask Validation Error Codes.
 */
define('CAK_NOT_VALID', 1);
define('CAK_MASK_CLASH', 2);
define('CAK_BAD_FETCH', 3);
define('CAK_BAD_KEY', 4);
define('CAK_BAD_MASK', 5);
define('CAK_EXPIRE_SET', 6);
define('CAK_BAD_TYPE', 7);

/**
 * Houses a representation of the Custom Api Key's.
 *
 * Many of the things here are looking towards what might be required later, so ignore it if it seems to be useless.
 *
 */
class CAK {
	
	var $id;
	var $vcode;
	var $char_id;
	var $mask;
	var $type;
	var $keys_validated;
	var $mask_validated;
	
	var $base_url = 'http://api.eve-online.com';
	var $mask_url = '/account/APIKeyInfo.xml.aspx?keyID=%d&vCode=%s';
	
	function CAK($_id = 0, $_vcode = 0, $_char_id = 0) {
		$this->id = $_id;
		$this->vcode = $_vcode;
		$this->mask = 0;
		$this->char_id = $_char_id;
		$this->type = CAK_UNKNOWN;
		$this->keys_validated = false;
		$this->mask_validated = false;
	} //End Constructor(id,vcode).
	
	function get_auth() {
		return array('keyID' => $this->id, 'vCode' => $this->vcode, 'characterID' => $this->char_id);
	} //End get_auth().
	
	/**
	 * Checks the contents of id and vcode to ensure they are correct.
	 *
	 * @return Returns CAK_OK on success, or an error status on failure.
	 */
	function validate($validate_char = false) {
		
		if ($this->id == 0 || strlen($this->vcode) == 0) {
			$this->keys_validated = false;
			return CAK_NOT_INIT;
		} //End if.
		
		if ($validate_char && $this->char_id == 0) {
			return CAK_NULL_CHAR;
		} //End if.
		
		//We won't allow small vcodes.
		if (strlen($this->vcode) < 20 || strlen($this->vcode) > 64) {
			$this->keys_validated = false;
			return CAK_VCODE_LEN;
		} //End if.
		
		if (!check_numeric($this->id)) {
			$this->keys_validated = false;
			return CAK_ID_NOT_NUM;
		} //End if.
		
		if (!check_alpha_numeric($this->vcode)) {
			$this->keys_validated = false;
			return CAK_BAD_VCODE;
		} //End if.
		
		$this->keys_validated = true;
		return CAK_OK;
		
	} //End validate().
	
	/**
	 * Validate this CAK's mask.
	 *
	 * If the CAK does not have a mask associated with it, it will fetch it.
	 *
	 * @return Returns CAK_OK on success, or an associated error status on failure.
	 */
	function validate_mask($req_mask = null) {
		if (!$this->keys_validated) {
			if ($this->validate() != CAK_OK) {
				$this->mask_validated = false;
				return CAK_NOT_VALID;
			} //End if.
		} //End if.
		
		global $pun_config;
		
		if ($req_mask == null) {
			$req_mask = CAK_MASK;
		} //End if.
		
		/**
		 * Try and fetch the mask if it isn't there.
		 * Note that we aren't going to use XML here; the amount of data we want doesn't warrent the construction of a parser.
		 *
		 * Our target looks like this;
		 * <key accessMask="[0-9]+" type="[a-zA-Z]+" expires="[\s0-9:-]*">
		 *
		 * Note that thie 'expires' field can be null, we will expect this to be the case and reject expiring keys.
		 * (Users can delete the key from their API control panel.)
		 */
		if ($this->mask == 0) {
			global $pun_request;
			if (!$blob = $pun_request->get(sprintf($this->base_url.$this->mask_url, $this->id, $this->vcode))) {
				return CAK_BAD_FETCH;
			} //End if.
			
			//Try and find our key.
			if (preg_match('/<key accessMask="([0-9]+)" type="([a-zA-Z]+)" expires="([\s0-9:-]*)">/i', $blob, $matches) == 0) {
				return CAK_BAD_KEY;
			} //End if.
			
			//Is the mask good?
			if (intval($matches[1]) == 0) {
				return CAK_BAD_MASK; //0 set masks are useless.
			} //End if.
			
			//Have they set an expire date?
			if (strlen($matches[3]) > 0) {
				return CAK_EXPIRE_SET;
			} //End if.
			
			//What type of key is it?
			$temp = strtolower($matches[2]);
			if ($temp == "character") {
				$this->type = CAK_CHARACTER;
			} else if ($temp == "account") {
				$this->type = CAK_ACCOUNT;
			} else if ($temp == "corporation") {
				$this->type = CAK_CORPORATION;
			} else {
				$this->type = CAK_UNKNOWN;
			} //End if - else.
			
			//Is the type accepted?
			if (intval($pun_config['o_eve_cak_type']) > $this->type) {
				return CAK_BAD_TYPE;
			} //End if.
			
			//It's all good, lets get on with checking!
			$this->mask = $matches[1];
			
		} //End if.
		
		/**
		 * Now for the most important part; validation.
		 *
		 * The best way to do this is to use our mask ($req_mask) to check what values are enabled.
		 * If a flag is set, we then test to see if that flag is set in the CAK's mask.
		 * This means that any extra flags the client sets will be ignored safely, as we use our mask for comparisons.
		 *
		 * We start with 67,108,864, also known as 0b100000000000000000000000000.
		 * (The max value CCP is currently using.)
		 *
		 * Yes, this loop is a little weird. Go-go 32/64-bit support!
		 * If it's established as 32-bit safe now, it's just a matter of changing $i to support the full 64-bit range.
		 * intval() will return the "max_int" value, system dependant, instead of rolling over the value.
		 */
		//Set the scale we'll be working with.
		bscale(0);
	
		$i = '67108864';
		$temp_mask = $this->mask;
		while (intval($i) != 0) {
			
			//Is the flag set in our required mask?
			if (bdiv($req_mask, $i) == 1) {
				
				//Does the CAK's mask have that flag set?
				if (bdiv($temp_mask, $i) != 1) {
					//OMGAH, PANIC!
					$this->mask_validated = false;
					return CAK_MASK_CLASH;
				} //End if.
				
				//So far so good, adjust the masks and keep going.
				$req_mask = bsub($req_mask, $i);
				
			} //End if.
			
			if ($temp_mask >= $i) {
				$temp_mask = bsub($temp_mask, $i);
			} //End if.
			
			//Deincrement $i for the next pass.
			$i = bdiv($i, 2);
		} //End while loop.
		
		
		$this->mask_validated = true;
		
		return CAK_OK;
		
	} //End validate_mask().
	
} //End CAK.

?>