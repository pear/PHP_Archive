<?php
require_once 'PEAR/PackageFileManager.php';
require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHAndling(PEAR_ERROR_DIE);
$version = '0.11.2';
$apiversion = '1.0.0';
$notes = '
major bugfix release
* fix 64-bit/32-bit compatibility of phars (Brian Shire)
* fix Bug #11858: seek to SEEK_END fails
* fix Bug #11859: mapPhar fails for all phars internally loaded
* fix Bug #11860: missing constant in PHP_Archive_Exception UNKNOWNSIG';


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
$package->addReplacement('Archive/Creator.php', 'pear-config', '@data_dir@', 'data_dir');

$package->generateContents();

$package->setPackageType('php');
$package->addRelease();

if (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] == 'commit') {
    $package->writePackageFile();
} else {
    $package->debugPackageFile();
}


?>