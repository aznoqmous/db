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
    'database' => '',
    'table' => '',
    'uniqueField' => 'id',
    'lock' => false // prevent from insert / update but allow selects
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

    $strDns = "{$this->dbType}:host={$this->host};port={$this->port};dbname={$this->database};charset={$this->charset}";

    try {
      $this->db = new PDO($strDns, $this->user, $this->password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false
      ]);
    }
    catch(PDOException $ex) {
      die("Aznoqmous\DB: Failed to connect to database");
    }
  }

  /*
  * BULK ACTIONS
  */
  public function empty($table=false)
  {
    if($this->lock) die('Aznoqmous\Db: save prevented by lock: true');
    $table = ($table)?: $this->table;
    $res = $this->db->prepare("TRUNCATE TABLE $table");
    return $res->execute();
  }

  /*
  * STRUCTURE
  */
  public function getSchema($table=false)
  {
    $database = $this->database;
    $table = ($table) ?: $this->table;
    $res = $this->db->prepare("SELECT COLUMN_NAME as field, IS_NULLABLE as nullable, COLUMN_DEFAULT as default_value, COLUMN_TYPE as type FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='$table' AND TABLE_SCHEMA='$database'");
    $res->execute();
    return $res->fetchAll(PDO::FETCH_CLASS);
  }

  public function copy($db, $table=false)
  {
    $this->createTableFromSchema($db->getSchema($table));
    foreach( $db->findAll() as $element){
      $this->save($element);
    }
  }
  public function copySchema($db, $table=false)
  {
    $this->createTableFromSchema($db->getSchema($table));
  }

  public function createTableFromSchema($schema, $table=false)
  {
    $table = ($table)?: $this->table;
    $fields = [];
    foreach($schema as $field){
      $field = (object) $field;
      $field->default = (array_key_exists('default_value', $field))?$field->default_value: $field->default;
      $default = ($field->default)?: 'null';
      if($field->field != $this->uniqueField) $fields[] = "{$field->field} {$field->type} DEFAULT $default";
    }
    $fields = implode(', ', $fields);
    $createQuery = "CREATE TABLE $table ( {$this->uniqueField} INT( 11 ) AUTO_INCREMENT PRIMARY KEY, $fields )";

    try {
      $this->db->exec("DROP TABLE IF EXISTS $table");
      $res = $this->db->exec($createQuery);
    }
    catch(PDOException $e){
      die($e->getMessage());
    }

    return $res;
  }

  public function createTableFromObject($tableName, $obj)
  {
    if($this->lock) die('Aznoqmous\DB: save prevented by lock: true');
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
      if($key != $this->uniqueField) $fields[] = "$key $type DEFAULT NULL";
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

  public function select($table)
  {
    $this->table = $table;
  }

  /*
  * UPDATE
  */
  public function save($obj)
  {
    $obj = (object) $obj;
    if($this->lock) die('Aznoqmous\Db: save prevented by lock: true');
    $new = false;
    if(!array_key_exists($this->uniqueField, $obj)) $new = true;
    else if(!$this->findById($obj->{$this->uniqueField})) $new = true;

    if($new) $this->insert($obj);
    else $this->update($obj);
  }

  public function insert($obj)
  {
    if($this->lock) die('Aznoqmous\Db: save prevented by lock: true');
    $obj = (array) $obj;
    $updates = [];
    $arrValues = [];
    $arrKeys = array_keys($obj);
    foreach($obj as $key => $value){
      $arrReplacements[] = "?";
      $arrValues[] = $value;
    }

    $keys = implode(', ', $arrKeys);
    // $values = implode(', ' $arrValues);
    $replacements = implode(', ', $arrReplacements);

    $insertQuery = "INSERT INTO {$this->table} ($keys) VALUES ($replacements)";

    $res = $this->db->prepare($insertQuery);
    $res->execute($arrValues);
  }

  public function update($obj)
  {
    if($this->lock) die('Aznoqmous\Db: save prevented by lock: true');
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

  public function findOneById($value)
  {
    $res = $this->findBy($this->uniqueField, $value);
    return (count($res))? $res[0] : false;
  }

  public function findByMatching($key, $pattern)
  {
    $res = $this->db->prepare("SELECT * FROM {$this->table} WHERE $key LIKE '$pattern'");
    $res->execute();
    return $res->fetchAll(PDO::FETCH_CLASS);
  }

}
