--TEST--
Test opendir()-related functionality on a .phar, also is_dir()/is_file()
--SKIPIF--
--FILE--
<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'phpt_test.php.inc';
require_once 'PHP/Archive.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'opendir' . DIRECTORY_SEPARATOR .
    'opendir.phar';
$dir = opendir('phar://opendir.phar/');
$result = array();
while (false !== ($file = readdir($dir))) {
    $result[$file] = array('f' => is_file('phar://opendir.phar/' . $file),
        'd' => is_dir('phar://opendir.phar/' . $file));
}
$phpunit = new PEAR_PHPTest(true);
$phpunit->assertEquals(array (
  'cachestat' => 
  array (
    'f' => false,
    'd' => true,
  ),
  'cachestat.phpt' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'cachestattest.php' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'eof.phpt' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'filenotfound' => 
  array (
    'f' => false,
    'd' => true,
  ),
  'filenotfound.phpt' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'filenotfoundtest.php' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'gopearphar.php.inc' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'indexhooha.php' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'longfilename' => 
  array (
    'f' => false,
    'd' => true,
  ),
  'longfilename.phpt' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'makepearphar.php.inc' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'normalstat.phpt' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'opendir' => 
  array (
    'f' => false,
    'd' => true,
  ),
  'opendir.phpt' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'pearindex.php' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'phar.log' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'phpt_test.php.inc' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'planet_php' => 
  array (
    'f' => false,
    'd' => true,
  ),
  'savetest.php' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'seek.phpt' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'streamstat.phpt' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'test.php' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'test1.php' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'test_path.php' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'test_tar.tar' => 
  array (
    'f' => true,
    'd' => false,
  ),
)
, $result, 'result');
closedir($dir);
echo 'tests done';
?>
--EXPECT--
phar://opendir.phar/index.phpstring(5) "hello"
tests done