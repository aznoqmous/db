# aznoqmous DB

## Usage

```php
// initialize
$db = new DB([
  'host' => 'localhost',
  'port' => 3306,
  'dbType' => 'mysql',
  'user' => 'root',
  'password' => '',
  'charset' => 'utf8',
  'dbname' => '',
  'table' => '',
  'uniqueField' => 'id'
]);

// basic initialize (will work in most case)
$db = new DB([
  'user' => '',
  'password' => '',
  'dbname' => '',
  'table' => ''
]);

// Get elements as array of objects
$db->findBy('key', $value);
$db->findAll();
$db->findMultipleBy('key', $arrValues);
$db->findByMatching('key', '%search%');

// Save a modified object to the db
$db->save($obj);

```
