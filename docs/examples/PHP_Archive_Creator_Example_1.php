<?php
/**
 * PHP_Archive_Creator Example
 *
 * This example shows a basic use of PHP_Archive_Creator
 *
 * @copyright Copyright © David Shafik and Synaptic Media 2004. All rights reserved.
 * @author Davey Shafik <davey@synapticmedia.net>
 * @link http://www.synapticmedia.net Synaptic Media
 * @version $Id$
 * @package PHP_Archive
 * @category PHP
 */
 
/**
 * PHP_Archive_Creator
 */
require_once 'PHP/Archive/Creator.php';

// Instantiate a PHP_Archive_Creator object, we pass in the init file name
$phar = new PHP_Archive_Creator('test.php', true);

// Add our files
$phar->addFile('../../tests/test.php', 'test.php');
$phar->addFile('../../tests/test_path.php', 'test_root.php');
$phar->addFile('../../tests/test_path.php', 'subdir/test_subdir.php');

// Save our new PHP_Archive somewhere - we should use a .phar extension
$phar->savePhar('../../tests/test_phar2.phar');

$phar = new PHP_Archive_Creator('test.php');

// Add our files
$phar->addFile('../../tests/test.php', 'test.php');
$phar->addFile('../../tests/test_path.php', 'test_root.php');
$phar->addFile('../../tests/test_path.php', 'subdir/test_subdir.php');

// Save our new PHP_Archive somewhere - we should use a .phar extension
$phar->savePhar('../../tests/test_phar.phar');

?>