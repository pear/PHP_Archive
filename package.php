<?php
require_once 'PEAR/PackageFileManager.php';

$version = '0.5.0';
$notes = <<<EOT
 * Full support for multiple .phars! phar://pharname.phar/file
   and phar://phar2.phar/anotherfile will work now
 * Fix gz compression
 * remove Archive_Tar dep for non-creation
 * remove preg dep for non-creation
 * bundle PHP_Archive in all created .phars - standalone! Only
   compressed .phars have a dep on zlib
 * Add support for filenames > 100 characters in length and unit test
 * Add full support for stat()/is_file()/is_dir()/is_readable() etc., opendir()/readdir()
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