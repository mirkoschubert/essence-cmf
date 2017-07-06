<?php
/**
* Class for managing JSON Files
* Reads, writes, deteletes data of a file.
*
* @version 0.2
* @package SiteParser
* @since 0.1
**/

/**
* HOW TO USE THE JSONParser CLASS
*
* OPEN OR CREATE A JSON FILE
* There is an optional language variable for creating multi-language JSON files.
*
* $json = new JSONParser('filename.json', 'en-en');
*
* CREATE A NEW ENTRY
* The 'name' variable must be set as an identification
*
* $brian = new stdClass();
* $brian->name = 'brian';
* $brian->city = 'memphis';
* $json->appendEntry($brian);
*
* EDIT AN EXISTING ENTRY
*
* $brian = selectEntryByName('brian');
* unset($brian->city);
* $brian->surname = 'walker';
* $json->updateEntry($brian);
*
* DELETE AN ENTRY
*
* $json->deleteEntry('brian');
*
* ACCESS JSON STREAM DIRECTLY
* For API porposes there are two variables:
*
* $json->stream
* Full JSON Stream with language, created and updated dates and status messages.
*
* $json->data
* Data stream to manipulate the JSON data directly.
*
**/


class JSONParser {

	public $file = ''; // actual open file
	public $stream; // JSON Full stream
	public $data = array(); // JSON Data - change here!


	public function __construct($file, $language = 'de-de') {

		if (file_exists($file)) $this->loadJSON($file); else $this->createJSON($file, $language);

	}


	/**
	* Loads the whole JSON file into streams
	*
	* @since 0.1
	* @param (string)$file
	**/
	private function loadJSON($file) {

		if (file_exists($file)) {
			$fh = fopen($file, 'r');
			if (!$fh) {
				$this->updateStatus('400');
				return;
			} else {
				$json = fread($fh, filesize($file));
				$this->file = $file;
				$this->stream = json_decode($json);
				$this->data = $this->stream->data;
				$this->updateStatus('200');
				fclose($fh);
			}
		} else $this->updateStatus('404');
	}


	/**
	* Creates a fresh JSON file
	*
	* @since 0.1
	* @param (string)$file, (string)$language
	**/
	private function createJSON($file, $language = 'de-de') {

		if (!file_exists($file)) {
			$fh = fopen($file, 'w');
			if (!$fh) {
				$this->updateStatus('400');
				$return;
			} else {
				$this->file = $file;
				$this->stream = new stdClass();
				$this->stream->data = $this->data = array();
				$this->stream->language = $language;
				$this->stream->created = date('c');
				fwrite($fh, json_encode($this->stream, JSON_PRETTY_PRINT)); // -> ab PHP 5.4
				//fwrite($fh, json_encode($this->stream));
				$this->updateStatus('200');
				fclose($fh);
			}
		}
	}

	/**
	* Saves the actual JSON file from the stream variable
	*
	* @since 0.1
	**/
	private function saveJSON() {

		if (file_exists($this->file)) {
			$fh = fopen($this->file, 'w');
			if (!$fh) {
				$this->updateStatus('400');
				return;
			} else {
				$this->cleanStatus();
				$this->stream->data = $this->data;
				$this->stream->updated = date('c');
				fwrite($fh, json_encode($this->stream, JSON_PRETTY_PRINT)); // -> ab PHP 5.4
				//fwrite($fh, json_encode($this->stream));
				$this->updateStatus('200');
				fclose($fh);
			}
		} else $this->updateStatus('404');
	}

	/**
	* Searches the data stream for a specific entry by name
	*
	* @since 0.1
	* @param (string)$name
	* @return (object)
	**/
	public function selectEntryByName($name) {

		$selected = new stdClass();
		foreach ($this->data as $key => $value) {
			if ($value->name == $name) $selected = $this->data[$key];
		}
		return $selected;
	}

	// TO-DO: selects one Entry which has all variables
	function selectEntry($vars = array()) {

	}

	// TO-DO: searches the JSON data for variables (multi-output)
	function searchEntry($search = array()) {

	}

	/**
	* Updates an entry and saves it
	*
	* @since 0.1
	* @param (object)$data
	**/
	public function updateEntry($data) {

		if (!empty($data)) {
			foreach ($this->data as $key => $value) {
				if ($value->name == $data->name) $this->data[$key] = (object) $data;
			}
			$this->saveJSON();
		}
	}

	/**
	* Appends an entry in the data stream and saves it
	*
	* @since 0.1
	* @param (object)$data
	**/
	public function appendEntry($data) {

		if (!empty($data)) {
			$this->data[] = (object) $data;
			$this->saveJSON();
		}
	}

	/**
	* Deletes an entry with a specific name from stream and file
	*
	* @since 0.1
	* @param (string)$name
	**/
	public function deleteEntry($name) {

		if ($name != '') {
			foreach ($this->data as $key => $value) {
				if ($value->name == $name) $found = $key;
			}
			if ($found) {
				unset($this->data[$found]);
				$this->saveJSON();
			}
		}
	}


	/**
	* Cleans the status in JSON stream for saving purposes
	*
	* @since 0.1
	**/
	private function cleanStatus() {

		unset($this->stream->status_code);
		unset($this->stream->status_txt);
	}

	/**
	* Error Handling: Sets the status or errors in the global JSON stream. This will nit be saved!
	*
	* @since 0.1
	* @param (string)$status
	**/
	private function updateStatus($status) {

		// errorcodes
		$errorcodes = array(
				'200' => 'OK',
				'400' => 'FILE_BROKEN',
				'404' => 'FILE_NOT_FOUND'
			);

		if (!empty($this->stream)) {
			$this->stream->status_code = $status;
			$this->stream->status_txt = $errorcodes[$status];
		} else {
			$this->stream = new stdClass();
			$this->stream->data = null;
			$this->stream->status_code = $status;
			$this->stream->status_txt = $errorcodes[$status];
		}
	}

	/**
	 * Error Handling: Gets the status from the global JSON stream
	 *
	 * @since 0.2
	 * @return (mixed) $status_code or false
	 */
	public function getStatus() {

		return (isset($this->stream->status_code)) ? $this->stream->status_code : false;
	}

	/**
	* Helper Function: Converts multidimensional arrays into a JSON compatible object
	*
	* @since 0.1
	* @param (array)$array
	* @return (object)$array
	**/
	private function arrayToObject( $array ){
	  foreach( $array as $key => $value ){
	    if( is_array( $value ) ) $array[ $key ] = arrayToObject( $value );
	  }
	  return (object) $array;
	}

}



?>
