<?php

namespace Aznoqmous;

use PDO;

class DB {

  protected $options = [
    'host' => 'localhost',
    'port' => 3306,
    'dbType' => 'mysql',
    'user' => 'root',
    'password' => '',
    'charset' => 'utf8',
    'dbname' => '',
    'table' => '',
    'uniqueField' => 'id'
  ];

  protected $db = '';

  public function __construct($options=[])
  {
    $this->initDB($options);
  }

  public function initDB($options=[])
  {
    $this->options = array_merge($this->options, $options);
    foreach($this->options as $key => $value){
      $this->{$key} = $value;
    }

    $strDns = "{$this->dbType}:host={$this->host};port={$this->port};dbname={$this->dbname};charset={$this->charset}";

    try {
      $this->db = new PDO($strDns, $this->user, $this->password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false
      ]);
    }
    catch(PDOException $ex) {
      die("AZNOQMOUS\DB: FAILED TO CONNECT TO DATABASE");
    }
  }

  public function select($table)
  {
    $this->table = $table;
  }

  public function createTable($tableName, $obj)
  {
    $arrTypes = [
      ['longstring', 'string', 'boolean' ],
      ['text', 'varchar (255)', 'tinyint']
    ];
    $obj = (object) $obj;
    $fields = [];
    foreach($obj as $key => $value){
      $type = gettype($value);
      if($type == 'string' && strlen($value) > 255) $type = 'longstring';
      $type = str_replace($arrTypes[0], $arrTypes[1], $type);
      $fields[] = "$key $type DEFAULT NULL";
    }
    $fields = implode(', ', $fields);
    $createQuery = "CREATE TABLE $tableName ( {$this->uniqueField} INT( 11 ) AUTO_INCREMENT PRIMARY KEY, $fields )";

    try {
      $this->db->exec("DROP TABLE IF EXISTS $tableName");
      $res = $this->db->exec($createQuery);
    }
    catch(PDOException $e){
      die($e->getMessage());
    }

    $this->select($tableName);
  }

  /*
  * UPDATE
  */
  public function save($obj)
  {
    $new = false;
    if(!array_key_exists($this->uniqueField, $obj)) $new = true;
    else if(!$this->findById($obj->{$this->uniqueField})) $new = true;

    if($new) $this->insert($obj);
    else $this->update($obj);
  }

  public function insert($obj)
  {
    $keys = implode(', ', array_keys($obj));
    $values = array_values($obj);
    array_map(function($value){
      return "\"$value\"";
    }, $values);
    $values = implode('","', $values);
    $insertQuery = "INSERT INTO {$this->table} ({$keys}) VALUES (\"$values\")";
    dump($insertQuery);
    $res = $this->db->prepare($insertQuery);
    $res->execute();
  }
  public function update($obj)
  {
    $uniqueField = $this->uniqueField;
    $id = $obj->{$uniqueField};
    $updates = [];
    $arrValues = [];
    foreach($obj as $key => $value){
      if($key != $uniqueField && $key != 'condition') {
        $updates[] = "$key=?";
        $arrValues[] = $value;
      }
    }
    $arrValues[] = $id;

    $strUpdates = implode(', ', $updates);
    $query = "UPDATE {$this->table} SET $strUpdates WHERE {$uniqueField}=?";
    $res = $this->db->prepare($query);

    if(!$res) dump($this->db->errorInfo()[2]);

    return $res->execute($arrValues);
  }

  /*
  * SELECTS
  */
  public function findAll($limit=0, $offset=0)
  {
    $limitStr = ($limit)?"LIMIT $offset, $limit":'';
    $res = $this->db->prepare("SELECT * FROM {$this->table} WHERE 1 $limitStr");
    $res->execute();
    return $res->fetchAll(PDO::FETCH_CLASS);
  }

  public function findBy($key, $value)
  {
    $res = $this->db->prepare("SELECT * FROM {$this->table} WHERE $key = $value");
    $res->execute();
    return $res->fetchAll(PDO::FETCH_CLASS);
  }

  public function findMultipleBy($key, $values)
  {
    $res = [];
    foreach($values as $value){
      $res = array_merge($res, $this->findBy($key, $value));
    }
    return $res;
  }

  public function findById($value)
  {
    return $this->findBy($this->uniqueField, $value);
  }

  public function findByMatching($key, $pattern)
  {
    $res = $this->db->prepare("SELECT * FROM {$this->table} WHERE $key LIKE '$pattern'");
    $res->execute();
    return $res->fetchAll(PDO::FETCH_CLASS);
  }

}
