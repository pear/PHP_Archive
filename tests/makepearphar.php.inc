<?php
$peardir = 'C:\development\pear-core';

require_once 'PEAR/PackageFile.php';
require_once 'PEAR/Config.php';
require_once 'PHP/Archive/Creator.php';
$config = &PEAR_Config::singleton();
$pkg = &new PEAR_PackageFile($config);
$pf = $pkg->fromPackageFile($peardir . DIRECTORY_SEPARATOR . 'package2.xml', PEAR_VALIDATE_NORMAL);
$pearver = $pf->getVersion();

$creator = new PHP_Archive_Creator('index.php', true);
$commandcontents = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'pearindex.php');
$commandcontents = str_replace('@PEARVER@', $pearver, $commandcontents);
$creator->addString($commandcontents, 'index.php');

$commandcontents = file_get_contents($peardir . DIRECTORY_SEPARATOR . 'OS' .
    DIRECTORY_SEPARATOR . 'Guess.php');
$commandcontents = str_replace(
    array(
        "include_once \"",
        "@package_version@",
    ),
    array(
        "include_once \"phar://go-pear.phar/",
        $pearver,
    ),
    $commandcontents);
$creator->addString($commandcontents, 'OS/Guess.php');

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
        'if (file_exists($path . "/PEAR/Task/$task.php")) {',
        "include_once \"PEAR/Task/\$task.php",
    ),
    array(
        "require_once 'phar://PEAR.phar/",
        "include_once 'phar://PEAR.phar/",
        'if (true) {', // we're self-contained, so this should work
        "include_once \"phar://PEAR.phar/PEAR/Task/\$task.php",
    ),
    $commandcontents);
$creator->addString($commandcontents, 'PEAR/PackageFile/v2.php');

$commandcontents = file_get_contents($peardir . DIRECTORY_SEPARATOR . 'PEAR' .
    DIRECTORY_SEPARATOR . 'Frontend.php');
$commandcontents = str_replace(
    array(
        "include_once ",
        'PEAR_Frontend::isIncludeable($file)',
    ),
    array(
        "include_once 'phar://PEAR.phar/' . ",
        'true',
    ),
    $commandcontents);
$creator->addString($commandcontents, 'PEAR/Frontend.php');

$commandcontents = file_get_contents($peardir . DIRECTORY_SEPARATOR . 'PEAR' .
    DIRECTORY_SEPARATOR . 'Downloader' . DIRECTORY_SEPARATOR . 'Package.php');
$commandcontents = str_replace(
    array(
        "require_once '",
        "include_once '",
        "@PEAR-VER@",
    ),
    array(
        "require_once 'phar://PEAR.phar/",
        "include_once 'phar://PEAR.phar/",
        $pearver,
    ),
    $commandcontents);
$creator->addString($commandcontents, 'PEAR/Downloader/Package.php');

$commandcontents = file_get_contents($peardir . DIRECTORY_SEPARATOR . 'PEAR' .
    DIRECTORY_SEPARATOR . 'PackageFile' . DIRECTORY_SEPARATOR . 'v2' .
    DIRECTORY_SEPARATOR . 'Validator.php');
$commandcontents = str_replace(
    array(
        "require_once '",
        "include_once '",
        "@PEAR-VER@",
    ),
    array(
        "require_once 'phar://PEAR.phar/",
        "include_once 'phar://PEAR.phar/",
        $pearver,
    ),
    $commandcontents);
$creator->addString($commandcontents, 'PEAR/PackageFile/v2/Validator.php');

$commandcontents = file_get_contents($peardir . DIRECTORY_SEPARATOR . 'PEAR' .
    DIRECTORY_SEPARATOR . 'PackageFile' . DIRECTORY_SEPARATOR . 'v2' .
    DIRECTORY_SEPARATOR . 'rw.php');
$commandcontents = str_replace(
    array(
        "require_once '",
        "include_once '",
        "@package_version@",
    ),
    array(
        "require_once 'phar://PEAR.phar/",
        "include_once 'phar://PEAR.phar/",
        $pearver,
    ),
    $commandcontents);
$creator->addString($commandcontents, 'PEAR/PackageFile/v2/rw.php');

$commandcontents = file_get_contents($peardir . DIRECTORY_SEPARATOR . 'PEAR' .
    DIRECTORY_SEPARATOR . 'PackageFile' . DIRECTORY_SEPARATOR . 'Generator' .
    DIRECTORY_SEPARATOR . 'v1.php');
$commandcontents = str_replace(
    array(
        "require_once '",
        "include_once '",
        "@PEAR-VER@",
    ),
    array(
        "require_once 'phar://PEAR.phar/",
        "include_once 'phar://PEAR.phar/",
        $pearver,
    ),
    $commandcontents);
$creator->addString($commandcontents, 'PEAR/PackageFile/Generator/v1.php');

$commandcontents = file_get_contents($peardir . DIRECTORY_SEPARATOR . 'PEAR' .
    DIRECTORY_SEPARATOR . 'PackageFile' . DIRECTORY_SEPARATOR . 'Generator' .
    DIRECTORY_SEPARATOR . 'v2.php');
$commandcontents = str_replace(
    array(
        "require_once '",
        "include_once '",
        "@PEAR-VER@",
    ),
    array(
        "require_once 'phar://PEAR.phar/",
        "include_once 'phar://PEAR.phar/",
        $pearver,
    ),
    $commandcontents);
$creator->addString($commandcontents, 'PEAR/PackageFile/Generator/v2.php');

$commandcontents = file_get_contents($peardir . DIRECTORY_SEPARATOR . 'PEAR' .
    DIRECTORY_SEPARATOR . 'Dependency2.php');
$commandcontents = str_replace(
    array(
        "require_once '",
        "include_once '",
        "@PEAR-VER@",
    ),
    array(
        "require_once 'phar://PEAR.phar/",
        "include_once 'phar://PEAR.phar/",
        $pearver,
    ),
    $commandcontents);
$creator->addString($commandcontents, 'PEAR/Dependency2.php');

$commandcontents = file_get_contents($peardir . DIRECTORY_SEPARATOR . 'OS' .
    DIRECTORY_SEPARATOR . 'Guess.php');
$commandcontents = str_replace(
    array(
        "include_once \"",
        "@package_version@",
    ),
    array(
        "include_once \"phar://PEAR.phar/",
        $pearver,
    ),
    $commandcontents);
$creator->addString($commandcontents, 'OS/Guess.php');

$commandcontents = file_get_contents($peardir . DIRECTORY_SEPARATOR . 'PEAR' .
    DIRECTORY_SEPARATOR . 'PackageFile.php');
$commandcontents = str_replace(
    array(
        "require_once '",
        "include_once '",
        "@PEAR-VER@",
    ),
    array(
        "require_once 'phar://PEAR.phar/",
        "include_once 'phar://PEAR.phar/",
        $pearver,
    ),
    $commandcontents);
$creator->addString($commandcontents, 'PEAR/PackageFile.php');

$creator->addFile($peardir . DIRECTORY_SEPARATOR . 'template.spec', 'PEAR/template.spec');
$creator->addDir($peardir, array('tests/',
    'scripts/',
    'go-pear-list.php',
    'install-pear.php',
    '*OS/Guess.php',
    '*PEAR/Autoloader.php',
    '*PEAR/Builder.php',
    '*PEAR/Dependency.php',
    '*PEAR/Command.php',
    '*PEAR/Dependency2.php',
    '*PEAR/PackageFile/Generator/v1.php',
    '*PEAR/PackageFile/Generator/v2.php',
    '*PEAR/ErrorStack5.php',
    '*PEAR/Exception.php',
    '*PEAR/FTP.php',
    '*PEAR/PackageFile/v2.php',
    '*PEAR/PackageFile/PHP5/',
    '*PEAR/PackageFile/Parser/PHP5/',
    '*PEAR/PackageFile/Parser/PHP4/',
    '*PEAR/PackageFile/Generator/PHP5/',
    '*PEAR/PackageFile/Generator/PHP4/',
    '*PEAR/PackageFile.php',
    '*PEAR/Command/Package.php',
    '*PEAR/Downloader/Package.php',
    '*PEAR/test.php',
    '*PEAR/Warning.php',
    '*PEAR/Task/Postinstallscript/rw.php',
    '*PEAR/Task/Replace/rw.php',
    '*PEAR/Task/Unixeol/rw.php',
    '*PEAR/Task/Windowseol/rw.php',
    '*PEAR/Frontend.php'), array('*.php','*.xml'), 'PEAR.phar');
$creator->savePhar(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'PEAR.phar');
?>