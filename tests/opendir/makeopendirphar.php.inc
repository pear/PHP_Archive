<?php
require_once 'PHP/Archive/Creator.php';
require_once 'PHP/Archive/Manager.php';
chdir(dirname(__FILE__));
$creator = new PHP_Archive_Creator('indexhooha.php', 'opendir.phar', 'gz');
$creator->addDir(dirname(dirname(__FILE__)), array('CVS/', '*.phar'));
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'test1.php', 'indexhooha.php');
$creator->savePhar(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'opendir.phar');
@unlink($name);
$b = new PHP_Archive_Manager(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'opendir.phar');
$b->dump();
?>