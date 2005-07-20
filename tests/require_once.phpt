--TEST--
Test running a .phar that requires a non-existent internal file
--SKIPIF--
--FILE--
<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'phpt_test.php.inc';
require_once 'PHP/Archive.php';
ini_set('html_errors', 0);
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'require_once' . DIRECTORY_SEPARATOR .
    'require_once.phar';
echo 'tests done';
?>
--EXPECTF--
phar://require_once.phar/indexhooha.php
Fatal error: Error: "nosuchfile.php" not found in phar "require_once.phar" in %s on line 355
