<?php require 'vendor/autoload.php';

use Aznoqmous\DB;

$db = new DB([
  'dbname' => 'db_test'
]);

$object = [
  'name' => 'jean charles',
  'description' => 'pihapzdh iaof iaon iaef iaef oaei ifba ifba eibpa piab pfaoef aopefn aôfb aôf aôf âoeb pihapzdh iaof iaon iaef iaef oaei ifba ifba eibpa piab pfaoef aopefn aôfb aôf aôf âoeb pihapzdh iaof iaon iaef iaef oaei ifba ifba eibpa piab pfaoef aopefn aôfb aôf aôf âoeb pihapzdh iaof iaon iaef iaef oaei ifba ifba eibpa piab pfaoef aopefn aôfb aôf aôf âoeb',
  'age' => 10,
  'gender' => 0
];

$db->createTable('object', $object);

$db->save($object);

foreach($db->findAll() as $object)
{
  $object->name = str_replace(' ', '-', $object->name);
  $db->save($object);
}
