<?php
$peardir = 'C:\devel\pear_with_channels';
$xmlrpcdir = 'C:\php5\pear\XML';

require_once $peardir . DIRECTORY_SEPARATOR . 'go-pear-list.php';
foreach ($packages as $package => $ver) {
    $packages[$package] = "$package-$ver";
}
require_once 'PEAR/PackageFile.php';
require_once 'PEAR/Config.php';
require_once 'PEAR/Downloader.php';
require_once 'PEAR/Installer.php';
require_once 'PEAR/Frontend.php';
require_once 'PHP/Archive/Creator.php';
$config = &PEAR_Config::singleton();
$ui = &PEAR_Frontend::singleton('PEAR_Frontend_CLI');

$dl = &new PEAR_Downloader($ui, array('downloadonly' => true, 'nodeps' => true), $config);
$dl->setDownloadDir(dirname(__FILE__));
$res = &$dl->download(array_values($packages));

$pkg = &new PEAR_PackageFile($config);
$pf = $pkg->fromPackageFile($peardir . DIRECTORY_SEPARATOR . 'package2.xml', PEAR_VALIDATE_NORMAL);
$pearver = $pf->getVersion();

$creator = new PHP_Archive_Creator('index.php', true);
foreach ($packages as $package => $name) {
    $creator->addFile("$name.tgz", "packages/$name.tgz");
    @unlink("$name.tgz");
}
$commandcontents = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'pearindex.php');
$commandcontents = str_replace('@PEARVER@', $pearver, $commandcontents);
$creator->addString($commandcontents, 'index.php');

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

$creator->addFile($xmlrpcdir . DIRECTORY_SEPARATOR . 'RPC.php', 'XML/RPC.php', true);
$creator->addDir($peardir, array('tests/',
    'scripts/',
    '*PEAR/Command.php',
    '*PEAR/Dependency2.php',
    '*PEAR/PackageFile/Generator/v1.php',
    '*PEAR/PackageFile/Generator/v2.php',
    '*PEAR/PackageFile/v2.php',
    '*PEAR/PackageFile.php',
    '*PEAR/Downloader/Package.php',
    '*PEAR/Frontend.php'),
    array(
        '*PEAR/ChannelFile/Parser.php',
        '*PEAR/Command/Install-init.php',
        '*PEAR/Command/Install.php',
        '*PEAR/Downloader/Package.php',
        '*PEAR/Frontend/CLI.php',
        '*PEAR/Installer/Role.php',
        '*PEAR/Installer/Role/Common.php',
        '*PEAR/Installer/Role/Data.php',
        '*PEAR/Installer/Role/Doc.php',
        '*PEAR/Installer/Role/Php.php',
        '*PEAR/Installer/Role/Script.php',
        '*PEAR/Installer/Role/Test.php',
        '*PEAR/PackageFile/v1.php',
        '*PEAR/PackageFile/v2.php',
        '*PEAR/PackageFile/Parser/v1.php',
        '*PEAR/PackageFile/Parser/v2.php',
        '*PEAR/PackageFile/Generator/v1.php',
        '*PEAR/Task/Common.php',
        '*PEAR/Task/Replace.php',
        '*PEAR/Task/Windowseol.php',
        '*PEAR/Task/Unixeol.php',
        '*PEAR/Validator/PECL.php',
        '*PEAR/ChannelFile.php',
        '*PEAR/Command.php',
        '*PEAR/Common.php',
        '*PEAR/Config.php',
        '*PEAR/Dependency2.php',
        '*PEAR/DependencyDB.php',
        '*PEAR/Downloader.php',
        '*PEAR/ErrorStack.php',
        '*PEAR/Frontend.php',
        '*PEAR/Installer.php',
        '*PEAR/PackageFile.php',
        '*PEAR/Registry.php',
        '*PEAR/Remote.php',
        '*PEAR/Validate.php',
        '*PEAR/XMLParser.php',
        'PEAR.php',
        '*OS/Guess.php',
        '*Archive/Tar.php',
        '*Console/Getopt.php',
        'System.php',
    ), 'PEAR.phar');
$creator->savePhar(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'go-pear.phar');
?>