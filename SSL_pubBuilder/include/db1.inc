<?php
require_once "adodb_lite/adodb.inc.php";
require_once "adodb_lite/adodb-exceptions.inc.php";

class PubBuilder_DB {
  function _get_connection() {
    static $_db_connection;
    
    if (!is_object($_db_connection)) {
      try {
        $_db_connection = ADONewConnection('mysqli');
        $connect_result = $_db_connection->Connect('localhost', 'root', '', 'pubbuilder');
      } catch (exception $ex) {
        die('<pre>' . $ex . '</pre>');
      }          
    }
    
    return $_db_connection;
  }
  
  function query($query) {
    try {
      $db_connection = PubBuilder_DB::_get_connection();
            
      return $db_connection->Execute($query);
    } catch (exception $ex) {
      die('<pre>' . $ex . '</pre>');
    }    
  }

  function qstr($str) {
    return PubBuilder_DB::_get_connection()->qstr($str);
  }

  function lastID() {	
    return PubBuilder_DB::_get_connection()->Insert_ID();
  }
}

$_SERVER['PHP_AUTH_USER'] = 'edekker';

?>
