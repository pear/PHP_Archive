--TEST--
Test statting a .phar [phar extension]
--SKIPIF--
<?php
if (version_compare(phpversion(), '5.0.0', '<')) {
    echo 'skip php5-only test';
}
if (!extension_loaded('phar')) { echo 'skip phar extension conflicts with this test'; }
?>
--FILE--
<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'phpt_test.php.inc';
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'longfilename' . DIRECTORY_SEPARATOR .
    'longphar.phar';
$phpunit = new PEAR_PHPTest(true);
$x = stat('phar://longphar.phar/testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttest.php');
$phpunit->assertEquals(array (
  0 => 12,
  1 => -895164305,
  2 => 33060,
  3 => 1,
  4 => 0,
  5 => 0,
  6 => -1,
  7 => 43,
  8 => $x[8],
  9 => $x[8],
  10 => $x[8],
  11 => 0,
  12 => 0,
  'dev' => 12,
  'ino' => -895164305,
  'mode' => 33060,
  'nlink' => 1,
  'uid' => 0,
  'gid' => 0,
  'rdev' => -1,
  'size' => 43,
  'atime' => $x[8],
  'mtime' => $x[8],
  'ctime' => $x[8],
  'blksize' => 0,
  'blocks' => 0,
), $x, 'stat');
echo 'tests done';
?>
--EXPECT--
phar://longphar.phar/testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttest.phpstring(5) "hello"
tests done