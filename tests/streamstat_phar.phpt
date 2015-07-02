--TEST--
Test statting an open .phar file handle [phar extension]
--INI--
phar.require_hash=Off
--SKIPIF--
<?php
if (version_compare(phpversion(), '5.0.0', '<')) {
    echo 'skip php5-only test';
}
if (!extension_loaded('phar')) { echo 'skip test needs phar extension'; }
?>
--FILE--
<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'setup.php';
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'longfilename' . DIRECTORY_SEPARATOR .
    'longphar.phar';
$fp = fopen('phar://longphar.phar/testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttest.php', 'r');
$phpunit = new PEAR_PHPTest(true);
$x = fstat($fp);
$phpunit->assertEquals(array (
  0 => 12,
  1 => $x[1],
  2 => $x[2],
  3 => 1,
  4 => 0,
  5 => 0,
  6 => -1,
  7 => 43,
  8 => $x[8],
  9 => $x[8],
  10 => $x[8],
  11 => $x[11],
  12 => $x[12],
  'dev' => 12,
  'ino' => $x[1],
  'mode' => $x[2],
  'nlink' => 1,
  'uid' => 0,
  'gid' => 0,
  'rdev' => -1,
  'size' => 43,
  'atime' => $x[8],
  'mtime' => $x[8],
  'ctime' => $x[8],
  'blksize' => $x[11],
  'blocks' => $x[12],
), $x, 'stat');
fclose($fp);
echo 'tests done';
?>
--EXPECTF--
phar://%slongphar.phar/testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttest.phpstring(5) "hello"
tests done