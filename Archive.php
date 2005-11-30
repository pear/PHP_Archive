<?php
/**
 * PHP_Archive Class (implements .phar)
 *
 * @package PHP_Archive
 * @category PHP
 */
/**
 * PHP_Archive Class (implements .phar)
 *
 * PHAR files a singular archive from which an entire application can run.
 * To use it, simply package it using {@see PHP_Archive_Creator} and use phar://
 * URIs to your includes. i.e. require_once 'phar://config.php' will include config.php
 * from the root of the PHAR file.
 *
 * Gz code borrowed from the excellent File_Archive package by Vincent Lascaux.
 *
 * @copyright Copyright © David Shafik and Synaptic Media 2004. All rights reserved.
 * @author Davey Shafik <davey@synapticmedia.net>
 * @author Greg Beaver <cellog@php.net>
 * @link http://www.synapticmedia.net Synaptic Media
 * @version $Id$
 * @package PHP_Archive
 * @category PHP
 */
 
class PHP_Archive
{
    private $_compressed;
    /**
     * @var string Real path to the .phar archive
     */
    protected $pharName = null;
    /**
     * Current file name in the phar
     * @var string
     */
    protected $currentFilename = null;
    /**
     * Length of current file in the phar
     * @var string
     */
    protected $internalFileLength = null;
    /**
     * Current file statistics (size, creation date, etc.)
     * @var string
     */
    protected $currentStat = null;
    /**
     * @var resource|null Pointer to open .phar
     */
    protected $fp = null;
    /**
     * @var int Current Position of the pointer
     */
    protected $position = 0;

    private static $_pharMapping = array();
    private static $_manifest = array();
    private static $_fileStart = array();
    private $basename;

    /**
     * Map a full real file path to an alias used to refer to the .phar
     *
     * This function can only be called from the initialization of the .phar itself.
     * Any attempt to call from outside the .phar or to re-alias the .phar will fail
     * as a security measure.
     * @param string $file full realpath() filepath, like /path/to/go-pear.phar
     * @param string $alias alias used in opening a file within the phar
     *                      like phar://go-pear.phar/file
     * @param bool $compressed determines whether to attempt zlib uncompression
     *                         on accessing internal files
     * @param int $dataoffset the value of __COMPILER_HALT_OFFSET__
     */
    public static function mapPhar($file, $alias, $compressed, $dataoffset)
    {
        if ($compressed) {
            if (!function_exists('gzinflate')) {
                die('Error: zlib extension is not enabled - gzinflate() function needed' .
                    ' for compressed .phars');
            }
        }
        // this ensures that this is safe
        if (!in_array($file, get_included_files())) {
            die('SECURITY ERROR: PHP_Archive::mapPhar can only be called from within ' .
                'the phar that initiates it');
        }
        if (!is_array(self::$_pharMapping)) {
            self::$_pharMapping = array();
        }
        if (isset(self::$_pharMapping[$alias])) {
            die('ERROR: PHP_Archive::mapPhar has already been called for alias "' .
                $alias . '" cannot re-alias to "' . $file . '"');
        }
        self::$_pharMapping[$alias] = array($file, $compressed, $dataoffset);
    }

    /**
     * @param string
     */
    public static function processFile($path)
    {
        if ($path == '.') {
            return '';
        }
        $std = str_replace("\\", "/", $path);
        while ($std != ($std = ereg_replace("[^\/:?]+/\.\./", "", $std))) ;
        $std = str_replace("/./", "", $std);
        if (strlen($std) > 1 && $std[0] == '/') {
            $std = substr($std, 1);
        }
        if (strncmp($std, "./", 2) == 0) {
            return substr($std, 2);
        } else {
            return $std;
        }
    }

    /**
     * Seek in the master archive to a matching file or directory
     * @param string
     */
    protected function selectFile($path, $allowdirs = true)
    {
        $std = self::processFile($path);
        if (isset(self::$_manifest[$this->basename][$path])) {
            $this->_setCurrentFile($path);
            return true;
        }
        if (!$allowdirs) {
            return 'Error: "' . $path . '" is not a file in phar "' . $this->basename . '"';
        }
        foreach (self::$_manifest[$this->basename] as $file => $info) {
            if (empty($std) ||
                  //$std is a directory
                  strncmp($std.'/', $path, strlen($std)+1) == 0) {
                $this->currentFilename = $this->internalFileLength = $this->currentStat = null;
                return true;
            }
        }
        return 'Error: "' . $path . '" not found in phar "' . $this->basename . '"';
    }

    private function _setCurrentFile($path)
    {
        $this->currentStat = array(
            2 => 0100444, // file mode, readable by all, writeable by none
            4 => 0, // uid
            5 => 0, // gid
            7 => self::$_manifest[$this->basename][$path][0], // size
            9 => self::$_manifest[$this->basename][$path][1], // creation time
            );
        $this->currentFilename = $path;
        // actual file length in file includes 8-byte header
        $this->internalFileLength = self::$_manifest[$this->basename][$path][3] - 8;
        // seek to offset of file header within the .phar
        if (is_resource(@$this->fp)) {
            fseek($this->fp, self::$_fileStart[$this->basename] + self::$_manifest[$this->basename][$path][2]);
        }
    }

    /**
     * Seek to a file within the master archive, and extract its contents
     * @param string
     * @return array|string an array containing an error message string is returned
     *                      upon error, otherwise the file contents are returned
     */
    public function extractFile($path)
    {
        $this->fp = @fopen($this->archiveName, "rb");
        $stat = fstat($this->fp);
        if (!$this->fp) {
            return array('Error: cannot open phar "' . $this->archiveName . '"');
        }
        if (($e = $this->selectFile($path, false)) === true) {
            $temp = unpack("Vcrc32/Visize", fread($this->fp, 8));
            $data = '';
            $count = $this->internalFileLength;
            while ($count) {
                if ($count < 8192) {
                    $data .= @fread($this->fp, $count);
                    $count = 0;
                } else {
                    $count -= 8192;
                    $data .= @fread($this->fp, 8192);
                }
            }
            @fclose($this->fp);
            if ($this->_compressed) {
                $data = gzinflate($data);
            }
            if (!isset(self::$_manifest[$this->basename][$path]['ok'])) {
                if ($temp['isize'] != $this->currentStat[7]) {
                    return array("Not valid internal .phar file (size error {$size} != " .
                        $this->currentStat[7] . ")");
                }
                if ($temp['crc32'] != crc32($data)) {
                    return array("Not valid internal .phar file (checksum error)");
                }
                self::$_manifest[$this->basename][$path]['ok'] = true;
            }
            return $data;
        } else {
            @fclose($this->fp);
            return array($e);
        }
    }

    /**
     * Locate the .phar archive in the include_path and detect the file to open within
     * the archive.
     *
     * Possible parameters are phar://filename_within_phar.ext or
     * phar://pharname.phar/filename_within_phar.ext
     *
     * phar://filename_within_phar.ext will simply use the last .phar opened.
     * @param string a file within the archive
     * @return string the filename within the .phar to retrieve
     */
    public function initializeStream($file)
    {
        $info = parse_url($file);
        if (!isset($info['host']) || !count(self::$_pharMapping)) {
            // malformed internal file
            return false;
        }
        if (!isset($info['path'])) {
            // last opened phar is requested
            $info['path'] = $info['host'];
            $info['host'] = '';
        } elseif (strlen($info['path']) > 1) {
            $info['path'] = substr($info['path'], 1);
        }
        if (isset(self::$_pharMapping[$info['host']])) {
            $this->basename = $info['host'];
            $this->archiveName = self::$_pharMapping[$info['host']][0];
            $this->_compressed = self::$_pharMapping[$info['host']][1];
        } else {
            // no such phar has been included, or last opened phar is requested
            $pharinfo = end(self::$_pharMapping);
            $this->basename = key(self::$_pharMapping);
            $this->archiveName = $pharinfo[0];
            $this->_compressed = $pharinfo[1];
        }
        if (!isset(self::$_manifest[$this->basename])) {
            $fp = fopen($this->archiveName, 'rb');
            // seek to __HALT_COMPILER_OFFSET__
            fseek($fp, self::$_pharMapping[$this->basename][2]);
            $manifest_length = unpack('Vlen', fread($fp, 4));
            self::$_manifest[$this->basename] =
                $this->unserializeManifest(fread($fp, $manifest_length['len']));
            self::$_fileStart[$this->basename] = ftell($fp);
            fclose($fp);
        }
        $file = $info['path'];
        return $file;
    }


    function unserializeManifest($manifest)
    {
        $info = unpack('V', substr($manifest, 0, 4));
        $manifest = substr($manifest, 4);
        $ret = array();
        for ($i = 0; $i < $info[1]; $i++) {
            $len = unpack('V', substr($manifest, 0, 4));
            $savepath = substr($manifest, 4, $len[1]);
            $manifest = substr($manifest, $len[1] + 4);
            $ret[$savepath] = array_values(unpack('Va/Vb/Vc/Vd', substr($manifest, 0, 16)));
            $manifest = substr($manifest, 16);
        }
        return $ret;
    }

    /**
     * Open the requested file - PHP streams API
     *
     * @param string $file String provided by the Stream wrapper
     * @access private
     */
    public function stream_open($file)
    {
        return $this->_streamOpen($file);
    }

    /**
     * @param string filename to opne, or directory name
     * @param bool if true, a directory will be matched, otherwise only files
     *             will be matched
     * @uses trigger_error()
     * @return bool success of opening
     * @access private
     */
    private function _streamOpen($file, $searchForDir = false)
    {
        $path = $this->initializeStream($file);
        if (!$path) {
            trigger_error('Error: Unknown phar in "' . $file . '"', E_USER_ERROR);
        }
        if (is_array($this->file = $this->extractFile($path))) {
            trigger_error($this->file[0], E_USER_ERROR);
            return false;
        }
        if ($path != $this->currentFilename) {
            if (!$searchForDir) {
                trigger_error("Cannot open '$file', is a directory", E_USER_ERROR);
                return false;
            } else {
                $this->file = '';
                return true;
            }
        }

        if (!is_null($this->file) && $this->file !== false) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Read the data - PHP streams API
     *
     * @param int
     * @access private
     */
    public function stream_read($count)
    {
        $ret = substr($this->file, $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }
    
    /**
     * Whether we've hit the end of the file - PHP streams API
     * @access private
     */
    function stream_eof()
    {
        return $this->position >= $this->currentStat[7];
    }
    
    /**
     * For seeking the stream - PHP streams API
     * @param int
     * @param SEEK_SET|SEEK_CUR|SEEK_END
     * @access private
     */
    public function stream_seek($pos, $whence)
    {
        switch ($whence) {
            case SEEK_SET:
                if ($pos < 0) {
                    return false;
                }
                $this->position = $pos;
                break;
            case SEEK_CUR:
                if ($pos + $this->currentStat[7] < 0) {
                    return false;
                }
                $this->position += $pos;
                break;
            case SEEK_END:
                if ($pos + $this->currentStat[7] < 0) {
                    return false;
                }
                $this->position = $pos + $this->currentStat[7];
            default:
                return false;
        }
        return true;
    }
    
    /**
     * The current position in the stream - PHP streams API
     * @access private
     */
    public function stream_tell()
    {
        return $this->position;
    }

    /**
     * The result of an fstat call, returns mod time from creation, and file size -
     * PHP streams API
     * @uses _stream_stat()
     * @access private
     */
    public function stream_stat()
    {
        return $this->_stream_stat();
    }

    /**
     * Retrieve statistics on a file or directory within the .phar
     * @param string file/directory to stat
     * @access private
     */
    public function _stream_stat($file = null)
    {
        $std = $file ? self::processFile($file) : $this->currentFilename;
        if ($file) {
            if (isset(self::$_manifest[$this->basename][$file])) {
                $this->_setCurrentFile($file);
                $isdir = false;
            } else {
                $isdir = true;
            }
        } else {
            $isdir = false; // open streams must be files
        }
        $mode = $isdir ? 0040444 : 0100444;
        // 040000 = dir, 010000 = file
        // everything is readable, nothing is writeable
        return array(
           0, 0, $mode, 0, 0, 0, 0, 0, 0, 0, 0, 0, // non-associative indices
           'dev' => 0, 'ino' => 0,
           'mode' => $mode,
           'nlink' => 0, 'uid' => 0, 'gid' => 0, 'rdev' => 0, 'blksize' => 0, 'blocks' => 0,
           'size' => $this->currentStat[7],
           'atime' => $this->currentStat[9],
           'mtime' => $this->currentStat[9],
           'ctime' => $this->currentStat[9],
           );
    }

    /**
     * Stat a closed file or directory - PHP streams API
     * @param string
     * @param int
     * @access private
     */
    public function url_stat($url, $flags)
    {
        $path = $this->initializeStream($url);
        return $this->_stream_stat($path);
    }

    /**
     * Open a directory in the .phar for reading - PHP streams API
     * @param string directory name
     * @access private
     */
    public function dir_opendir($path)
    {
        $info = parse_url($path);
        $path = !empty($info['path']) ?
            $info['host'] . $info['path'] : $info['host'] . '/';
        $path = $this->initializeStream('phar://' . $path);
        if (isset(self::$_manifest[$this->basename][$path])) {
            trigger_error('Error: "' . $path . '" is a file, and cannot be opened with opendir',
                E_USER_ERROR);
            return false;
        }
        if ($path == false) {
            trigger_error('Error: Unknown phar in "' . $file . '"', E_USER_ERROR);
            return false;
        }
        $this->fp = @fopen($this->archiveName, "rb");
        if (!$this->fp) {
            trigger_error('Error: cannot open phar "' . $this->archiveName . '"');
            return false;
        }
        $this->_dirFiles = array();
        foreach (self::$_manifest[$this->basename] as $file => $info) {
            if ($path == '/') {
                if (strpos($file, '/')) {
                    $this->_dirFiles[array_shift($a = explode('/', $file))] = true;
                } else {
                    $this->_dirFiles[$file] = true;
                }
            } elseif (strpos($file, $path) === 0) {
                $fname = substr($file, strlen($path) + 1);
                if (strpos($fname, '/')) {
                    $this->_dirFiles[array_unshift($a = explode('/', $fname))] = true;
                } else {
                    $this->_dirFiles[$fname] = true;
                }
            }
        }
        @fclose($this->fp);
        @uksort($this->_dirFiles, 'strnatcmp');
        return true;
    }

    /**
     * Read the next directory entry - PHP streams API
     * @access private
     */
    public function dir_readdir()
    {
        $ret = key($this->_dirFiles);
        @next($this->_dirFiles);
        if (!$ret) {
            return false;
        }
        return $ret;
    }

    /**
     * Close a directory handle opened with opendir() - PHP streams API
     * @access private
     */
    public function dir_closedir()
    {
        $this->_dirFiles = array();
        reset($this->_dirFiles);
        return true;
    }

    /**
     * Rewind to the first directory entry - PHP streams API
     * @access private
     */
    public function dir_rewinddir()
    {
        reset($this->_dirFiles);
        return true;
    }

    /**
     * API version of this class
     * @return string
     */
    public function APIVersion()
    {
        return '@API-VER@';
    }
}
?>