<?php
$peardir = 'C:\devel\pear_with_channels';

require_once 'PHP/Archive/Creator.php';
$creator = new PHP_Archive_Creator('index.php', true);
$creator->addFile(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'pearindex.php', 'index.php');
$commandcontents = file_get_contents($peardir . DIRECTORY_SEPARATOR . 'PEAR' .
    DIRECTORY_SEPARATOR . 'Command.php');
$commandcontents = str_replace(
    array(
        "require_once '",
        "include_once '",
    ),
    array(
        "require_once 'phar://PEAR.phar/",
        "include_once 'phar://PEAR.phar/",
    ),
    $commandcontents);
$creator->addString($commandcontents, 'PEAR/Command.php');
$commandcontents = file_get_contents($peardir . DIRECTORY_SEPARATOR . 'PEAR' .
    DIRECTORY_SEPARATOR . 'PackageFile' . DIRECTORY_SEPARATOR . 'v2.php');
$commandcontents = str_replace(
    array(
        "require_once '",
        "include_once '",
        "include_once \"PEAR/Task/\$task.php",
    ),
    array(
        "require_once 'phar://PEAR.phar/",
        "include_once 'phar://PEAR.phar/",
        "include_once \"phar://PEAR.phar/PEAR/Task/\$task.php",
    ),
    $commandcontents);
$creator->addString($commandcontents, 'PEAR/PackageFile/v2.php');
$commandcontents = file_get_contents($peardir . DIRECTORY_SEPARATOR . 'PEAR' .
    DIRECTORY_SEPARATOR . 'Frontend.php');
$commandcontents = str_replace(
    array(
        "include_once ",
    ),
    array(
        "include_once 'phar://PEAR.phar/' . ",
    ),
    $commandcontents);
$creator->addString($commandcontents, 'PEAR/Frontend.php');
$creator->addDir($peardir, array('tests/',
    'PEAR/Command.php',
    'PEAR/PackageFile/v2.php',
    'PEAR/Frontend.php'), array('*.php'), 'PEAR.phar');
$creator->savePhar(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'PEAR.phar');
?>