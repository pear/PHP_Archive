<?php
PHP_Archive::cacheStat('cachestat.phar');
$a = file_get_contents('phar://cachestat.phar/test1.php');
require_once 'phar://cachestat.phar/test1.php';
?>