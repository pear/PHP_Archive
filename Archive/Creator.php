<?php
/**
 * PHP_Archive Creator Class 
 *
 * @package PHP_Archive
 * @category PHP
 */
 
/**
 * Require PHP_Archive
 */

require_once 'PHP/Archive.php';
 
/**
 * Require Archive_Tar
 */

require_once 'Archive/Tar.php';

/**
 * PHP_Archive Creator Class
 *
 * This class allows you to easily, programatically create PHP Archives (PHAR files)
 *
 * @copyright Copyright © David Shafik and Synaptic Media 2004. All rights reserved.
 * @author Davey Shafik <davey@synapticmedia.net>
 * @link http://www.synapticmedia.net Synaptic Media
 * @version $Id$
 * @package PHP_Archive
 * @category PHP
 * @example c:\web\php-cvs\pear\PHP_Archive\docs\examples\PHP_Archive_Creator_Example_1.php Example PHP_Archive_Creator usage
 * @todo Implement PHP_Archive_Creator::addDir();
 */

class PHP_Archive_Creator {
    
    /**
     * @var string The Archive Filename
     */
    
    var $archive_name;
    
    /**
     * @var object An instance of Archive_Tar
     */
    
    var $tar;
    
    /**
     * @var string The temporary path to the TAR archive
     */
    
    var $temp_path;
    
    /**
     * @var string Where the TAR archive will be saved
     */
    
    var $save_path;
    
    /**
     * @var boolean Whether or not the archive should be compressed
     */
    
    var $compress = false;
    
    /**
     * @var boolean Whether or not a file has been added to the archive
     */
     
    var $modified = false;
    
    /**
     * PHP_Archive Constructor
     *     
     * @param string $init_file Init file (file called by default upon PHAR execution)
     * @param boolean $compress Whether to compress the files or not (will cause slowdown!)
     * @param mixed $allow_direct_access The file extension to prepend to requests for files 
     *                                   (i.e. request is /index, this is .php or .htm or
     *                                   whatever), false means that users can't browse to 
     *                                   any file but the init file (you should handle other 
     *                                   pages in your init file code). If you set it to True,
     *                                   then the exact PATH_INFO is used
     * @return void
     */
    function __construct($init_file = 'index.php', $compress = false, $allow_direct_access = false)
    {
        $this->compress = $compress;
        $this->temp_path = tempnam(PHP_ARCHIVE_DATA_DIR, 'phr');
        $tar = new Archive_Tar($this->temp_path);
        $unpack_code = <<<PHP
        
        
        
        
        
        
        
        

require_once 'PHP/Archive.php';
stream_register_wrapper('phar', 'PHP_Archive');
PHP;

        if ($compress == true) {
            $unpack_code .= <<<PHP

if (!defined('PHP_ARCHIVE_COMPRESSED')) {
    define('PHP_ARCHIVE_COMPRESSED', true);
}

PHP;
        } else {
            $unpack_code .= <<<PHP

if (!defined('PHP_ARCHIVE_COMPRESSED')) {
    define('PHP_ARCHIVE_COMPRESSED', false);
}

PHP;
        }
        
        if (!$allow_direct_access) {
            $unpack_code .= <<<PHP
require_once 'phar://$init_file';
PHP;
        } else {
            if ($allow_direct_access) {
                $allow_direct_access = '';
            } elseif ($allow_direct_access{0} != '.') {
                $allow_direct_access = '.' . $allow_direct_access;
            }
            $unpack_code .= <<<PHP
require_once 'phar://{$_SERVER['PATH_INFO']}$allow_direct_access';
PHP;
        }
        
        $unpack_code .= <<<PHP
        
exit;
?>
PHP;
        $tar->addString('<?php #PHP_ARCHIVE_HEADER-0.4.0.php', $unpack_code);
        
        $this->code = $unpack_code;
        
        $this->tar =& $tar;
    }
    
    /**
     * PHP4 COmpatible Constructor
     *
     * @see PHP_Archive_Creator::__construct
     */
    
    function PHP_Archive_Creator($init_file = 'index.php', $compress = false, $allow_direct_access = false)
    {
        $this->__construct($init_file, $compress, $allow_direct_access);
    }

    /**
     * Add a file to the PHP Archive
     *
     * @param string $file Path of the File to add
     * @param string $save_path The save location of the file in the archive
     * @return boolean
     */
    
    function addFile($file, $save_path)
    {
        $this->modified = true;
        $file_contents = file_get_contents($file);
        if ($this->compress) {
            $file_contents = base64_encode(gzcompress($file_contents));
        }
        return $this->tar->addString($save_path, $file_contents);
    }
    
    /**
     * Add a directory to the archive
     *
     * Not implemented yet!
     *
     * @param string $dir The directory path to add
     * @return boolean
     */
    
    function addDir($dir)
    {
        // Not implemented yet!
    }
    
    /**
     * Add an array of files to the archive
     *
     * @param array $files This is an associative array of the format 'file_to_archive' => 'save_path_in_archive'
     * @return boolean
     */
     
    function addArray($files)
    {
        $this->modified = true;
        if (!$this->compress) {
            $this->compress = $compress;
        }
        foreach ($files as $file_path => $save_path) {
            $returns[] = $this->addFile($file_path, $save_path, $compress);
        }
        return !in_array(false, $returns);
    }
    
    /**
     * Save the PHAR Archive
     *
     * @param string $file_path The file path of where to save the file
     * @return void
     */
    
    function savePhar($file_path = null)
    {
        copy($this->temp_path, $file_path);
        unlink($this->temp_path);
    }
    
}
?>