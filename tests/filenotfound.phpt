--TEST--
Test running a .phar with missing file
--SKIPIF--
<?php
if (extension_loaded('phar')) { echo 'skip'; }
?>
--FILE--
<?php
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
Error: "test2.php" is not a file in phar "filenotfound.phar"
tests done