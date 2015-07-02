--TEST--
Test running a .phar that requires a non-existent internal file
--INI--
phar.require_hash=Off
--SKIPIF--
<?php
if (extension_loaded('phar')) {
    echo 'skip test conflicts with phar extension';
}
if (!extension_loaded('zlib')) {
    echo 'skip zlib extension not installed';
}
?>
--FILE--
<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'setup.php';
ini_set('html_errors', 0);
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'require_once' . DIRECTORY_SEPARATOR .
    'require_once.phar';
echo 'tests done';
?>
--EXPECTF--
phar://require_once.phar/indexhooha.php
Fatal error: Error: "nosuchfile.php" is not a file in phar "require_once.phar" in %s on line %s