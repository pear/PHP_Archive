--TEST--
Test seeking a .phar stream
--SKIPIF--
<?php
if (version_compare(phpversion(), '5.0.0', '<')) {
    echo 'skip php5-only test';
}
if (extension_loaded('phar')) { echo 'skip test not compatible with phar extension'; }
?>
--FILE--
<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'phpt_test.php.inc';
require_once 'PHP/Archive.php';
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'longfilename' . DIRECTORY_SEPARATOR .
    'longphar.phar';
$phpunit = new PEAR_PHPTest(true);
$fp = fopen('phar://longphar.phar/testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttest.php', 'r');
$phpunit->assertEquals(0, ftell($fp), 'first seek 0');
$phpunit->assertEquals('<?', fread($fp, 2), 'first read');
fseek($fp, 3);
$phpunit->assertEquals(3, ftell($fp), 'second seek 3');
$phpunit->assertEquals('hp', fread($fp, 2), 'second read');
$phpunit->assertEquals(5, ftell($fp), 'after second read');
fseek($fp, 0, SEEK_END);
$phpunit->assertEquals(47, ftell($fp), 'third seek 0 SEEK_END');
fseek($fp, -1, SEEK_END);
$phpunit->assertEquals(46, ftell($fp), 'fourth seek -1 SEEK_END');
fseek($fp, -61, SEEK_END);
$phpunit->assertEquals(46, ftell($fp), 'fifth seek -61 SEEK_END');
fseek($fp, -1, SEEK_CUR);
$phpunit->assertEquals(45, ftell($fp), 'sixth seek -1 SEEK_CUR');
fseek($fp, 20, SEEK_CUR);
$phpunit->assertEquals(65, ftell($fp), 'seventh seek 20 SEEK_CUR');
fseek($fp, 1, SEEK_END);
$phpunit->assertEquals(48, ftell($fp), 'eighth seek 1 SEEK_END');
fclose($fp);
echo 'tests done';
?>
--EXPECT--
phar://longphar.phar/testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttest.phpstring(5) "hello"
tests done