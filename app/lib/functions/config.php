<?php
/**
 * Functions to read and write config data
 *
 * @package essence-cms
 * @since 0.1
 */


/**
 * Echoes or returns value of a parameter of the config object
 * 
 * @param  string  $param
 * @param  boolean $return
 * @since 0.1
 */
function getConfig($param, $return = false) {

  $json = new JSONParser(D . '/config.json', 'en-en');
  $r = (string) $json->stream->data[0]->{$param};

  if ($return) {
    return (isset($r) && !empty($r)) ? $r : false;
  } else {
    echo (isset($r) && !empty($r)) ? $r : '';
  }
}

?>
