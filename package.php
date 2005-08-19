<?php
require_once 'PEAR/PackageFileManager.php';
require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHAndling(PEAR_ERROR_DIE);
$version = '0.6.0';
$apiversion = '0.6';
$notes = <<<EOT
Bugfix release
 * change error_reporting to E_ALL.  Was stupidly using
   E_ERROR | E_WARNING | E_PARSE | E_NOTICE
 * change __HALT_PHP_PARSER__ to __HALT_COMPILER()
 * rework fread() usage to avoid all potential bugs with chunks
   larger than 8192
 * drop support for PHP 4.3.x and 5.0.x.  Streams are impossible to
   fully support due to bugs in all PHP versions < 5.1.0.
EOT;


$package = PEAR_PackageFileManager2::importFromPackageFile1(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'package.xml',
    $options = array(
    'ignore'            => array('package.php', 'package.xml', 'package2.xml', '*.bak', '*src*',
        '*.tgz', '*pear_media*', 'index.htm', 'PEAR.phar', 'docs/', 'phar_unpack.php', 'info.phar'),
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
$package->addPackageDepWithChannel('required', 'Archive_Tar', 'pear.php.net', '1.3.2');
$package->addPackageDepWithChannel('required', 'PEAR', 'pear.php.net', '1.3.5');
$package->setPhpDep('5.1.0b1');
$package->setPearinstallerDep('1.4.0b1');
$package->generateContents();
$package->addReplacement('Archive.php', 'package-info', '@API-VER@', 'api-version');
$package->addReplacement('Archive/Creator.php', 'package-info', '@API-VER@', 'api-version');

$pf1 = $package->exportCompatiblePackageFile1($options);
if (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] == 'commit') {
    $package->writePackageFile();
    $pf1->writePackageFile();
} else {
    $package->debugPackageFile();
    $pf1->debugPackageFile();
}


?>