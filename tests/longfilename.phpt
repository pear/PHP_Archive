--TEST--
Test running a .phar that contains a long filename
--SKIPIF--
--FILE--
<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'phpt_test.php.inc';
require_once 'PHP/Archive.php';
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'longfilename' . DIRECTORY_SEPARATOR .
    'longphar.phar';
echo 'tests done';
?>
--EXPECT--
phar://testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttest.phpstring(5) "hello"
tests done