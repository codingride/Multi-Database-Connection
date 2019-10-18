<?php
/* 
* Copyright (C) 2017 Abdullah Alghamdi - All Rights Reserved
* 
* You can use this code based on the licenes attaached in README file
* If you have any question, you can reach me at
* codingride@gmail.com
*/

  //handeling database connections
  class database {

    //holding the connection value
    private $db_connect;
    private $db_connect_xb;

    //selecting the required database
    private $db_select;
    private $db_select_xb;

    private $magic_quotes_active;

    private $real_escape_string_exists;

    private $charset;

    public $table;

    function __construct($multi = false) {

      $this->db_connect($multi);
      $this->magic_quotes_active = get_magic_quotes_gpc();
      $this->real_escape_string_exists = function_exists( "mysqli_real_escape_string" );

    }

    //connect with the database
    private function db_connect($multi) {
      // DB_HOST, DB_USER, DB_PASS, etc.. are defined in another file
      // Use your own configuration instead
      if($multi) {
        $this->db_connect = mysqli_connect(DB_HOST , DB_USER , DB_PASS , DB_NAME);
        $this->db_connect_xb = mysqli_connect(XB_DB_HOST , XB_DB_USER , XB_DB_PASS , XB_DB_NAME);
      } else {
        $this->db_connect = mysqli_connect(DB_HOST , DB_USER , DB_PASS , DB_NAME);
      }

    }

    public function secure_value($value , $multi = false) {

      if($this->real_escape_string_exists) { // PHP v4.3.0 or higher

        // undo any magic quote effects so mysqli_real_escape_string can do the work
        if($this->magic_quotes_active) {
          $value = stripslashes($value);
        }

        if($multi) {
          $connect = $this->db_connect_xb;
        } else {
          $connect = $this->db_connect;
        }

        $value = mysqli_real_escape_string($connect,$value);
        $value = preg_replace("![\][xX]([A-Fa-f0-9]{1,3})!", "",$value);
        $value = htmlentities($value , ENT_QUOTES , ENTITY);

      } else { // before PHP v4.3.0

        // if magic quotes aren't already on then add slashes manually
        if(!$this->magic_quotes_active) {

          $value = addslashes($value);
          $value = preg_replace("![\][xX]([A-Fa-f0-9]{1,3})!", "",$value);
          $value = htmlentities($value , ENT_QUOTES , ENTITY);

        }
        // if magic quotes are active, then the slashes already exist
      }

      return $value;

    }

    public function read_value($value) {

      $value = stripslashes($value);
      $value = html_entity_decode($value , ENT_QUOTES , ENTITY);

      return $value;

    }

    public function query($sql , $multi = false) {

      $this->charset = CHARSET;

      if($multi) {
        $connect = $this->db_connect_xb;
      } else {
        $connect = $this->db_connect;
      }

      mysqli_query($connect,"SET CHARACTER SET '$this->charset'");
      mysqli_query($connect,"SET collation_connection = '" . $this->charset . "_general_ci'");

      $result = mysqli_query($connect,$sql);

      if (!$result) {
        $output = "Database query failed: " . mysqli_error($connect) . "<br /><br />";// . $sql;

        exit( $output );

      }

      return $result;

    }

    public function result_array($result_set) {

      return mysqli_fetch_assoc($result_set);

    }

    public function num_rows($result_set) {

      return mysqli_num_rows($result_set);

    }

    public function insert_id($multi = false) {

      if($multi) {
        $connect = $this->db_connect_xb;
      } else {
        $connect = $this->db_connect;
      }

      return mysqli_insert_id($connect);

    }

    public function all_results($sql , $multi = false) {

      return $this->query_result($sql , $multi);

    }

    // Get only one result out of any table
    public function one_result($sql , $multi = false) {

      $result_array = $this->query_result($sql , $multi);

      return !empty($result_array) ? array_shift($result_array) : false;

    }

    // query the database using specefec sql statment
    public function query_result($sql , $multi) {

      $result_set = $this->query($sql , $multi);
      $result_array = array();

      while ($row = $this->result_array($result_set)) {
        $result_array[] = $row;
      }

      return $result_array;

    }

    // Get field names out of any table
    public function get_field_names($table) {

      $field_set = $this->query("SELECT * FROM $table LIMIT 1");
      $field_num = mysqli_num_fields($field_set);
      $field_names = array();

      for($i = 0; $i < $field_num; $i++) {
        $field_names[] = mysqli_field_name($field_set, $i);
      }

      return $field_names;

    }

    public function count_all($sql) {

      $result_set = $this->query($sql);
      $row = mysqli_num_rows($result_set);

      return $row;

    }


    //close database connection when required
    public function db_close($multi = false) {

      if(isset($this->db_connect)) {
        mysqli_close($this->db_connect);
        unset($this->db_connect);
      } elseif (isset($this->db_connect_xb)) {
        mysqli_close($this->db_connect_xb);
        unset($this->db_connect_xb);
      }

    }

  }

?>
