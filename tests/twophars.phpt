--TEST--
Test including 2 .phars
--SKIPIF--
<?php
if (version_compare(phpversion(), '5.0.0', '<')) {
    echo 'skip php5-only test';
}
?>
--FILE--
<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'opendir' . DIRECTORY_SEPARATOR .
    'opendir.phar';
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'longfilename' . DIRECTORY_SEPARATOR .
    'longphar.phar';
echo 'tests done';
?>
--EXPECT--
phar://opendir.phar/indexhooha.phpstring(5) "hello"
phar://longphar.phar/testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttest.phpstring(5) "hello"
tests done