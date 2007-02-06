<?php
require_once 'PEAR/PackageFileManager.php';
require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHAndling(PEAR_ERROR_DIE);
$version = '0.10.0';
$apiversion = '1.0.0';
$notes = '
Update to match phar extension

Final API for 1.0.0 is set';


$package = PEAR_PackageFileManager2::importOptions(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'package.xml',
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
$package->setAPIStability('stable');
$package->setNotes($notes);
$package->clearDeps();
$package->addPackageDepWithChannel('required', 'PEAR', 'pear.php.net', '1.4.3');
$package->setPhpDep('5.1.0');
$package->setPearinstallerDep('1.4.3');
$package->addReplacement('Archive.php', 'package-info', '@API-VER@', 'api-version');
$package->addReplacement('Archive/Creator.php', 'package-info', '@API-VER@', 'api-version');

$package->generateContents();

$package->setPackageType('php');
$package->addRelease();

if (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] == 'commit') {
    $package->writePackageFile();
} else {
    $package->debugPackageFile();
}


?>