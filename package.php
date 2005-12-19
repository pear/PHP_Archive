<?php
require_once 'PEAR/PackageFileManager.php';
require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHAndling(PEAR_ERROR_DIE);
$version = '0.8.0';
$apiversion = '0.7.1';
$notes = <<<EOT
This release is fully compatible with the phar extension

Small BC breaks:
* PHP_Archive::processFile() was public static and is now private static
* parameter order change to make phar extension
Feature additions:
* creating .phars that are reliant on the .phar extension is now possible
EOT;


$package = PEAR_PackageFileManager2::importFromPackageFile1(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'package.xml',
    $options = array(
    'ignore'            => array('package.php', 'package.xml', 'package2.xml', '*.bak', '*src*',
        '*.tgz', '*pear_media*', 'index.htm', 'PEAR.phar', 'docs/', 'phar_unpack.php', 'info.phar',
        '*CVS*'),
	'filelistgenerator' => 'cvs', // other option is 'file'
    'changelogoldtonew' => false,
    'baseinstalldir'    => 'PHP',
    'packagedirectory'  => dirname(__FILE__),
    'simpleoutput'      => true
    ));

$package->setReleaseVersion($version);
$package->setAPIVersion($apiversion);
$package->setReleaseStability('alpha');
$package->setAPIStability('alpha');
$package->setNotes($notes);
$package->clearDeps();
$package->addPackageDepWithChannel('required', 'PEAR', 'pear.php.net', '1.4.3');
$package->setPhpDep('5.1.0b1');
$package->setPearinstallerDep('1.4.3');
$package->addReplacement('Archive.php', 'package-info', '@API-VER@', 'api-version');
$package->addReplacement('Archive/Creator.php', 'package-info', '@API-VER@', 'api-version');

$package->generateContents();

$package->setPackageType('php');
$package->addRelease();

$pf1 = $package->exportCompatiblePackageFile1($options);
if (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] == 'commit') {
    $package->writePackageFile();
    $pf1->writePackageFile();
} else {
    $package->debugPackageFile();
    $pf1->debugPackageFile();
}


?>