<?php
require_once 'PHP/Archive/Creator.php';
require_once 'PHP/Archive/Manager.php';
chdir(dirname(__FILE__));
copy('../test1.php', $name = str_repeat('test', 26) . '.php');
$creator = new PHP_Archive_Creator($name, false, 'longphar.phar');
$creator->addFile(dirname(__FILE__) . DIRECTORY_SEPARATOR . $name, $name);
$creator->savePhar(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'longphar.phar');
@unlink($name);
$b = new PHP_Archive_Manager(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'longphar.phar');
$b->dump();
?>