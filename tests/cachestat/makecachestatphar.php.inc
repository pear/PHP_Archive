<?php
require_once 'PHP/Archive/Creator.php';
chdir(dirname(__FILE__));
$creator = new PHP_Archive_Creator('index.php', false);
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'cachestattest.php', 'index.php');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'test1.php', 'test1.php');
$creator->savePhar(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cachestat.phar');
?>