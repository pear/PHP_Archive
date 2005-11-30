--TEST--
Test seeking a .phar stream
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
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'longfilename' . DIRECTORY_SEPARATOR .
    'longphar.phar';
$phpunit = new PEAR_PHPTest(true);
$fp = fopen('phar://longphar.phar/testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttest.php', 'r');
var_dump(ftell($fp));
var_dump(fread($fp, 2));
fseek($fp, 3);
var_dump(ftell($fp));
var_dump(fread($fp, 2));
var_dump(ftell($fp));
fseek($fp, 0, SEEK_END);
var_dump(ftell($fp));
fseek($fp, -1, SEEK_END);
var_dump(ftell($fp));
fseek($fp, -61, SEEK_END);
var_dump(ftell($fp));
fseek($fp, -1, SEEK_CUR);
var_dump(ftell($fp));
fseek($fp, 20, SEEK_CUR);
var_dump(ftell($fp));
fseek($fp, 1, SEEK_END);
var_dump(ftell($fp));
fclose($fp);
echo 'tests done';
?>
--EXPECT--
phar://longphar.phar/testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttest.phpstring(5) "hello"
int(0)
string(2) "<?"
int(3)
string(2) "hp"
int(5)
int(47)
int(46)
int(46)
int(45)
int(65)
int(48)
tests done