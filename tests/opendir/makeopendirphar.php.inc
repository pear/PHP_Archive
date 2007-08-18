<?php
require_once 'PHP/Archive/Creator.php';
require_once 'PHP/Archive/Manager.php';
chdir(dirname(__FILE__));
$creator = new PHP_Archive_Creator('indexhooha.php', 'opendir.phar', 'gz');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'test1.php', 'indexhooha.php');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'eof.phpt', 'eof.phpt');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'filenotfound.phpt', 'filenotfound.phpt');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'filenotfound_phar.phpt', 'filenotfound_phar.phpt');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'filenotfound/makefilenotfoundphar.php.inc', 'filenotfound/makefilenotfoundphar.php.inc');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'gopearphar.php.inc', 'gopearphar.php.inc');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'longfilename/makelongphar.php.inc', 'longfilename/makelongphar.php.inc');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'longfilename.phpt', 'longfilename.phpt');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'makepearphar.php.inc', 'makepearphar.php.inc');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'normalstat.phpt', 'normalstat.phpt');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'normalstat_phar.phpt', 'normalstat_phar.phpt');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'opendir/makeopendirphar.php.inc', 'opendir/makeopendirphar.php.inc');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'opendir.phpt', 'opendir.phpt');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'phpt_test.php.inc', 'phpt_test.php.inc');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'planet_php/default_phar.php', 'planet_php/default_phar.php');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'require_once/makerequire_oncephar.php.inc', 'require_once/makerequire_oncephar.php.inc');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'require_once.phpt', 'require_once.phpt');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'require_once_phar.phpt', 'require_once_phar.phpt');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'seek.phpt', 'seek.phpt');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'seek_phar.phpt', 'seek_phar.phpt');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'streamstat.phpt', 'streamstat.phpt');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'streamstat_phar.phpt', 'streamstat_phar.phpt');
$creator->addFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'test_tar.tar', 'test_tar.tar');

$creator->savePhar(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'opendir.phar');
@unlink($name);
$b = new PHP_Archive_Manager(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'opendir.phar');
$b->dump();
?>