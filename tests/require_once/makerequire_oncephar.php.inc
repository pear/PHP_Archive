<?php
require_once 'PHP/Archive/Creator.php';
require_once 'PHP/Archive/Manager.php';
chdir(dirname(__FILE__));
$creator = new PHP_Archive_Creator('indexhooha.php', true, 'require_once.phar');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'test_require.php', 'indexhooha.php');
$creator->savePhar(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'require_once.phar');
@unlink($name);
$b = new PHP_Archive_Manager(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'require_once.phar');
$b->dump();
?>