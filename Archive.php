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
    const GZ = 0x00001000;
    const BZ2 = 0x00002000;
    const SIG = 0x00010000;
    const SHA1 = 0x0002;
    const MD5 = 0x0001;
    const SHA256 = 0x0003;
    const SHA512 = 0x0004;
    const OPENSSL = 0x0010;
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
     * true if the current file is an empty directory
     * @var string
     */
    protected $isDir = false;
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
     * Information stored is a boolean indicating whether this .phar is compressed
     * with zlib, another for bzip2, phar-specific meta-data, and
     * the precise offset of internal files
     * within the .phar, used with the {@link $_manifest} to load actual file contents
     * @var array
     */
    private static $_pharMapping = array();
    /**
     * Map real file paths to alias used
     *
     * @var array
     */
    private static $_pharFiles = array();
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
     * Default MIME types used for the web front controller
     *
     * @var array
     */
    public static $defaultmimes = array(
            'aif' => 'audio/x-aiff',
            'aiff' => 'audio/x-aiff',
            'arc' => 'application/octet-stream',
            'arj' => 'application/octet-stream',
            'art' => 'image/x-jg',
            'asf' => 'video/x-ms-asf',
            'asx' => 'video/x-ms-asf',
            'avi' => 'video/avi',
            'bin' => 'application/octet-stream',
            'bm' => 'image/bmp',
            'bmp' => 'image/bmp',
            'bz2' => 'application/x-bzip2',
            'css' => 'text/css',
            'doc' => 'application/msword',
            'dot' => 'application/msword',
            'dv' => 'video/x-dv',
            'dvi' => 'application/x-dvi',
            'eps' => 'application/postscript',
            'exe' => 'application/octet-stream',
            'gif' => 'image/gif',
            'gz' => 'application/x-gzip',
            'gzip' => 'application/x-gzip',
            'htm' => 'text/html',
            'html' => 'text/html',
            'ico' => 'image/x-icon',
            'jpe' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'js' => 'application/x-javascript',
            'log' => 'text/plain',
            'mid' => 'audio/x-midi',
            'mov' => 'video/quicktime',
            'mp2' => 'audio/mpeg',
            'mp3' => 'audio/mpeg3',
            'mpg' => 'audio/mpeg',
            'pdf' => 'aplication/pdf',
            'png' => 'image/png',
            'rtf' => 'application/rtf',
            'tif' => 'image/tiff',
            'tiff' => 'image/tiff',
            'txt' => 'text/plain',
            'xml' => 'text/xml',
        );

    public static $defaultphp = array(
        'php' => true
        );

    public static $defaultphps = array(
        'phps' => true
        );

    public static $deny = array('/.+\.inc$/');

    public static function viewSource($archive, $file)
    {
        // security, idea borrowed from PHK
        if (!file_exists($archive . '.introspect')) {
            header("HTTP/1.0 404 Not Found");
            return false;
        }
        if (self::_fileExists($archive, $_GET['viewsource'])) {
            $source = highlight_file('phar://@ALIAS@/' .
                $_GET['viewsource'], true);
            header('Content-Type: text/html');
            header('Content-Length: ' . strlen($source));
            echo '<html><head><title>Source of ',
                htmlspecialchars($_GET['viewsource']), '</title></head>';
            echo '<body><h1>Source of ',
                htmlspecialchars($_GET['viewsource']), '</h1>';
            if (isset($_GET['introspect'])) {
                echo '<a href="', htmlspecialchars($_SERVER['PHP_SELF']),
                    '?introspect=', urlencode(htmlspecialchars($_GET['introspect'])),
                    '">Return to ', htmlspecialchars($_GET['introspect']), '</a><br />';
            }
            echo $source;
            return false;
        } else {
            header("HTTP/1.0 404 Not Found");
            return false;
        }

    }

    public static function introspect($archive, $dir)
    {
        // security, idea borrowed from PHK
        if (!file_exists($archive . '.introspect')) {
            header("HTTP/1.0 404 Not Found");
            return false;
        }
        if (!$dir) {
            $dir = '/';
        }
        $dir = self::processFile($dir);
        if ($dir[0] != '/') {
            $dir = '/' . $dir;
        }
        try {
            $self = htmlspecialchars($_SERVER['PHP_SELF']);
            $iterate = new DirectoryIterator('phar://@ALIAS@' . $dir);
            echo '<html><head><title>Introspect ', htmlspecialchars($dir),
                '</title></head><body><h1>Introspect ', htmlspecialchars($dir),
                '</h1><ul>';
            if ($dir != '/') {
                echo '<li><a href="', $self, '?introspect=',
                    htmlspecialchars(dirname($dir)), '">..</a></li>';
            }
            foreach ($iterate as $entry) {
                if ($entry->isDot()) continue;
                $name = self::processFile($entry->getPathname());
                $name = str_replace('phar://@ALIAS@/', '', $name);
                if ($entry->isDir()) {
                    echo '<li><a href="', $self, '?introspect=',
                        urlencode(htmlspecialchars($name)),
                        '">',
                        htmlspecialchars($entry->getFilename()), '/</a> [directory]</li>';
                } else {
                    echo '<li><a href="', $self, '?introspect=',
                        urlencode(htmlspecialchars($dir)), '&viewsource=',
                        urlencode(htmlspecialchars($name)),
                        '">',
                        htmlspecialchars($entry->getFilename()), '</a></li>';
                }
            }
            return false;
        } catch (Exception $e) {
            echo '<html><head><title>Directory not found: ',
                htmlspecialchars($dir), '</title></head>',
                '<body><h1>Directory not found: ', htmlspecialchars($dir), '</h1>',
                '<p>Try <a href="', htmlspecialchars($_SERVER['PHP_SELF']), '?introspect=/">',
                'This link</a></p></body></html>';
            return false;
        }
    }

    public static function webFrontController($initfile)
    {
        if (isset($_SERVER) && isset($_SERVER['REQUEST_URI'])) {
            $uri = parse_url($_SERVER['REQUEST_URI']);
            $archive = realpath($_SERVER['SCRIPT_FILENAME']);
            $subpath = str_replace('/' . basename($archive), '', $uri['path']);
            if (!$subpath || $subpath == '/') {
                if (isset($_GET['viewsource'])) {
                    return self::viewSource($archive, $_GET['viewsource']);
                }
                if (isset($_GET['introspect'])) {
                    return self::introspect($archive, $_GET['introspect']);
                }
                $subpath = '/' . $initfile;
            }
            if (!self::_fileExists($archive, substr($subpath, 1))) {
                header("HTTP/1.0 404 Not Found");
                return false;
            }
            foreach (self::$deny as $pattern) {
                if (preg_match($pattern, $subpath)) {
                    header("HTTP/1.0 404 Not Found");
                    return false;
                }
            }
            $inf = pathinfo(basename($subpath));
            if (!isset($inf['extension'])) {
                header('Content-Type: text/plain');
                header('Content-Length: ' .
                    self::_filesize($archive, substr($subpath, 1)));
                readfile('phar://@ALIAS@' . $subpath);
                return false;
            }
            if (isset(self::$defaultphp[$inf['extension']])) {
                include 'phar://@ALIAS@' . $subpath;
                return false;
            }
            if (isset(self::$defaultmimes[$inf['extension']])) {
                header('Content-Type: ' . self::$defaultmimes[$inf['extension']]);
                header('Content-Length: ' .
                    self::_filesize($archive, substr($subpath, 1)));
                readfile('phar://@ALIAS@' . $subpath);
                return false;
            }
            if (isset(self::$defaultphps[$inf['extension']])) {
                header('Content-Type: text/html');
                $c = highlight_file('phar://@ALIAS@' . $subpath, true);
                header('Content-Length: ' . strlen($c));
                echo $c;
                return false;
            }
            header('Content-Type: text/plain');
            header('Content-Length: ' .
                    self::_filesize($archive, substr($subpath, 1)));
            readfile('phar://@ALIAS@' . $subpath);
        }
    }

    /**
     * Detect end of stub
     *
     * @param string $buffer stub past '__HALT_'.'COMPILER();'
     * @return end of stub, prior to length of manifest.
     */
    private static function _endOfStubLength($buffer)
    {
        $pos = 0;
        if (!strlen($buffer)) {
            return $pos;
        }
        if (($buffer[0] == ' ' || $buffer[0] == "\n") && @substr($buffer, 1, 2) == '?>')
        {
            $pos += 3;
            if ($buffer[$pos] == "\r" && $buffer[$pos+1] == "\n") {
                $pos += 2;
            }
            else if ($buffer[$pos] == "\n") {
                $pos += 1;
            }
        }
        return $pos;
    }

    /**
     * Allows loading an external Phar archive without include()ing it
     *
     * @param string $file  phar package to load
     * @param string $alias alias to use
     * @throws Exception
     */
    public static final function loadPhar($file, $alias = NULL)
    {
        $file = realpath($file);
        if ($file) {
            $fp = fopen($file, 'rb');
            $buffer = '';
            while (!feof($fp)) {
                $buffer .= fread($fp, 8192);
                // don't break phars
                if ($pos = strpos($buffer, '__HALT_COMPI' . 'LER();')) {
                    $buffer .= fread($fp, 5);
                    fclose($fp);
                    $pos += 18;
                    $pos += self::_endOfStubLength(substr($buffer, $pos));
                    return self::_mapPhar($file, $pos, $alias);
                }
            }
            fclose($fp);
        }
    }

    /**
     * Map a full real file path to an alias used to refer to the .phar
     *
     * This function can only be called from the initialization of the .phar itself.
     * Any attempt to call from outside the .phar or to re-alias the .phar will fail
     * as a security measure.
     * @param string $alias
     * @param int $dataoffset the value of __COMPILER_HALT_OFFSET__
     */
    public static final function mapPhar($alias = NULL, $dataoffset = NULL)
    {
        try {
            $trace = debug_backtrace();
            $file = $trace[0]['file'];
            // this ensures that this is safe
            if (!in_array($file, get_included_files())) {
                die('SECURITY ERROR: PHP_Archive::mapPhar can only be called from within ' .
                    'the phar that initiates it');
            }
            $file = realpath($file);
            if (!isset($dataoffset)) {
                $dataoffset = constant('__COMPILER_HALT_OFFSET'.'__');
                $fp = fopen($file, 'rb');
                fseek($fp, $dataoffset, SEEK_SET);
                $dataoffset = $dataoffset + self::_endOfStubLength(fread($fp, 5));
                fclose($fp);
            }

            self::_mapPhar($file, $dataoffset);
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    /**
     * Sub-function, allows recovery from errors
     *
     * @param unknown_type $file
     * @param unknown_type $dataoffset
     */
    private static function _mapPhar($file, $dataoffset, $alias = NULL)
    {
        $file = realpath($file);
        if (isset(self::$_manifest[$file])) {
            return;
        }
        if (!is_array(self::$_pharMapping)) {
            self::$_pharMapping = array();
        }
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
            throw new Exception('ERROR: manifest length read was "' .
                strlen($manifest) .'" should be "' .
                $manifest_length['len'] . '"');
        }
        $info = self::_unserializeManifest($manifest);
        if ($info['alias']) {
            $alias = $info['alias'];
            $explicit = true;
        } else {
            if (!isset($alias)) {
                $alias = $file;
            }
            $explicit = false;
        }
        self::$_manifest[$file] = $info['manifest'];
        $compressed = $info['compressed'];
        self::$_fileStart[$file] = ftell($fp);
        fclose($fp);
        if ($compressed & 0x00001000) {
            if (!function_exists('gzinflate')) {
                throw new Exception('Error: zlib extension is not enabled - gzinflate() function needed' .
                    ' for compressed .phars');
            }
        }
        if ($compressed & 0x00002000) {
            if (!function_exists('bzdecompress')) {
                throw new Exception('Error: bzip2 extension is not enabled - bzdecompress() function needed' .
                    ' for compressed .phars');
            }
        }
        if (isset(self::$_pharMapping[$alias])) {
            throw new Exception('ERROR: PHP_Archive::mapPhar has already been called for alias "' .
                $alias . '" cannot re-alias to "' . $file . '"');
        }
        self::$_pharMapping[$alias] = array($file, $compressed, $dataoffset, $explicit,
            $info['metadata']);
        self::$_pharFiles[$file] = $alias;
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
        $calcapi = explode('.', self::APIVersion());
        if ($calcapi[0] != $majorcompat) {
            throw new Exception('Phar is incompatible API version ' . $apiver_dots . ', but ' .
                'PHP_Archive is API version '.self::APIVersion());
        }
        if ($calcapi[0] === '0') {
            if (self::APIVersion() != $apiver_dots) {
                throw new Exception('Phar is API version ' . $apiver_dots .
                    ', but PHP_Archive is API version '.self::APIVersion(), E_USER_ERROR);
            }
        }
        $flags = unpack('V', substr($manifest, 6, 4));
        $ret = array('compressed' => $flags[1] & 0x00003000);
        // signature is not verified by default in PHP_Archive, phar is better
        $ret['hassignature'] = $flags[1] & 0x00010000;
        $aliaslen = unpack('V', substr($manifest, 10, 4));
        if ($aliaslen) {
            $ret['alias'] = substr($manifest, 14, $aliaslen[1]);
        } else {
            $ret['alias'] = false;
        }
        $manifest = substr($manifest, 14 + $aliaslen[1]);
        $metadatalen = unpack('V', substr($manifest, 0, 4));
        if ($metadatalen[1]) {
            $ret['metadata'] = unserialize(substr($manifest, 4, $metadatalen[1]));
            $manifest = substr($manifest, 4 + $metadatalen[1]);
        } else {
            $ret['metadata'] = null;
            $manifest = substr($manifest, 4);
        }
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
            // 5 = metadata length
            $ret['manifest'][$savepath] = array_values(unpack('Va/Vb/Vc/Vd/Ve/Vf', substr($manifest, $start, 24)));
            $ret['manifest'][$savepath][3] = sprintf('%u', $ret['manifest'][$savepath][3]
                & 0xffffffff);
            if ($ret['manifest'][$savepath][5]) {
                $ret['manifest'][$savepath][6] = unserialize(substr($manifest, $start + 24,
                    $ret['manifest'][$savepath][5]));
            } else {
                $ret['manifest'][$savepath][6] = null;
            }
            $ret['manifest'][$savepath][7] = $offset;
            $offset += $ret['manifest'][$savepath][2];
            $start += 24 + $ret['manifest'][$savepath][5];
        }
        return $ret;
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
        while ($std != ($std = preg_replace("/[^\/:?]+\/\.\.\//", "", $std))) ;
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
            if ($path[strlen($path)-1] == '/') {
                // directory
                if (!$allowdirs) {
                    return 'Error: "' . $path . '" is a directory in phar "' . $this->_basename . '"';
                }
                $this->_setCurrentFile($path, true);
            } else {
                $this->_setCurrentFile($path);
            }
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

    private function _setCurrentFile($path, $dir = false)
    {
        if ($dir) {
            $this->currentStat = array(
                2 => 040777, // directory mode, readable by all, writeable by none
                4 => 0, // uid
                5 => 0, // gid
                7 => 0, // size
                9 => self::$_manifest[$this->_archiveName][$path][1], // creation time
                );
            $this->internalFileLength = 0;
            $this->isDir = true;
        } else {
            $this->currentStat = array(
                2 => 0100444, // file mode, readable by all, writeable by none
                4 => 0, // uid
                5 => 0, // gid
                7 => self::$_manifest[$this->_archiveName][$path][0], // size
                9 => self::$_manifest[$this->_archiveName][$path][1], // creation time
                );
            $this->internalFileLength = self::$_manifest[$this->_archiveName][$path][2];
            $this->isDir = false;
        }
        $this->currentFilename = $path;
        // seek to offset of file header within the .phar
        if (is_resource(@$this->fp)) {
            fseek($this->fp, self::$_fileStart[$this->_archiveName] + self::$_manifest[$this->_archiveName][$path][7]);
        }
    }

    private static function _fileExists($archive, $path)
    {
        return isset(self::$_manifest[$archive]) &&
            isset(self::$_manifest[$archive][$path]);
    }

    private static function _filesize($archive, $path)
    {
        return self::$_manifest[$archive][$path][0];
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
            if (self::$_manifest[$this->_archiveName][$path][4] & self::GZ) {
                $data = gzinflate($data);
            } elseif (self::$_manifest[$this->_archiveName][$path][4] & self::BZ2) {
                $data = bzdecompress($data);
            }
            if (!isset(self::$_manifest[$this->_archiveName][$path]['ok'])) {
                if (strlen($data) != $this->currentStat[7]) {
                    return array("Not valid internal .phar file (size error {$size} != " .
                        $this->currentStat[7] . ")");
                }
                if (self::$_manifest[$this->_archiveName][$path][3] != sprintf("%u", crc32($data) & 0xffffffff)) {
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
     * Parse urls like phar:///fullpath/to/my.phar/file.txt
     *
     * @param string $file
     * @return false|array
     */
    static protected function parseUrl($file)
    {
        if (substr($file, 0, 7) != 'phar://') {
            return false;
        }
        $file = substr($file, 7);

        $ret = array('scheme' => 'phar');
        $pos_p = strpos($file, '.phar.php');
        $pos_z = strpos($file, '.phar.gz');
        $pos_b = strpos($file, '.phar.bz2');
        if ($pos_p) {
            if ($pos_z) {
                return false;
            }
            $ret['host'] = substr($file, 0, $pos_p + strlen('.phar.php'));
            $ret['path'] = substr($file, strlen($ret['host']));
        } elseif ($pos_z) {
            $ret['host'] = substr($file, 0, $pos_z + strlen('.phar.gz'));
            $ret['path'] = substr($file, strlen($ret['host']));
        } elseif ($pos_b) {
            $ret['host'] = substr($file, 0, $pos_z + strlen('.phar.bz2'));
            $ret['path'] = substr($file, strlen($ret['host']));
        } elseif (($pos_p = strpos($file, ".phar")) !== false) {
            $ret['host'] = substr($file, 0, $pos_p + strlen('.phar'));
            $ret['path'] = substr($file, strlen($ret['host']));
        } else {
            return false;
        }
        if (!$ret['path']) {
            $ret['path'] = '/';
        }
        return $ret;
    }

    /**
     * Locate the .phar archive in the include_path and detect the file to open within
     * the archive.
     *
     * Possible parameters are phar://pharname.phar/filename_within_phar.ext
     * @param string a file within the archive
     * @return string the filename within the .phar to retrieve
     */
    public function initializeStream($file)
    {
        $file = self::processFile($file);
        $info = @parse_url($file);
        if (!$info) {
            $info = self::parseUrl($file);
        }
        if (!$info) {
            return false;
        }
        if (!isset($info['host'])) {
            // malformed internal file
            return false;
        }
        if (!isset(self::$_pharFiles[$info['host']]) &&
              !isset(self::$_pharMapping[$info['host']])) {
            try {
                self::loadPhar($info['host']);
                // use alias from here out
                $info['host'] = self::$_pharFiles[$info['host']];
            } catch (Exception $e) {
                return false;
            }
        }
        if (!isset($info['path'])) {
            return false;
        } elseif (strlen($info['path']) > 1) {
            $info['path'] = substr($info['path'], 1);
        }
        if (isset(self::$_pharMapping[$info['host']])) {
            $this->_basename = $info['host'];
            $this->_archiveName = self::$_pharMapping[$info['host']][0];
            $this->_compressed = self::$_pharMapping[$info['host']][1];
        } elseif (isset(self::$_pharFiles[$info['host']])) {
            $this->_archiveName = $info['host'];
            $this->_basename = self::$_pharFiles[$info['host']];
            $this->_compressed = self::$_pharMapping[$this->_basename][1];
        }
        $file = $info['path'];
        return $file;
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
                break;
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
                do {
                    $isdir = false;
                    if ($file == '/') {
                        break;
                    }
                    foreach (self::$_manifest[$this->_archiveName] as $path => $info) {
                        if (strpos($path, $file) === 0) {
                            if (strlen($path) > strlen($file) &&
                                  $path[strlen($file)] == '/') {
                                break 2;
                            }
                        }
                    }
                    // no files exist and no directories match this string
                    return false;
                } while (false);
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
           'size' => $this->currentStat ? $this->currentStat[7] : 0,
           'atime' => $this->currentStat ? $this->currentStat[9] : 0,
           'mtime' => $this->currentStat ? $this->currentStat[9] : 0,
           'ctime' => $this->currentStat ? $this->currentStat[9] : 0,
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
     * Set a stream option - PHP stream API
     * @param int
     * @param int
     * @param int
     * @access private
     */
    public function stream_set_option($option, $arg1, $arg2)
    {
        // Simply ignore set options.
        return false;
    }

    /**
     * Open a directory in the .phar for reading - PHP streams API
     * @param string directory name
     * @access private
     */
    public function dir_opendir($path)
    {
        $info = @parse_url($path);
        if (!$info) {
            $info = self::parseUrl($path);
            if (!$info) {
                trigger_error('Error: "' . $path . '" is a file, and cannot be opened with opendir',
                    E_USER_ERROR);
                return false;
            }
        }
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
                if ($fname == '/' || $fname[strlen($fname)-1] == '/') {
                    continue; // empty directory
                }
                if (strpos($fname, '/')) {
                    // this is a directory
                    $a = explode('/', $fname);
                    $this->_dirFiles[array_shift($a)] = true;
                } elseif ($file[strlen($path)] == '/') {
                    // this is a file
                    $this->_dirFiles[$fname] = true;
                }
            }
        }
        @fclose($this->fp);
        if (!count($this->_dirFiles)) {
            return false;
        }
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
        return true;
    }

    /**
     * Rewind to the first directory entry - PHP streams API
     * @access private
     */
    public function dir_rewinddir()
    {
        @reset($this->_dirFiles);
        return true;
    }

    /**
     * API version of this class
     * @return string
     */
    public static final function APIVersion()
    {
        return '@API-VER@';
    }

    /**
     * Retrieve Phar-specific metadata for a Phar archive
     *
     * @param string $phar full path to Phar archive, or alias
     * @return null|mixed The value that was serialized for the Phar
     *                    archive's metadata
     * @throws Exception
     */
    public static function getPharMetadata($phar)
    {
        if (isset(self::$_pharFiles[$phar])) {
            $phar = self::$_pharFiles[$phar];
        }
        if (!isset(self::$_pharMapping[$phar])) {
            throw new Exception('Unknown Phar archive: "' . $phar . '"');
        }
        return self::$_pharMapping[$phar][4];
    }

    /**
     * Retrieve File-specific metadata for a Phar archive file
     *
     * @param string $phar full path to Phar archive, or alias
     * @param string $file relative path to file within Phar archive
     * @return null|mixed The value that was serialized for the Phar
     *                    archive's metadata
     * @throws Exception
     */
    public static function getFileMetadata($phar, $file)
    {
        if (!isset(self::$_pharFiles[$phar])) {
            if (!isset(self::$_pharMapping[$phar])) {
                throw new Exception('Unknown Phar archive: "' . $phar . '"');
            }
            $phar = self::$_pharMapping[$phar][0];
        }
        if (!isset(self::$_manifest[$phar])) {
            throw new Exception('Unknown Phar: "' . $phar . '"');
        }
        $file = self::processFile($file);
        if (!isset(self::$_manifest[$phar][$file])) {
            throw new Exception('Unknown file "' . $file . '" within Phar "'. $phar . '"');
        }
        return self::$_manifest[$phar][$file][6];
    }

    /**
     * @return list of supported signature algorithmns.
     */
    public static function getSupportedSignatures()
    {
        $ret = array('MD5', 'SHA-1');
        if (extension_loaded('hash')) {
            $ret[] = 'SHA-256';
            $ret[] = 'SHA-512';
        }
        if (extension_loaded('openssl')) {
            $ret[] = 'OpenSSL';
        }
        return $ret;
    }
}
?>
