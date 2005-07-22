<?php
require_once 'PEAR/PackageFileManager.php';

$version = '0.6.0';
$notes = <<<EOT
Bugfix release
 * change error_reporting to E_ALL.  Was stupidly using
   E_ERROR | E_WARNING | E_PARSE | E_NOTICE
 * change __HALT_PHP_PARSER__ to __HALT_COMPILER()
 * rework fread() usage to avoid all potential bugs with chunks
   larger than 8192
EOT;

$description =<<<EOT
PHP_Archive allows you to create a single .phar file containing an entire application.
EOT;

$package = new PEAR_PackageFileManager();

$result = $package->setOptions(array(
    'package'           => 'PHP_Archive',
    'summary'           => 'Create and Use PHP Archive files',
    'description'       => $description,
    'version'           => $version,
    'state'             => 'alpha',
    'license'           => 'PHP License',
    'ignore'            => array('package.php', 'package.xml', '*.bak', '*src*',
        '*.tgz', '*pear_media*', 'index.htm', 'PEAR.phar', 'docs/'),
	'filelistgenerator' => 'cvs', // other option is 'file'
    'notes'             => $notes,
    'changelogoldtonew' => false,
    'baseinstalldir'    => 'PHP',
    'packagedirectory'  => '',
    'simpleoutput'      => true
    ));

if (PEAR::isError($result)) {
    echo $result->getMessage();
    die();
}

$package->addMaintainer('davey','lead','Davey Shafik','davey@php.net');

/*
$package->addDependency('tokenizer', '', 'has', 'ext', false);*/
//$package->addDependency('auto');
$package->addDependency('Archive_Tar', '1.3.1', 'ge', 'pkg', false);
$package->addDependency('PEAR', '1.3.5', 'ge', 'pkg', false);
$package->addDependency('php', '4.3.0', 'ge', 'php', false);

if (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] == 'commit') {
    $result = $package->writePackageFile();
} else {
    $result = $package->debugPackageFile();
}

if (PEAR::isError($result)) {
    echo $result->getMessage();
    die();
}
?>