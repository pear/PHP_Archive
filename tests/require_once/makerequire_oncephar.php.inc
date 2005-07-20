<?php
require_once 'PHP/Archive/Creator.php';
chdir(dirname(__FILE__));
$creator = new PHP_Archive_Creator('indexhooha.php', true);
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'test_require.php', 'indexhooha.php');
$creator->savePhar(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'require_once.phar');
@unlink($name);
?>