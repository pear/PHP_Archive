<?php
//require_once 'PHP/Archive.php';
//require_once 'test.phar';
require_once 'PHP/Archive/Creator.php';
$creator = new PHP_Archive_Creator('index.php', true);
$creator->addFile(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'test1.php', 'index.php');
$creator->savePhar(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'test.phar');
?>