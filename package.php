<?php
require_once 'PEAR/PackageFileManager.php';

$version = '0.4.0';
$notes = <<<EOT
Made PHP_Archive_Creator smarter
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
    'ignore'            => array('*old*','*entries*','*Template*','*Root*','*Repository*','package.php', 'package.xml', '*.bak', '*src*', '*.tgz', '*pear_media*', 'index.htm'),
	'filelistgenerator' => 'file', // other option is 'file'
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
$package->addDependency('Archive_Tar', '1.2', 'ge', 'pkg', false);
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