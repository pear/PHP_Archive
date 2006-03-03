--TEST--
Test statting an open .phar file handle [phar extension]
--SKIPIF--
<?php
if (version_compare(phpversion(), '5.0.0', '<')) {
    echo 'skip php5-only test';
}
if (!extension_loaded('phar')) { echo 'skip test needs phar extension'; }
?>
--FILE--
<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'phpt_test.php.inc';
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'longfilename' . DIRECTORY_SEPARATOR .
    'longphar.phar';
$fp = fopen('phar://longphar.phar/testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttest.php', 'r');
$phpunit = new PEAR_PHPTest(true);
$x = fstat($fp);
$phpunit->assertEquals(array (
  0 => 12,
  1 => -895164305,
  2 => 33060,
  3 => 1,
  4 => 0,
  5 => 0,
  6 => -1,
  7 => 47,
  8 => $x[8],
  9 => $x[8],
  10 => $x[8],
  11 => -1,
  12 => -1,
  'dev' => 12,
  'ino' => -895164305,
  'mode' => 33060,
  'nlink' => 1,
  'uid' => 0,
  'gid' => 0,
  'rdev' => -1,
  'size' => 47,
  'atime' => $x[8],
  'mtime' => $x[8],
  'ctime' => $x[8],
  'blksize' => -1,
  'blocks' => -1,
), $x, 'stat');
fclose($fp);
echo 'tests done';
?>
--EXPECT--
phar://longphar.phar/testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttest.phpstring(5) "hello"
tests done