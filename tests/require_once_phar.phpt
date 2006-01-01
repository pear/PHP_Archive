--TEST--
Test running a .phar that requires a non-existent internal file [phar extension]
--SKIPIF--
<?php
if (!extension_loaded('phar')) {
    echo 'skip test needs phar extension';
}
?>
--FILE--
<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'phpt_test.php.inc';
ini_set('html_errors', 0);
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'require_once' . DIRECTORY_SEPARATOR .
    'require_once.phar';
echo 'tests done';
?>
--EXPECTF--
phar://require_once.phar/indexhooha.php
Warning: require_once(phar://require_once.phar/nosuchfile.php): failed to open stream: phar error: "nosuchfile.php" is not a file in phar "require_once.phar" in phar://require_once.phar/indexhooha.php on line 4

Fatal error: require_once(): Failed opening required 'phar://require_once.phar/nosuchfile.php' (include_path='%s') in phar://require_once.phar/indexhooha.php on line 4
