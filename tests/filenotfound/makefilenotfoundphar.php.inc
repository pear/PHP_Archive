<?php
require_once 'PHP/Archive/Creator.php';
chdir(dirname(__FILE__));
$creator = new PHP_Archive_Creator('index.php', false);
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'filenotfoundtest.php', 'index.php');
$creator->savePhar(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'filenotfound.phar');
@unlink($name);
?>