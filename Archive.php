<?php
/**
 * PHP_Archive Class (implements .phar)
 *
 * @package PHP_Archive
 * @category PHP
 */

/**
 * Directory that contains this file
 */
 
define('PHP_ARCHIVE_BASEDIR', dirname(__FILE__));

/**
 * Data Directory for PHP_Archive
 */
 
define('PHP_ARCHIVE_DATA_DIR', PHP_ARCHIVE_BASEDIR . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'data'. DIRECTORY_SEPARATOR .'PHP_Archive'. DIRECTORY_SEPARATOR .'data');

/**
 * PHP_Archive Class (implements .phar)
 *
 * PHAR files a singular archive from which an entire application can run.
 * To use it, simply package it using {@see PHP_Archive_Creator} and use phar://
 * URIs to your includes. i.e. require_once 'phar://config.php' will include config.php
 * from the root of the PHAR file.
 *
 * @copyright Copyright © David Shafik and Synaptic Media 2004. All rights reserved.
 * @author Davey Shafik <davey@synapticmedia.net>
 * @link http://www.synapticmedia.net Synaptic Media
 * @version $Id$
 * @package PHP_Archive
 * @category PHP
 */
 
class PHP_Archive {
    
    /**
     * @var string Archive filename
     */
    
    var $archive_name = null;
    
    /**
     * @var object Archive_Tar object
     */
    
    var $archive = null;
    
    /**
     * @var string Content of the file being requested
     */
    
    var $file = null;
    
    /**
     * @var int Current Position of the pointer
     */
    
    var $position = 0;
    
    /**
     * Start the stream
     *
     * Opens the PHP Archive, which is the file being called
     */
    
    function stream_start()
    {
        require_once 'Archive/Tar.php';
        $this->archive_name = array_shift(get_included_files());
        $this->archive =  new Archive_Tar($this->archive_name);
        return true;
    }
    
    /**
     * Open the requested file
     *
     * @param string $file String provided by the Stream wrapper
     */
    
    function stream_open($file)
    {
        $path = substr($file, 7);
        $this->stream_start();
        $this->file = $this->archive->extractInString($path);
        if (PHP_ARCHIVE_COMPRESSED) {
            $this->file = gzuncompress(base64_decode($this->file));
        }
        if (!is_null($this->file) && $this->file !== false) {
            return true;
        } else {
           return false;
        }
    }
    
    /**
     * Read the data
     *
     * @param int $count offset of the file to return
     */
    
    function stream_read($count)
    {
        static $i = 0;
        $i++;
        $ret = substr($this->file, $this->position, $count);
		$this->position += strlen($ret);
		return $ret;
    }
    
    /**
     * Whether we've hit the end of the file
     */
    
    function stream_eof()
	{
		return $this->position >= strlen($this->file);
	}
	
	/**
	 * For seeking the stream, does nothing
	 */
	
	function stream_seek() {
	    return true;
	}
	
	/**
	 * The current position in the stream
	 */
	
	function stream_tell() {
	    return $this->position;
	}
	
	/**
	 * The result of an fstat call, returns no values
	 */
	
	function stream_stat() {
	    return array();
	}
}

?>