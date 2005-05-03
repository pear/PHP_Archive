<?php
require_once 'PHP/Archive/Creator.php';
chdir(dirname(__FILE__));
$creator = new PHP_Archive_Creator('indexhooha.php', true);
$creator->addDir(dirname(dirname(__FILE__)), array('CVS/', '*.phar'));
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'test1.php', 'indexhooha.php');
$creator->savePhar(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'opendir.phar');
@unlink($name);
?>