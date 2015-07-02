--TEST--
Test opendir()-related functionality on a .phar, also is_dir()/is_file()
--INI--
phar.require_hash=Off
--SKIPIF--
<?php
if (!extension_loaded('zlib')) {
    echo 'skip zlib extension not installed';
}
?>
--FILE--
<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'setup.php';
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
  'filenotfound_phar.phpt' => 
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
  'normalstat_phar.phpt' => 
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
  'require_once' => 
  array (
    'f' => false,
    'd' => true,
  ),
  'require_once.phpt' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'require_once_phar.phpt' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'seek.phpt' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'seek_phar.phpt' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'streamstat.phpt' => 
  array (
    'f' => true,
    'd' => false,
  ),
  'streamstat_phar.phpt' => 
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
--EXPECTF--
phar:/%sopendir.phar/indexhooha.phpstring(5) "hello"
tests done