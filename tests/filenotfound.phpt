--TEST--
Test running a .phar that caches stat
--SKIPIF--
--FILE--
<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'phpt_test.php.inc';
require_once 'PHP/Archive.php';
function myhand($e, $s)
{
    if ($e == E_STRICT) return;
    if (strpos($s, '"test2.php'))
    echo $s . "\n";
}
set_error_handler('myhand');
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'filenotfound' . DIRECTORY_SEPARATOR .
    'filenotfound.phar';
echo 'tests done';
?>
--EXPECT--
Error: "test2.php" not found in phar "cachestat.phar"
tests done