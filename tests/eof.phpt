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
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cachestat' . DIRECTORY_SEPARATOR .
    'cachestat.phar';
$phpunit = new PEAR_PHPTest(true);
$fp = fopen('phar://cachestat.phar/test1.php', 'r');
var_dump(feof($fp));
fseek($fp, 10000);
var_dump(feof($fp));
fclose($fp);
echo 'tests done';
?>
--EXPECT--
phar://cachestat.phar/test1.phpstring(5) "hello"
bool(false)
bool(true)
tests done