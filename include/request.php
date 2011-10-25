<?php

/**
 * Request class designed to wrap around cUrl or fopen(), depending on the situation.
 */
class Request {
	
	var $last_request;
	var $num_requests;
	
	/**
	 * Constructs a new request object.
	 */
	function Request() {
		$num_requests = 0;
		$last_request = array('url' => '', 'data' => array(), 'err' => '');
	} //End Constructor().
	
	/**
	 * Need to download a file of unknown length and don't want to load it *all* into memory? Look no further!
	 *
	 * fetch_file is designed to allow streaming of a remote file to a local file, like saving an image!
	 *
	 * @return Returns the number of bytes written, or false on failure.
	 */
	function fetch_file($url, $file) {
		if (defined('EVEBB_CURL')) {
			return $this->curl_fetch_file($url, $file);
		} //End if.
		
		return $this->fopen_fetch_file($url, $file);
	} //End fetch_file().
	
	function curl_fetch_file($url, $file) {
		$this->written = 0;
		
		if (!$this->fout = fopen($file, 'w')) {
			$this->last_request['err'] = 'Unable to open local file for writing.';
			return false;
		} //End if.
		
		$curl = curl_init($url);
		
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_POST, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($curl, CURLOPT_WRITEFUNCTION, array($this, 'curl_write_file'));
		
		if (!curl_exec($curl)) {
			$this->last_request['err'] = curl_error($curl);
		} //End if.
		
		curl_close($curl);
		
		fclose($this->fout);
		unset($this->fout);
		
		return $this->written;
	} //End curl_fetch_file().
	
	function curl_write_file($curlHandle, $data) {
		fwrite($this->fout, $data);
		$this->written += strlen($data);
		return strlen($data);
	} //End curl_write_file.
	
	function fopen_fetch_file($url, $file) {
		$written = 0;
		if (!$fin = @fopen($url, 'r')) {
			$this->last_request['err'] = 'Unable to open remote file for reading.';
			return false;
		} //End if.
		
		if (!$fout = fopen($file, 'w')) {
			$this->last_request['err'] = 'Unable to open local file for writing.';
			return false;
		} //End if.
		
		while(!feof($fin)) {
			$buffer = fread($fin, 1024);
			$written += strlen($buffer);
			fwrite($fout, $buffer);
		} //End if.
		
		fflush($fout);
		fclose($fout);
		fclose($fin);
		
		return $written;
	} //End function fopen_fetch_file().
	
	/**
	 * Used to 'get' a request to a webserver.
	 *
	 * Expects you to correctly format the url before use.
	 *
	 * @return Returns the response from the URL, or false on failure.
	 */
	function get($url) {
		if (defined('EVEBB_CURL')) {
			return $this->curl_get($url);
		} //End if.
		
		return $this->fopen_get($url);
	} //End get().
	
	/**
	 * cURL implementation for get()
	 *
	 * @return Returns the response from the URL, or false on failure.
	 */
	function curl_get($url) {
		
		$this->num_requests++;
		$this->last_request['url'] = $url;
		$this->last_request['data'] = array();
		$this->last_request['err'] = '';
		
		$curl = curl_init($url);
		
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_POST, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		
		if (!$response = curl_exec($curl)) {
			$this->last_request['err'] = curl_error($curl);
			$response =  false;
		} //End if.
		
		curl_close($curl);
		
		return $response;
	} //End curl_post().
	
	
	/**
	 * fopen implementation for get()
	 *
	 * @return Returns the response from the URL, or false on failure.
	 */
	function fopen_get($url) {
		
		$this->num_requests++;
		$this->last_request['url'] = $url;
		$this->last_request['data'] = array();
		$this->last_request['err'] = '';
		
		
		$context = stream_context_create();
		$file = @fopen($url, 'r', false, $context);
		
		if (!$file) {
			$this->last_request['err'] = 'Unable to open connection.';
			return false;
		} //End if.
		
		$response = stream_get_contents($file);
		fclose($file);
		
		return $response;
	} //End fopen_post().
	
	/**
	 * Used to 'post' a request to a webserver.
	 *
	 * Opens the url, reads the data, returns the blob.
	 *
	 * Note that the optional headers may be used at a later date.
	 *
	 * @return Returns the response from the URL, or false on failure.
	 */
	function post($url, $data = array(), $optional_headers = array()) {
		
		if (defined('EVEBB_CURL')) {
			return $this->curl_post($url, $data, $optional_headers);
		} //End if.
		
		return $this->fopen_post($url, $data, $optional_headers);
		
	} //End post().
	
	/**
	 * cURL implementation for post()
	 *
	 * @return Returns the response from the URL, or false on failure.
	 */
	function curl_post($url, $data = array(), $optional_headers = array()) {
		
		$data = http_build_query($data);
		str_replace('@', '', $data); //Just to be on the safe side.
		
		$this->num_requests++;
		$this->last_request['url'] = $url;
		$this->last_request['data'] = $data;
		$this->last_request['err'] = '';
		
		$curl = curl_init($url);
		
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		
		if (!$response = curl_exec($curl)) {
			$this->last_request['err'] = curl_error($curl);
			$response = false;
		} //End if.
		
		curl_close($curl);
		
		return $response;
	} //End curl_post().
	
	/**
	 * fopen implementation for post()
	 *
	 * @return Returns the response from the URL, or false on failure.
	 */
	function fopen_post($url, $data = array(), $optional_headers = array()) {
		
		$this->num_requests++;
		$this->last_request['url'] = $url;
		$this->last_request['data'] = $data;
		$this->last_request['err'] = '';
		
		$context;
		$file;
		
		if (count($data) == 0) {
			$context = stream_context_create();
		} else {
			$params = array('http' =>
					array(
						'method' => 'POST',
						'content' => str_replace('@', '', http_build_query($data))
					)
			);
			$context = stream_context_create($params);
		}  //End if - else.
		
		$file = @fopen($url, 'r', false, $context);
		
		if (!$file) {
			$this->last_request['err'] = 'Unable to open connection.';
			return false;
		} //End if.
		
		$response = stream_get_contents($file);
		fclose($file);
		
		return $response;
	} //End fopen_post().
	
} //End Request class.

?>