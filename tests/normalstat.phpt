--TEST--
Test statting a .phar
--SKIPIF--
<?php
if (version_compare(phpversion(), '5.0.0', '<')) {
    echo 'skip php5-only test';
}
?>
--FILE--
<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'phpt_test.php.inc';
require_once 'PHP/Archive.php';
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cachestat' . DIRECTORY_SEPARATOR .
    'cachestat.phar';
$phpunit = new PEAR_PHPTest(true);
$phpunit->assertEquals(array (
  0 => 0,
  1 => 0,
  2 => 33060,
  3 => 0,
  4 => 0,
  5 => 0,
  6 => 0,
  7 => 47,
  8 => 0,
  9 => 0,
  10 => 0,
  11 => -1,
  12 => -1,
  'dev' => 0,
  'ino' => 0,
  'mode' => 33060,
  'nlink' => 0,
  'uid' => 0,
  'gid' => 0,
  'rdev' => 0,
  'size' => 47,
  'atime' => 0,
  'mtime' => 0,
  'ctime' => 0,
  'blksize' => -1,
  'blocks' => -1,
), stat('phar://cachestat.phar/test1.php'), 'stat');
echo 'tests done';
?>
--EXPECT--
phar://cachestat.phar/test1.phpstring(5) "hello"
tests done