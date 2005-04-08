<?php

echo __FILE__;

require 'phar://test_phar.phar/test_root.php';
echo __LINE__ . "\n";

require 'phar://subdir/test_subdir.php';
echo __LINE__;

?>