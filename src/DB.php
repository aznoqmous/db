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
    $this->options = array_merge($this->options, $options);

    $strDns = "{$this->options['dbType']}:host={$this->options['host']};port={$this->options['port']};dbname={$this->options['dbname']};charset={$this->options['charset']}";

    try {
      $this->db = new PDO($strDns, $this->options['user'], $this->options['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false
      ]);
    }
    catch(PDOException $ex) {
      die(json_encode(array('outcome' => false)));
    }
  }

  public function save($obj)
  {
    $uniqueField = $this->options['uniqueField'];
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
    $query = "UPDATE {$this->options['table']} SET $strUpdates WHERE {$uniqueField}=?";
    $res = $this->db->prepare($query);

    if(!$res) dump($this->db->errorInfo()[2]);

    return $res->execute($arrValues);
  }

  public function findAll($limit=0, $offset=0)
  {
    $limitStr = ($limit)?"LIMIT $offset, $limit":'';
    $res = $this->db->prepare("SELECT * FROM product WHERE 1 $limitStr");
    $res->execute();
    return $res->fetchAll(PDO::FETCH_CLASS);
  }

  public function findBy($key, $value)
  {
    $res = $this->db->prepare("SELECT * FROM product WHERE $key = $value");
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
    return $this->findBy($this->options['uniqueField'], $value);
  }

  public function findByMatching($key, $pattern)
  {
    $res = $this->db->prepare("SELECT * FROM product WHERE $key LIKE '$pattern'");
    $res->execute();
    return $res->fetchAll(PDO::FETCH_CLASS);
  }

}
