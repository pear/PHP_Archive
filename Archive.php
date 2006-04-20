<?php
/**
 * PHP_Archive Class (implements .phar)
 *
 * @package PHP_Archive
 * @category PHP
 */
/**
 * Flag for GZ compression
 */
define('PHP_ARCHIVE_COMPRESSED', 1);
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
 * @copyright Copyright David Shafik and Synaptic Media 2004. All rights reserved.
 * @author Davey Shafik <davey@synapticmedia.net>
 * @author Greg Beaver <cellog@php.net>
 * @link http://www.synapticmedia.net Synaptic Media
 * @version $Id$
 * @package PHP_Archive
 * @category PHP
 */
 
class PHP_Archive
{
    /**
     * Whether this archive is compressed with zlib
     *
     * @var bool
     */
    private $_compressed;
    /**
     * @var string Real path to the .phar archive
     */
    private $_archiveName = null;
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

    /**
     * Map actual realpath of phars to meta-data about the phar
     *
     * Data is indexed by the alias that is used by internal files.  In other
     * words, if a file is included via:
     * <code>
     * require_once 'phar://PEAR.phar/PEAR/Installer.php';
     * </code>
     * then the alias is "PEAR.phar"
     * 
     * Information stored is the actual file name, a boolean indicating whether
     * this .phar is compressed with zlib, and the precise offset of internal files
     * within the .phar, used with the {@link $_manifest} to load actual file contents
     * @var array
     */
    private static $_pharMapping = array();
    /**
     * File listing for the .phar
     * 
     * The manifest is indexed per phar.
     * 
     * Files within the .phar are indexed by their relative path within the
     * .phar.  Each file has this information in its internal array
     *
     * - 0 = uncompressed file size
     * - 1 = timestamp of when file was added to phar
     * - 2 = offset of file within phar relative to internal file's start
     * - 3 = compressed file size (actual size in the phar)
     * @var array
     */
    private static $_manifest = array();
    /**
     * Absolute offset of internal files within the .phar, indexed by absolute
     * path to the .phar
     *
     * @var array
     */
    private static $_fileStart = array();
    /**
     * file name of the phar
     *
     * @var string
     */
    private $_basename;

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
    public static final function mapPhar($file, $dataoffset)
    {
        $file = realpath($file);
        // this ensures that this is safe
        if (!in_array($file, get_included_files())) {
            die('SECURITY ERROR: PHP_Archive::mapPhar can only be called from within ' .
                'the phar that initiates it');
        }
        if (!is_array(self::$_pharMapping)) {
            self::$_pharMapping = array();
        }
        if (!isset(self::$_manifest[$file])) {
            $fp = fopen($file, 'rb');
            // seek to __HALT_COMPILER_OFFSET__
            fseek($fp, $dataoffset);
            $manifest_length = unpack('Vlen', fread($fp, 4));
            $manifest = '';
            $last = '1';
            while (strlen($last) && strlen($manifest) < $manifest_length['len']) {
                $read = 8192;
                if ($manifest_length['len'] - strlen($manifest) < 8192) {
                    $read = $manifest_length['len'] - strlen($manifest);
                }
                $last = fread($fp, $read);
                $manifest .= $last;
            }
            if (strlen($manifest) < $manifest_length['len']) {
                die('ERROR: manifest length read was "' . strlen($manifest) .'" should be "' .
                    $manifest_length['len'] . '"');
            }
            $info = self::_unserializeManifest($manifest);
            if (!$info) {
                die; // error declared in unserializeManifest
            }
            $alias = $info['alias'];
            self::$_manifest[$file] = $info['manifest'];
            $compressed = $info['compressed'];
            self::$_fileStart[$file] = ftell($fp);
            fclose($fp);
        }
        if ($compressed) {
            if (!function_exists('gzinflate')) {
                die('Error: zlib extension is not enabled - gzinflate() function needed' .
                    ' for compressed .phars');
            }
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
    private static function processFile($path)
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
        if (isset(self::$_manifest[$this->_archiveName][$path])) {
            $this->_setCurrentFile($path);
            return true;
        }
        if (!$allowdirs) {
            return 'Error: "' . $path . '" is not a file in phar "' . $this->_basename . '"';
        }
        foreach (self::$_manifest[$this->_archiveName] as $file => $info) {
            if (empty($std) ||
                  //$std is a directory
                  strncmp($std.'/', $path, strlen($std)+1) == 0) {
                $this->currentFilename = $this->internalFileLength = $this->currentStat = null;
                return true;
            }
        }
        return 'Error: "' . $path . '" not found in phar "' . $this->_basename . '"';
    }

    private function _setCurrentFile($path)
    {
        $this->currentStat = array(
            2 => 0100444, // file mode, readable by all, writeable by none
            4 => 0, // uid
            5 => 0, // gid
            7 => self::$_manifest[$this->_archiveName][$path][0], // size
            9 => self::$_manifest[$this->_archiveName][$path][1], // creation time
            );
        $this->currentFilename = $path;
        $this->internalFileLength = self::$_manifest[$this->_archiveName][$path][2];
        // seek to offset of file header within the .phar
        if (is_resource(@$this->fp)) {
            fseek($this->fp, self::$_fileStart[$this->_archiveName] + self::$_manifest[$this->_archiveName][$path][5]);
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
        $this->fp = @fopen($this->_archiveName, "rb");
        if (!$this->fp) {
            return array('Error: cannot open phar "' . $this->_archiveName . '"');
        }
        if (($e = $this->selectFile($path, false)) === true) {
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
            if (self::$_manifest[$this->_archiveName][$path][4] & PHP_ARCHIVE_COMPRESSED) {
                $data = gzinflate($data);
            }
            if (!isset(self::$_manifest[$this->_archiveName][$path]['ok'])) {
                if (strlen($data) != $this->currentStat[7]) {
                    return array("Not valid internal .phar file (size error {$size} != " .
                        $this->currentStat[7] . ")");
                }
                if (self::$_manifest[$this->_archiveName][$path][3] != crc32($data)) {
                    return array("Not valid internal .phar file (checksum error)");
                }
                self::$_manifest[$this->_archiveName][$path]['ok'] = true;
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
            $this->_basename = $info['host'];
            $this->_archiveName = self::$_pharMapping[$info['host']][0];
            $this->_compressed = self::$_pharMapping[$info['host']][1];
        } else {
            // no such phar has been included, or last opened phar is requested
            $pharinfo = end(self::$_pharMapping);
            $this->_basename = key(self::$_pharMapping);
            $this->_archiveName = $pharinfo[0];
            $this->_compressed = $pharinfo[1];
        }
        $file = $info['path'];
        return $file;
    }

    /**
     * extract the manifest into an internal array
     *
     * @param string $manifest
     * @return false|array
     */
    private static function _unserializeManifest($manifest)
    {
        // retrieve the number of files in the manifest
        $info = unpack('V', substr($manifest, 0, 4));
        $apiver = substr($manifest, 4, 2);
        $apiver = bin2hex($apiver);
        $apiver_dots = hexdec($apiver[0]) . '.' . hexdec($apiver[1]) . '.' . hexdec($apiver[2]);
        $majorcompat = hexdec($apiver[0]);
        $calcapi = explode('.', '@API-VER@');
        if ($calcapi[0] != $majorcompat) {
            trigger_error('Phar is incompatible API version ' . $apiver_dots . ', but ' .
                'PHP_Archive is API version @API-VER@');
            return false;
        }
        if ($calcapi[0] === '0') {
            if ('@API-VER@' != $apiver_dots) {
                trigger_error('Phar is API version ' . $apiver_dots .
                    ', but PHP_Archive is API version @API-VER@', E_USER_ERROR);
                return false;
            }
        }
        $ret = array('compressed' => $apiver[3]);
        $aliaslen = unpack('V', substr($manifest, 6, 4));
        $ret['alias'] = substr($manifest, 10, $aliaslen[1]);
        $manifest = substr($manifest, 10 + $aliaslen[1]);
        $offset = 0;
        $start = 0;
        for ($i = 0; $i < $info[1]; $i++) {
            // length of the file name
            $len = unpack('V', substr($manifest, $start, 4));
            $start += 4;
            // file name
            $savepath = substr($manifest, $start, $len[1]);
            $start += $len[1];
            // retrieve manifest data:
            // 0 = uncompressed file size
            // 1 = timestamp of when file was added to phar
            // 2 = compressed filesize
            // 3 = crc32
            // 4 = flags
            $ret['manifest'][$savepath] = array_values(unpack('Va/Vb/Vc/Vd/Ce', substr($manifest, $start, 17)));
            $ret['manifest'][$savepath][5] = $offset;
            $offset += $ret['manifest'][$savepath][2];
            $start += 17;
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
            if (isset(self::$_manifest[$this->_archiveName][$file])) {
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
        if (isset(self::$_manifest[$this->_archiveName][$path])) {
            trigger_error('Error: "' . $path . '" is a file, and cannot be opened with opendir',
                E_USER_ERROR);
            return false;
        }
        if ($path == false) {
            trigger_error('Error: Unknown phar in "' . $file . '"', E_USER_ERROR);
            return false;
        }
        $this->fp = @fopen($this->_archiveName, "rb");
        if (!$this->fp) {
            trigger_error('Error: cannot open phar "' . $this->_archiveName . '"');
            return false;
        }
        $this->_dirFiles = array();
        foreach (self::$_manifest[$this->_archiveName] as $file => $info) {
            if ($path == '/') {
                if (strpos($file, '/')) {
                    $a = explode('/', $file);
                    $this->_dirFiles[array_shift($a)] = true;
                } else {
                    $this->_dirFiles[$file] = true;
                }
            } elseif (strpos($file, $path) === 0) {
                $fname = substr($file, strlen($path) + 1);
                if (strpos($fname, '/')) {
                    $a = explode('/', $fname);
                    $this->_dirFiles[array_shift($a)] = true;
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
    public final function APIVersion()
    {
        return '@API-VER@';
    }
}
?>