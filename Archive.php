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
    var $currentStat = null;
    var $currentFilename = null;
    var $internalFileLength = null;
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
        $aname = get_included_files();
        array_pop($aname);
        $this->archive_name = array_pop($aname);
        require_once 'PEAR.php';
//        require_once 'Archive/Tar.php';
//        $this->archive =  new Archive_Tar($this->archive_name);
        return true;
    }

    function _processFile($path)
    {
        if ($path == '.') {
            return '';
        }
        $std = str_replace("\\", "/", $path);
        while ($std != ($std = preg_replace("/[^\/:?]+\/\.\.\//", "", $std))) ;
        $std = str_replace("/./", "", $std);
        if (strncmp($std, "./", 2) == 0) {
            return substr($std, 2);
        } else {
            return $std;
        }
    }

    function _selectFile($path)
    {
        $std = $this->_processFile($path);
        while (($error = $this->_nextFile()) === true) {
            $sourceName = $this->currentFilename;
            if (empty($std) || $std == $this->currentFilename ||
                  //$std is a directory
                  strncmp($std.'/', $sourceName, strlen($std)+1) == 0) {
                return true;
            }
        }
        return $error;
    }

    function _open()
    {
        $this->_file = @fopen($this->archive_name, "rb");
        if (!$this->_file) {
            return PEAR::raiseError('Error: cannot open phar "' . $this->archive_name . '"');
        }
    }

    function _nextFile()
    {
        $rawHeader = @fread($this->_file, 512);
        if (strlen($rawHeader) < 512 || $rawHeader == pack("a512", "")) {
            return PEAR::raiseError('Error: phar "' . $this->archive_name . '" has no tar header');
        }

        $header = unpack(
            "a100filename/a8mode/a8uid/a8gid/a12size/a12mtime/".
            "a8checksum/a1type/a100linkname/a6magic/a2version/".
            "a32uname/a32gname/a8devmajor/a8devminor/a155path",
            $rawHeader);
        $this->currentStat = array(
            2 => octdec($header['mode']),
            4 => octdec($header['uid']),
            5 => octdec($header['gid']),
            7 => octdec($header['size']),
            9 => octdec($header['mtime'])
            );

        $this->internalFileLength = $this->currentStat[7];
        if ($this->internalFileLength % 512 == 0) {
            $this->footerLength = 0;
        } else {
            $this->footerLength = 512 - $this->internalFileLength % 512;
        }

        $this->currentFilename = $this->_processFile($header['path'] . $header['filename']);
        $checksum = 8 * ord(" ");
        for ($i = 0; $i < 148; $i++) {
            $checksum += ord($rawHeader{$i});
        }
        for ($i = 156; $i < 512; $i++) {
            $checksum += ord($rawHeader{$i});
        }

        if (octdec($header['checksum']) != $checksum) {
            return PEAR::raiseError('Error: phar "' .
                $this->archive_name . '" Checksum error on entry "' . $this->currentFilename . '"');
        }
        return true;
    }

    function extractFile($path)
    {
        $this->_open();
        if ($this->_selectFile($path)) {
            $actualLength = $this->internalFileLength;
            $data = @fread($this->_file, $actualLength);
            return $data;
        }
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
        $this->file = $this->extractFile($path);
        $compressed = $this->file ? (int) $this->file{0} : false;
        $this->file ? $this->file = substr($this->file, 1) : false;
        if ($compressed) {
            $this->file = base64_decode($this->file);
            // code borrowed from File_Archive_Reader_Gzip by Greg Beaver
            $header = substr($this->file, 0, 10);
            $temp = unpack("Vcrc32/Visize", substr($this->file, -8));
            $crc32 = $temp['crc32'];
            $size = $temp['isize'];
    
            $id = @unpack("H2id1/H2id2/C1tmp/C1flags", substr($header, 0, 4));
            if ($id['id1'] != "1f" || $id['id2'] != "8b") {
                trigger_error("Not valid gz file (wrong header)", E_USER_ERROR);
                return false;
            }
            $this->file = gzinflate(substr($this->file, 10, strlen($this->file) - 8));

            if ($size != strlen($this->file)) {
                trigger_error("Not valid gz file (size error {$size} != " .
                    strlen($this->file) . ")", E_USER_ERROR);
                return false;
            }
            if ($crc32 != crc32($this->file)) {
                trigger_error("Not valid gz file (checksum error)", E_USER_ERROR);
                return false;
            }
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