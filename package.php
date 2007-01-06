<?php
require_once 'PEAR/PackageFileManager.php';
require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHAndling(PEAR_ERROR_DIE);
$version = '0.9.1';
$apiversion = '0.8.0';
$notes = '
fix API version so that Phar extension can also open PHP_Archive-generated archives

major 32-bit/64-bit issue in PHP 5.1 where unpack() returns different values
was causing some phars to fail.  For instance:

$a = pack("V", 3068571189);
var_dump(unpack("Va", $a));

reports
int(3068571189) on 32 bit and
int(-1226396107) on 64 bit

in PHP 5.1.  This is fixed in PHP 5.2.  This only affects CRCs.  The workaround
found is to sprintf("%u", $crc)
';


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
$package->setAPIStability('alpha');
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