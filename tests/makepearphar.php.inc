<?php
$peardir = '/home/cellog/workspace/pear-core';

require_once 'PEAR/PackageFile.php';
require_once 'PEAR/Config.php';
require_once 'PHP/Archive/Creator.php';
$config = &PEAR_Config::singleton();
$pkg = &new PEAR_PackageFile($config);
$pf = $pkg->fromPackageFile($peardir . DIRECTORY_SEPARATOR . 'package2.xml', PEAR_VALIDATE_NORMAL);
$pearver = $pf->getVersion();

$creator = new PHP_Archive_Creator('index.php', 'PEAR.phar', false);
$commandcontents = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'pearindex.php');
$commandcontents = str_replace('@PEARVER@', $pearver, $commandcontents);
$creator->addString($commandcontents, 'index.php');

function replaceVersion($contents, $path)
{
    return str_replace(array('@PEAR-VER@', '@package_version@'), $GLOBALS['pearver'], $contents);
}
$commandcontents = file_get_contents($peardir . DIRECTORY_SEPARATOR . '/PEAR/Frontend.php');
$commandcontents = str_replace(
    array(
        "\$file = str_replace('_', '/', \$uiclass) . '.php';"
    ),
    array(
        "\$file = 'phar://PEAR.phar/' . str_replace('_', '/', \$uiclass) . '.php';"
    ), $commandcontents);
$commandcontents = replaceVersion($commandcontents, '');
$creator->addString($commandcontents, 'PEAR/Frontend.php');

$commandcontents = file_get_contents($peardir . DIRECTORY_SEPARATOR . '/PEAR/PackageFile/v2.php');
$commandcontents = str_replace(
    array(
        '$fp = @fopen("PEAR/Task/$taskfile.php", \'r\', true);',
    ),
    array(
        '$fp = @fopen("phar://PEAR.phar/PEAR/Task/$taskfile.php", \'r\', true);'
    ), $commandcontents);
$commandcontents = replaceVersion($commandcontents, '');
$commandcontents = $creator->tokenMagicRequire($commandcontents, 'a.php');
$creator->addString($commandcontents, 'PEAR/PackageFile/v2.php');

$creator->addMagicRequireCallback(array($creator, 'limitedSmartMagicRequire'));
$creator->addMagicRequireCallback('replaceVersion');
$creator->addFile($peardir . '/PEAR/Command.php', 'PEAR/Command.php');

$creator->clearMagicRequire();
$creator->addMagicRequireCallback(array($creator, 'tokenMagicRequire'));
$creator->addMagicRequireCallback('replaceVersion');
$creator->addDir($peardir . DIRECTORY_SEPARATOR . 'PEAR', array(),
    array(
        '*PEAR/Dependency2.php',
        '*PEAR/PackageFile/Generator/v1.php',
        '*PEAR/PackageFile/Generator/v2.php',
        '*PEAR/PackageFile/v2/Validator.php',
        '*PEAR/Downloader/Package.php',
        '*PEAR/Installer/Role.php',
        '*PEAR/ChannelFile/Parser.php',
        '*PEAR/Command/Install.xml',
        '*PEAR/Command/Install.php',
        '*PEAR/Command/Registry.xml',
        '*PEAR/Command/Registry.php',
        '*PEAR/Downloader/Package.php',
        '*PEAR/Frontend/CLI.php',
        '*PEAR/Installer/Role/Common.php',
        '*PEAR/Installer/Role/Data.php',
        '*PEAR/Installer/Role/Doc.php',
        '*PEAR/Installer/Role/Php.php',
        '*PEAR/Installer/Role/Script.php',
        '*PEAR/Installer/Role/Test.php',
        '*PEAR/Installer/Role/Data.xml',
        '*PEAR/Installer/Role/Doc.xml',
        '*PEAR/Installer/Role/Php.xml',
        '*PEAR/Installer/Role/Script.xml',
        '*PEAR/Installer/Role/Test.xml',
        '*PEAR/PackageFile.php',
        '*PEAR/PackageFile/v1.php',
        '*PEAR/PackageFile/Parser/v1.php',
        '*PEAR/PackageFile/Parser/v2.php',
        '*PEAR/PackageFile/Generator/v1.php',
        '*PEAR/REST.php',
        '*PEAR/REST/10.php',
        '*PEAR/Task/Common.php',
        '*PEAR/Task/Postinstallscript.php',
        '*PEAR/Task/Postinstallscript/rw.php',
        '*PEAR/Task/Replace.php',
        '*PEAR/Task/Replace/rw.php',
        '*PEAR/Task/Windowseol.php',
        '*PEAR/Task/Windowseol/rw.php',
        '*PEAR/Task/Unixeol.php',
        '*PEAR/Task/Unixeol/rw.php',
        '*PEAR/Validator/PECL.php',
        '*PEAR/ChannelFile.php',
        '*PEAR/Command/Common.php',
        '*PEAR/Common.php',
        '*PEAR/Config.php',
        '*PEAR/Dependency2.php',
        '*PEAR/DependencyDB.php',
        '*PEAR/Downloader.php',
        '*PEAR/ErrorStack.php',
        '*PEAR/Installer.php',
        '*PEAR/Registry.php',
        '*PEAR/Remote.php',
        '*PEAR/Start.php',
        '*PEAR/Start/CLI.php',
        '*PEAR/Validate.php',
        '*PEAR/XMLParser.php',
    ), false, $peardir);
$creator->addFile($peardir . DIRECTORY_SEPARATOR . 'PEAR.php', 'PEAR.php');
$creator->addFile($peardir . DIRECTORY_SEPARATOR . 'Archive/Tar.php', 'Archive/Tar.php');
$creator->addFile($peardir . DIRECTORY_SEPARATOR . 'Console/Getopt.php', 'Console/Getopt.php');
$creator->addFile($peardir . DIRECTORY_SEPARATOR . 'System.php', 'System.php');
$creator->addFile($peardir . DIRECTORY_SEPARATOR . 'OS/Guess.php', 'OS/Guess.php');
$creator->addFile($peardir . DIRECTORY_SEPARATOR . 'Structures_Graph/Structures/Graph.php', 'Structures/Graph.php');
$creator->addFile($peardir . DIRECTORY_SEPARATOR . 'Structures_Graph/Structures/Graph/Node.php', 'Structures/Graph/Node.php');
$creator->addFile($peardir . DIRECTORY_SEPARATOR . 'Structures_Graph/Structures/Graph/Manipulator/AcyclicTest.php', 'Structures/Graph/Manipulator/AcyclicTest.php');
$creator->addFile($peardir . DIRECTORY_SEPARATOR . 'Structures_Graph/Structures/Graph/Manipulator/TopologicalSorter.php', 'Structures/Graph/Manipulator/TopologicalSorter.php');
$creator->savePhar(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'PEAR.phar');
?>
