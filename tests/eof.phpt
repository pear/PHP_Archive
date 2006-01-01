--TEST--
Test seeking a .phar stream
--SKIPIF--
<?php
if (version_compare(phpversion(), '5.1.0b1', '<')) {
    echo 'skip php5-only test';
}
?>
--FILE--
<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'phpt_test.php.inc';
if (!class_exists('PHP_Archive')) {
    // support phar extension
    require_once 'PHP/Archive.php';
}
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'longfilename' . DIRECTORY_SEPARATOR .
    'longphar.phar';
$phpunit = new PEAR_PHPTest(true);
$fp = fopen('phar://longphar.phar/testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttest.php', 'r');
var_dump(feof($fp), ftell($fp));
fseek($fp, filesize('phar://longphar.phar/testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttest.php'));
fread($fp, 2);
var_dump(feof($fp), ftell($fp));
fclose($fp);
echo 'tests done';
?>
--EXPECT--
phar://longphar.phar/testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttest.phpstring(5) "hello"
bool(false)
int(0)
bool(true)
int(43)
tests done