--TEST--
Test running a .phar that caches stat
--SKIPIF--
--FILE--
<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'phpt_test.php.inc';
require_once 'PHP/Archive.php';
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cachestat' . DIRECTORY_SEPARATOR .
    'cachestat.phar';
echo 'tests done';
?>
--EXPECT--
phar://cachestat.phar/test1.phpstring(5) "hello"
tests done