<?php
require_once 'PHP/Archive/Creator.php';
require_once 'PHP/Archive/Manager.php';
chdir(dirname(__FILE__));
copy(dirname(dirname(__FILE__)) . '/test1.php', $name = str_repeat('test', 26) . '.php');
$creator = new PHP_Archive_Creator($name, 'longphar.phar');
$creator->addFile($name, $name);
$creator->savePhar('longphar.phar');
@unlink($name);
$b = new PHP_Archive_Manager('longphar.phar');
$b->dump();
?>