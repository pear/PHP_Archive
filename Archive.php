<?php
/**
 * PHP_Archive Class (implements .phar)
 *
 * @package PHP_Archive
 * @category PHP
 * @todo finish opendir/readdir/rewinddir/closedir code
 */

/**
 * PHP_Archive Class (implements .phar)
 *
 * PHAR files a singular archive from which an entire application can run.
 * To use it, simply package it using {@see PHP_Archive_Creator} and use phar://
 * URIs to your includes. i.e. require_once 'phar://config.php' will include config.php
 * from the root of the PHAR file.
 *
 * Tar/Gz code borrowed from the excellent File_Archive package by Vincent Lascaux.
 *
 * @copyright Copyright © David Shafik and Synaptic Media 2004. All rights reserved.
 * @author Davey Shafik <davey@synapticmedia.net>
 * @author Greg Beaver <cellog@php.net>
 * @link http://www.synapticmedia.net Synaptic Media
 * @version $Id$
 * @package PHP_Archive
 * @category PHP
 */
 
class PHP_Archive {
    
    /**
     * @var string Archive filename
     */

    var $archiveName = null;

    /**
     * Current Stat info of the current file in the tar
     */

    var $currentStat = null;

    /**
     * Current file name in the tar
     * @var string
     */

    var $currentFilename = null;

    /**
     * Length of the current tar file
     * @var int
     */

    var $internalFileLength = 0;

    /**
     * Length of the current tar file's footer
     * @var int
     */

    var $footerLength = 0;

    /**
     * @var string Content of the file being requested
     */
    
    var $file = null;

    /**
     * @var resource|null Pointer to open .phar
     */
    
    var $_file = null;
    
    /**
     * @var int Current Position of the pointer
     */
    
    var $position = 0;

    function _processFile($path)
    {
        if ($path == '.') {
            return '';
        }
        $std = str_replace("\\", "/", $path);
        while ($std != ($std = ereg_replace("[^\/:?]+/\.\./", "", $std))) ;
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
            if (empty($std) || $std == $this->currentFilename ||
                  //$std is a directory
                  strncmp($std.'/', $this->currentFilename, strlen($std)+1) == 0) {
                return true;
            }
        }
        return $error;
    }

    function _processHeader($rawHeader)
    {
        if (strlen($rawHeader) < 512 || $rawHeader == pack("a512", "")) {
            return 'Error: phar "' . $this->archiveName . '" has corrupted tar header';
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
        return $header;
    }

    function _nextFile()
    {
        @fread($this->_file, $this->internalFileLength + $this->footerLength);
        $rawHeader = @fread($this->_file, 512);
        $header = $this->_processHeader($rawHeader);
        if (is_string($header)) {
            return $header;
        }
        if ($header['type'] == '5') {
            return false; // directory entry
        }

        if ($header['type'] == 'L') {
            // filenames longer than 100 characters
            // borrowed from Archive_Tar written by Vincent Blavet
            $longFilename = '';
            $n = floor($header['size']/512);
            for ($i=0; $i < $n; $i++) {
                $content = @fread($this->_file, 512);
                $longFilename .= $content;
            }
            if (($header['size'] % 512) != 0) {
                $content = @fread($this->_file, 512);
                $longFilename .= $content;
            }
            // ----- Read the next header
            $newHeader = @fread($this->_file, 512);
            $header = $this->_processHeader($newHeader);
            if (is_string($header)) {
                return $header;
            }
            $header['filename'] = trim($longFilename);
            $rawHeader = $newHeader;
        }
        $this->currentFilename = $this->_processFile($header['path'] . $header['filename']);
        $checksum = 8 * ord(" ");
        if (version_compare(phpversion(), '5.0.0', '>=')) {
            $c1 = str_split(substr($rawHeader, 0, 512));
            $checkheader = array_merge(array_slice($c1, 0, 148), array_slice($c1, 156));
            if (!function_exists('_PharDoChecksum')) {
                function _PharDoChecksum($a, $b) {return $a + ord($b);}
            }
            $checksum += array_reduce($checkheader, '_PharDoChecksum');
        } else {
            for ($i = 0; $i < 148; $i++) {
                $checksum += ord($rawHeader{$i});
            }
            for ($i = 156; $i < 512; $i++) {
                $checksum += ord($rawHeader{$i});
            }
        }

        if (octdec($header['checksum']) != $checksum) {
            return 'Error: phar "' .
                $this->archiveName . '" Checksum error on entry "' . $this->currentFilename . '"';
        }
        return true;
    }

    function extractFile($path)
    {
        $this->_file = @fopen($this->archiveName, "rb");
        if (!$this->_file) {
            return array('Error: cannot open phar "' . $this->archiveName . '"');
        }
        if (($e = $this->_selectFile($path)) === true) {
            $data = @fread($this->_file, $this->internalFileLength);
            @fclose($this->_file);
            return $data;
        } else {
            @fclose($this->_file);
            return array($e);
        }
    }

    /**
     * Start the stream
     *
     * Opens the PHP Archive, which is the file being called
     * @param string
     * @return bool
     */
    
    function initializeStream($file)
    {
        $aname = get_included_files();
        $this->archiveName = 'phar://';
        if (strpos($file, '.phar')) {
            // grab the basename of the phar we want
            $test = substr($file, 0, strpos($file, '.phar') + 5);
        } else {
            $test = false;
        }
        while (count($aname)) {
            $this->archiveName = array_pop($aname);
            if (strpos($this->archiveName, 'phar://') === false) {
                if ($test) {
                    if (strpos($this->archiveName, $test)) {
                        break;
                    }
                } else {
                    break;
                }
            }
        }
        if ($test && $this->archiveName != 'phar://') {
            $file = substr($file, strlen($test) + 1);
        }
        return $file;
    }

    /**
     * Open the requested file
     *
     * @param string $file String provided by the Stream wrapper
     */
    
    function stream_open($file)
    {
        $path = substr($file, 7);
        $path = $this->initializeStream($path);
        if ($this->archiveName == 'phar://') {
            trigger_error('Error: Unknown phar in "' . $file . '"', E_USER_ERROR);
        }
        if (is_array($this->file = $this->extractFile($path))) {
            trigger_error($this->file[0], E_USER_ERROR);
            return false;
        }
        $compressed = $this->file ? (int) $this->file{0} : false;
        $this->file ? $this->file = substr($this->file, 1) : false;
        if ($compressed) {
            if (!function_exists('gzinflate')) {
                trigger_error('Error: zlib extension is not enabled - gzinflate() function needed' .
                    ' for compressed .phars');
                return false;
            }
            $this->file = base64_decode($this->file);
            $header = substr($this->file, 0, 10);
            $temp = unpack("Vcrc32/Visize", substr($this->file, -8));
    
            $id = @unpack("H2id1/H2id2/C1tmp/C1flags", substr($header, 0, 4));
            if ($id['id1'] != "1f" || $id['id2'] != "8b") {
                trigger_error("Not valid gz file (wrong header)", E_USER_ERROR);
                return false;
            }
            $this->file = gzinflate(substr($this->file, 10, strlen($this->file) - 8));

            if ($temp['isize'] != strlen($this->file)) {
                trigger_error("Not valid gz file (size error {$size} != " .
                    strlen($this->file) . ")", E_USER_ERROR);
                return false;
            }
            if ($temp['crc32'] != crc32($this->file)) {
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
     * For seeking the stream
     */
    
    function stream_seek($pos) {
        $this->position = $pos;
        return true;
    }
    
    /**
     * The current position in the stream
     */
    
    function stream_tell() {
        return $this->position;
    }

    /**
     * The result of an fstat call, returns mod time from tar, and file size
     */
    
    function stream_stat() {
        return array(
           'size' => strlen($this->file),
           'atime' => $this->currentStat[9],
           'mtime' => $this->currentStat[9],
           'ctime' => $this->currentStat[9],
           );
    }

    /**
     * Open a directory in the .phar for reading
     */
    function dir_opendir($path)
    {
        $path = $this->initializeStream($path);
        if ($this->archiveName == 'phar://') {
            trigger_error('Error: Unknown phar in "' . $file . '"', E_USER_ERROR);
        }
    }

    function APIVersion()
    {
        return '0.5';
    }
}

?>