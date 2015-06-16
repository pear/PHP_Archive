<?php
/**
 * PHP_Archive Class creator (creates .phar)
 *
 * @package PHP_Archive
 * @category PHP
 */
/**
 * Needed for file manipulation
 */
require_once 'System.php';
require_once 'PHP/Archive.php';
/**
 * PHP_Archive Class creator (implements .phar)
 *
 * PHAR files a singular archive from which an entire application can run.
 * To use it, simply package it using {@see PHP_Archive_Creator} and use phar://
 * URIs to your includes. i.e. require_once 'phar://config.php' will include config.php
 * from the root of the PHAR file.
 *
 * Gz code borrowed from the excellent File_Archive package by Vincent Lascaux.
 *
 * @copyright Copyright ? David Shafik and Synaptic Media 2004. All rights reserved.
 * @author Davey Shafik <davey@synapticmedia.net>
 * @author Greg Beaver <cellog@php.net>
 * @link http://www.synapticmedia.net Synaptic Media
 * @version $Id$
 * @package PHP_Archive
 * @category PHP
 */
class PHP_Archive_Creator
{
    /**
     * @var string The Archive Filename
     */
    protected $archive_name;

    /**
     * @var string The temporary path to the TAR archive
     */
    protected $temp_path;

    /**
     * @var string Where the TAR archive will be saved
     */
    protected $save_path;

    /**
     * @var string The phar alias
     */
    protected $alias;
    
    /**
     * @var boolean Whether or not the archive should be compressed
     */
    protected $compress = false;

    /**
     * Phar bitmapped flags
     *
     * @var int
     */
    protected $flags = 0;
    /**
     * @var boolean Whether or not to collapse (remove whitespace/comments)
     */
    protected $collapse = false;

    /**
     * @var boolean Whether or not a file has been added to the archive
     */
    protected $modified = false;

    /**
     * Used to construct the internal manifest, or listing of files/directories
     *
     * @var array
     */
    protected $manifest = array();

    protected $relyOnPhar = false;
    protected $initFile = false;

    /**
     * Used to save Phar-specific metadata
     * @var mixed
     */
    protected $metadata = null;

    /**
     * Signature type, either PHP_Archive::SHA1 or PHP_Archive::MD5
     *
     * @var int
     */
    protected $sig;

    /**
     * A list of custom callbacks that should be used for manipulating file contents
     * prior to adding to the phar.
     *
     * @var array
     */
    private $_magicRequireCallbacks = array();

    /**
     * @param string
     */
    public static function processFile($path)
    {
        if ($path == '.') {
            return '';
        }
        $std = str_replace("\\", "/", $path);
        while ($std != ($std = preg_replace("/[^\/:?]+\/\.\.\//", "", $std)));
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
     * PHP_Archive Constructor
     *
     * @param string|false $init_file Init file (file called by default upon PHAR execution).
     *                                if false, none will be called, and execution will return.
     *                                use this option for libraries
     * @param string $alias alias name like "go-pear.phar" to be used for opening
     *                      files from this phar
     * @param string|false $compress Whether to compress the files or not (will cause slowdown!)
     *                               use 'gz' for zlib compression, 'bz2' for bzip2 compression
     * @param bool $relyOnPhar if true, then a slim, phar extension-dependent .phar will be
     *                         created
     * @param bool $collapse Remove whitespace and comments from PHP_Archive class
     */
    public function __construct($init_file = 'index.php', $alias, $compress = false,
                                $relyOnPhar = false, $collapse = false)
    {
        $this->compress = $compress;
        $this->collapse = $collapse;
        $this->relyOnPhar = $relyOnPhar;
        $this->initFile = $init_file;
        $this->temp_path = System::mktemp(array('-d', 'phr'));
        $contents = file_get_contents(dirname(dirname(__FILE__)) .
            DIRECTORY_SEPARATOR . 'Archive.php');
        if ($this->collapse) {
            $contents = self::collapse($contents);
        }
        $contents = trim(str_replace(array('<?php', '?>'), array('', ''), $contents));
        // make sure .phars added to CVS don't get checksum errors because of CVS tags
        $contents = str_replace('* @version $Id', '* @version Id', $contents);
        $unpack_code = "<?php
error_reporting(1803);
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('ASCII');
}
";
        if (!$relyOnPhar) {
            // for smooth use of phar extension
            $unpack_code .= "if (!class_exists('PHP_Archive')) {";
            $unpack_code .= $contents;
            $unpack_code .= "}
if (!class_exists('Phar')) {
    PHP_Archive::mapPhar(null, __COMPILER_HALT_OFFSET__);
} else {
    try {
        Phar::mapPhar();
    } catch (Exception \$e) {
        echo \$e->getMessage();
    }
}
if (class_exists('PHP_Archive') && !in_array('phar', stream_get_wrappers())) {
    stream_wrapper_register('phar', 'PHP_Archive');
}
";
        } else {
            $unpack_code .= "if (!extension_loaded('phar')) {";
            $unpack_code .= 'die("Error - phar extension not loaded");
}
try {
    Phar::mapPhar();
} catch (Exception \$e) {
    echo \$e->getMessage();
}
';
        }
        $unpack_code .= "\n@ini_set('memory_limit', -1);\n";

        $this->alias = $alias;
        if ($init_file) {
            $unpack_code .= '

require_once \'phar://@ALIAS@/' . addslashes($init_file) . '\';
';
        }
        $unpack_code .= '__HALT_COMPILER();';
        file_put_contents($this->temp_path . DIRECTORY_SEPARATOR . 'loader.php', $unpack_code);
    }

    /**
     * Set meta-data for entire Phar archive
     *
     * @param mixed $metadata
     */
    public function setPharMetadata($metadata)
    {
        $this->_metadata = $metadata;
    }

    /**
     * Append a signature to this phar when it is created
     */
    public function useSHA1Signature()
    {
        $this->flags |= PHP_Archive::SIG;
        $this->sig = PHP_Archive::SHA1;
    }

    /**
     * Append a signature to this phar when it is created
     */
    public function useMD5Signature()
    {
        $this->flags |= PHP_Archive::SIG;
        $this->sig = PHP_Archive::MD5;
    }

    /**
     * Specify a custom "magic require" callback for processing file contents.
     * 
     * This will be called regardless of the magicrequire parameter's
     * value for {@link addString()} or {@link addFile()}
     *
     * @param callback $callback
     */
    public function addMagicRequireCallback($callback)
    {
        if (is_callable($callback)) {
            $this->_magicRequireCallbacks[] = $callback;
        }
    }

    /**
     * Add a file to the PHP Archive
     *
     * @param string $file Path of the File to add
     * @param string $save_path The save location of the file in the archive
     * @param false  $magicrequire unused, set this to false
     * @param mixed  $metadata Any file-specific metadata to save
     * @return boolean
     */
    
    public function addFile($file, $save_path, $magicrequire = false, $metadata = null)
    {
        return $this->addString(file_get_contents($file), $save_path, $magicrequire, $metadata);
    }

    /**
     * For web-based applications, construct a default front controller
     * that will direct to the correct file within the phar.
     *
     * @param string  $indexfile    relative path to startup index file (defaults to the same file as
     *                              is used for CLI startup)
     * @param array   $defaultmimes list of MIME types to use, associative array of
     *                              extension => mime type. default is
     *                              from {@link PHP_Archive::$defaultmimes}
     * @param array   $defaultphp   list of file extensions that should be parsed as PHP. default is
     *                              from {@link PHP_Archive::$defaultphp}
     * @param array   $defaultphps  list of file extensions that should be parsed as PHP source. default is
     *                              from {@link PHP_Archive::$defaultphps}
     * @param array   $deny         list of files that should be hidden (return a 404 response)
     *                              Each file should be a valid pcre regular expression like '/.+\.inc$/',
     *                              which will deny all .inc files from being served.  The default is
     *                              from {@link PHP_Archive::$deny}
     */
    public function useDefaultFrontController($indexfile = false, $defaultmimes = false, $defaultphp = false,
                    $defaultphps = false, $deny = false)
    {
        if (!$indexfile) {
            $indexfile = $this->initFile;
        }
        $contents = file_get_contents($this->temp_path . DIRECTORY_SEPARATOR . 'loader.php');
        if ($this->relyOnPhar) {
            if (!$defaultmimes) {
                $defaultmimes = PHP_Archive::$defaultmimes;
            }
            if (!$defaultphp) {
                $defaultphp = PHP_Archive::$defaultphp;
            }
            if (!$defaultphps) {
                $defaultphps = PHP_Archive::$defaultphps;
            }
            if (!$deny) {
                $deny = PHP_Archive::$deny;
            }
            $templatefile = '@data_dir@/PHP_Archive/data/phar_frontcontroller.tpl';
            $template = file_get_contents($templatefile);
            if (!$template) {
                throw new PHP_Archive_Exception('Invalid template file "' . $templatefile . '"');
            }
            $template = str_replace('@mime@', var_export($defaultmimes, true), $template);
            $template = str_replace('@php@', var_export($defaultphp, true), $template);
            $template = str_replace('@phps@', var_export($defaultphps, true), $template);
            $template = str_replace('@alias@', $this->alias, $template);
            $template = str_replace('@deny@', var_export($deny, true), $template);
            $template = str_replace('@initfile@', $this->initFile, $template);
            $contents = str_replace("@ini_set('memory_limit', -1);",
                "@ini_set('memory_limit', -1);\n" . $template . "\n");
        } else {
            $extra = '';
            if ($defaultmimes || $defaultphp || $defaultphps || $deny) {
                if ($defaultmimes) {
                    $extra .= "\nPHP_Archive::\$defaultmimes = " .
                        var_export($defaultmimes, true) . "\n";
                }
                if ($defaultphp) {
                    $extra .= "\nPHP_Archive::\$defaultphp = " .
                        var_export($defaultphp, true) . "\n";
                }
                if ($defaultphps) {
                    $extra .= "\nPHP_Archive::\$defaultphps = " .
                        var_export($defaultphps, true) . "\n";
                }
                if ($deny) {
                    $extra .= "\nPHP_Archive::\$deny = " .
                        var_export($deny, true) . "\n";
                }
            }
            // if Phar extension is present, use the template code instead
            if (!$defaultmimes) {
                $defaultmimes = PHP_Archive::$defaultmimes;
            }
            if (!$defaultphp) {
                $defaultphp = PHP_Archive::$defaultphp;
            }
            if (!$defaultphps) {
                $defaultphps = PHP_Archive::$defaultphps;
            }
            if (!$deny) {
                $deny = PHP_Archive::$deny;
            }
            $templatefile = '@data_dir@/PHP_Archive/data/phar_frontcontroller.tpl';
            $template = file_get_contents($templatefile);
            if (!$template) {
                throw new PHP_Archive_Exception('Invalid template file "' . $templatefile . '"');
            }
            $template = str_replace('@mime@', var_export($defaultmimes, true), $template);
            $template = str_replace('@php@', var_export($defaultphp, true), $template);
            $template = str_replace('@phps@', var_export($defaultphps, true), $template);
            $template = str_replace('@alias@', $this->alias, $template);
            $template = str_replace('@deny@', var_export($deny, true), $template);
            $template = str_replace('@initfile@', $indexfile, $template);
            
            $contents = str_replace("@ini_set('memory_limit', -1);",
                "@ini_set('memory_limit', -1);\n" .
                'if (extension_loaded(\'phar\')) {' . $template . '} else {' .
                $extra .
                "if (!empty(\$_SERVER['REQUEST_URI'])) " .
                "{PHP_Archive::webFrontController('" .
                addslashes($indexfile) . "');exit;}}\n", $contents);
        }
        file_put_contents($this->temp_path . DIRECTORY_SEPARATOR . 'loader.php',
            $contents);
    }

    /**
     * Add a string to the PHP Archive as a file
     *
     * @param string $file_contents Contents of the File to add
     * @param string $save_path The save location of the file in the archive
     * @return boolean
     */
    
    public function addString($file_contents, $save_path, $magicrequire = false,
                              $metadata = null)
    {
        $save_path = self::processFile($save_path);
        if (count($this->_magicRequireCallbacks)) {
            foreach ($this->_magicRequireCallbacks as $callback) {
                $file_contents = call_user_func($callback, $file_contents, $save_path);
            }
        }
        if ($magicrequire) {
            die('ERROR: magicrequire is removed, set a magicrequire callback to ' .
                'array("PHP_Archive_Creator", "simpleMagicRequire) to implement');
        }
        if (!file_exists($this->temp_path . DIRECTORY_SEPARATOR . 'contents')) {
            mkdir($this->temp_path . DIRECTORY_SEPARATOR . 'contents');
        }
        $size = strlen($file_contents);
        $crc32 = crc32($file_contents);
        // save crc32 of file and the uncompressed file size, so we
        // can do a sanity check on the file when opening it from the phar
        if ($this->compress) {
            if ($this->compress == 'gz') {
                $this->flags |= PHP_Archive::GZ;
                $file_contents = gzdeflate($file_contents, 9);
            } elseif ($this->compress == 'bz2') {
                $this->flags |= PHP_Archive::BZ2;
                $file_contents = bzcompress($file_contents, 9);
            }
        }
        System::mkdir(array('-p', dirname($this->temp_path . DIRECTORY_SEPARATOR . 'contents' .
            DIRECTORY_SEPARATOR . $save_path)));
        if (file_exists($this->temp_path . DIRECTORY_SEPARATOR . 'contents' .
              DIRECTORY_SEPARATOR . $save_path)) {
            die('ERROR: path "' . $save_path . '" already exists');
        }
        file_put_contents($this->temp_path . DIRECTORY_SEPARATOR . 'contents' .
            DIRECTORY_SEPARATOR . $save_path, $file_contents);
        $flags = 0;
        if ($this->compress) {
            $flags |= ($this->compress == 'gz') ? 0x00001000 : 0x00002000;
        }
        $flags |= 0555; // file permissions
        $this->manifest[$save_path] =
            array(
                'tempfile' => $this->temp_path . DIRECTORY_SEPARATOR . 'contents' .
                    DIRECTORY_SEPARATOR . $save_path,
                'originalsize' => $size,
                'actualsize' => strlen($file_contents),
                'crc32' => $crc32,
                'flags' => $flags,
                'metadata' => $metadata);
    }

    public function clearMagicRequire()
    {
        $this->_magicRequireCallbacks = array();
    }

    /**
     * prepends all include/require calls with "phar://alias"
     *
     * @param string $contents file contents
     * @param string $path internal path of the file
     */
    public function simpleMagicRequire($contents, $path)
    {
        $file_contents = str_replace("require_once '", "require_once 'phar://" . $this->alias . "/",
            $contents);
        $file_contents = str_replace("include_once '", "include_once 'phar://" . $this->alias . "/",
            $file_contents);
        return $file_contents;
    }

    /**
     * prepends "phar://alias" only to include/require that use quotes
     *
     * @param string $contents file contents
     * @param string $path internal path of the file
     */
    public function limitedSmartMagicRequire($contents, $path)
    {
        $file_contents = preg_replace(
            '/(include|require)(_once)?\s*((?:\'|")[^;]+);/',
            '$1$2 \'phar://' . $this->alias . '/\' . $3;', $contents);
        return $file_contents;
    }

    /**
     * The basic magicrequire callback (implements old-fashioned magicrequire)
     *
     * @param string $contents file contents
     * @param string $path internal path of the file
     */
    public function smartMagicRequire($contents, $path)
    {
        $file_contents = preg_replace(
            '/(include|require)(_once)?([^;]+);/',
            '$1$2 \'phar://' . $this->alias . '/\' . $3', $contents);
        return $file_contents;
    }

    public function tokenMagicRequire($contents, $path)
    {
        $info = pathinfo($path);
        if (!isset($info['extension'])) {
            return $contents;
        }
        if ($info['extension'] != 'php') {
            return $contents;
        }
        $alias = '\'phar://' . $this->alias . '/\' . ';
        $contents = token_get_all($contents);
        $inphp = false;
        $finished = '';
        for ($i = 0; $i < count($contents); $i++) {
            $token = $contents[$i];
            if (!$inphp) {
                if (!is_array($token)) {
                    $finished .= $token;
                    continue;
                }
                if ($token[0] == T_OPEN_TAG) {
                    $finished .= $token[1];
                    $inphp = true;
                    continue;
                }
            }
            if (!is_array($token)) {
                $finished .= $token;
                continue;
            }
            if ($token[0] == T_CLOSE_TAG) {
                $finished .= $token[1];
                $inphp = false;
                continue;
            }
            if ($token[0] != T_INCLUDE && $token[0] != T_INCLUDE_ONCE &&
                  $token[0] != T_REQUIRE && $token[0] != T_REQUIRE_ONCE) {
                $finished .= $token[1];
                continue;
            }
            $finished .= $token[1];
            $new = '';
            $done = false;
            $token = $contents[++$i];
            for (;!$done;$i++) {
                $token = $contents[$i];
                if (!is_array($token)) {
                    if ($token == '(' || $token == '{' || $token == '"' ||
                          $token == "'") {
                        $finished .= ' ' . $alias . $token;
                        continue 2;
                    }
                    $finished .= $token;
                    // we have some oddness, don't touch this with a 10-foot pole
                    continue 2;
                }
                if ($token[0] == T_WHITESPACE) {
                    $finished .= $token[1];
                    continue;
                }
                $done = true;
                $finished .= $alias . $token[1];
                $i--;
            }
        }
        return $finished;
    }
    /**
     * Tell whether to ignore a file or a directory
     * allows * and ? wildcards
     *
     * @param    string  $file    just the file name of the file or directory,
     *                          in the case of directories this is the last dir
     * @param    string  $path    the full path
     * @param    1|0    $return  value to return if regexp matches.  Set this to
     *                            false to include only matches, true to exclude
     *                            all matches
     * @return   bool    true if $path should be ignored, false if it should not
     */
    private function _checkIgnore($file, $path, $return = 1)
    {
        if (file_exists($path)) {
            $path = realpath($path);
        }
        if (is_array($this->ignore[$return])) {
            foreach($this->ignore[$return] as $match) {
                // match is an array if the ignore parameter was a /path/to/pattern
                if (is_array($match)) {
                    // check to see if the path matches with a path delimiter appended
                    preg_match('/^' . strtoupper($match[0]).'$/', strtoupper($path) . '/',$find);
                    if (!count($find)) {
                        // check to see if it matches without an appended path delimiter
                        preg_match('/^' . strtoupper($match[0]).'$/', strtoupper($path), $find);
                    }
                    if (count($find)) {
                        // check to see if the file matches the file portion of the regex string
                        preg_match('/^' . strtoupper($match[1]).'$/', strtoupper($file), $find);
                        if (count($find)) {
                            return $return;
                        }
                    }
                    // check to see if the full path matches the regex
                    preg_match('/^' . strtoupper($match[0]).'$/',
                               strtoupper($path . DIRECTORY_SEPARATOR . $file), $find);
                    if (count($find)) {
                        return $return;
                    }
                } else {
                    // ignore parameter was just a pattern with no path delimiters
                    // check it against the path
                    preg_match('/^' . strtoupper($match).'$/', strtoupper($path), $find);
                    if (count($find)) {
                        return $return;
                    }
                    // check it against the file only
                    preg_match('/^' . strtoupper($match).'$/', strtoupper($file), $find);
                    if (count($find)) {
                        return $return;
                    }
                }
            }
        }
        return !$return;
    }
    
    /**
     * Construct the {@link $ignore} array
     * @param array strings of files/paths/wildcards to ignore
     * @param 0|1 0 = files to include, 1 = files to ignore
     */
    private function _setupIgnore($ignore, $index)
    {
        $ig = array();
        if (is_array($ignore)) {
            for($i=0; $i<count($ignore);$i++) {
                $ignore[$i] = strtr($ignore[$i], "\\", "/");
                $ignore[$i] = str_replace('//','/',$ignore[$i]);

                if (!empty($ignore[$i])) {
                    if (!is_numeric(strpos($ignore[$i], '/'))) {
                        $ig[] = $this->_getRegExpableSearchString($ignore[$i]);
                    } else {
                        if (basename($ignore[$i]) . '/' == $ignore[$i]) {
                            $ig[] = $this->_getRegExpableSearchString($ignore[$i]);
                        } else {
                            $ig[] = array($this->_getRegExpableSearchString($ignore[$i]),
                                      $this->_getRegExpableSearchString(basename($ignore[$i])));
                        }
                    }
                }
            }
            if (count($ig)) {
                $this->ignore[$index] = $ig;
            } else {
                $this->ignore[$index] = false;
            }
        } else $this->ignore[$index] = false;
    }
    
    /**
     * Converts $s into a string that can be used with preg_match
     * @param string $s string with wildcards ? and *
     * @return string converts * to .*, ? to ., etc.
     */
    private function _getRegExpableSearchString($s)
    {
        $y = '\/';
        if (DIRECTORY_SEPARATOR == '\\') {
            $y = '\\\\';
        }
        $s = str_replace('/', DIRECTORY_SEPARATOR, $s);
        $x = strtr($s, array('?' => '.','*' => '.*','.' => '\\.','\\' => '\\\\','/' => '\\/',
                                '[' => '\\[',']' => '\\]','-' => '\\-'));
        if (strpos($s, DIRECTORY_SEPARATOR) !== false &&
              strrpos($s, DIRECTORY_SEPARATOR) === strlen($s) - 1) {
            $x = "(?:.*$y$x?.*|$x.*)";
        }
        return $x;
    }

    /**
     * Test whether an entry should be processed.
     * 
     * Normally, it ignores all files and directories that begin with "."  addhiddenfiles option
     * instead only ignores "." and ".." entries
     * @param string directory name of entry
     * @param string name
     */
    private function _testFile($directory, $entry)
    {
        return is_file($directory . '/' . $entry) ||
              (is_dir($directory . '/' . $entry) && !in_array($entry, array('.', '..')));
    }

    /**
     * Retrieve a listing of every file in $directory and
     * all subdirectories.
     *
     * The return format is an array of full paths to files
     * @access protected
     * @return array list of files in a directory
     * @param string $directory full path to the directory you want the list of
     */
    public function dirList($directory, $toplevel = null)
    {
        if ($toplevel === null) {
            $toplevel = $directory;
        }
        $ret = false;
        $dirname = str_replace($toplevel . DIRECTORY_SEPARATOR, '', $directory);
        $dirname = str_replace($toplevel, '', $dirname);
        if ($dirname) {
            $dirname .= DIRECTORY_SEPARATOR;
        }
        if (@is_dir($directory)) {
            $ret = array();
            $d = @dir($directory);
            while($d && false !== ($entry = $d->read())) {
                if ($this->_testFile($directory, $entry)) {
                    if (is_file($directory . '/' . $entry)) {
                        // if include option was set, then only pass included files
                        if ($this->ignore[0]) {
                            if ($this->_checkIgnore($entry, $directory . '/' . $entry, 0)) {
                                continue;
                            }
                        }
                        // if ignore option was set, then only pass included files
                        if ($this->ignore[1]) {
                            if ($this->_checkIgnore($entry, $directory . '/' . $entry, 1)) {
                                continue;
                            }
                        }
                        $ret[$directory . DIRECTORY_SEPARATOR . $entry] = $dirname . $entry;
                    }
                    if (is_dir($directory . '/' . $entry)) {
                        $tmp = $this->dirList($directory . DIRECTORY_SEPARATOR . $entry, $toplevel);
                        if (is_array($tmp)) {
                            foreach($tmp as $i => $ent) {
                                $ret[$i] = $ent;
                            }
                        }
                    }
                }
            }
            if ($d) {
                $d->close();
            }
        } else {
            return false;
        }
        return $ret;
    }

    /**
     * Add a directory to the archive
     *
     * @param string The directory path to add
     * @param array  files to ignore
     * @param array  files to include (all others ignored)
     * @param bool   If set, then "require_once '" will be replaced with
     *               "require_once 'phar://$magicrequire/" [deprecated]
     * @param string Directory to consider as the top-level directory
     * @return boolean
     */
    
    public function addDir($dir, $ignore = array(), $include = array(), $magicrequire = false,
                           $toplevel = null)
    {
        $this->_setupIgnore($ignore, 1);
        $this->_setupIgnore($include, 0);
        $list = $this->dirList($dir, $toplevel);
        return $this->addArray($list, $magicrequire);
    }

    /**
     * Collapse a block of code (remove whitespace and comments)
     *
     * @param string $contents PHP code to collapse
     * @return string
     * @author Sean Coates <sean@php.net>
     */
    private static function collapse($contents)
    {
        $ret = '';
        $tokens = token_get_all($contents);
        foreach ($tokens as $t) {
            if (is_string($t)) {
                $ret .= $t;
            } else {
                list($token, $data) = $t;
                if ($token == T_WHITESPACE) {
                    if (strpos($data, "\n") !== false) {
                        $data = "\n";
                    } else {
                        $data = ' ';
                    }
                } elseif ($token == T_COMMENT || $token == T_DOC_COMMENT) {
                    $data = '';
                }
                $ret .= $data;
            }
        }
        return $ret;
    }

    /**
     * add an array of files to the archive
     *
     * @param unknown_type $files
     * @param bool $magicrequire determines whether to attempt to replace all
     *                           calls to require or include with internal
     *                           phar includes
     * @return unknown
     */
    public function addArray($files, $magicrequire = false)
    {
        if (!is_array($files) || empty($files)) {
            return false;
        }
        foreach ($files as $file_path => $save_path) {
            $returns[] = $this->addFile($file_path, $save_path, $magicrequire);
        }
        return !in_array(false, $returns);
    }

    /**
     * Construct the .phar and save it
     *
     * @param string $file_path
     * @return bool success of operation
     */
    public function savePhar($file_path)
    {
        uksort($this->manifest, 'strnatcasecmp');
        $newfile = fopen($file_path, 'wb');
        if (!$newfile) {
            return false;
        }
        if (isset($this->alias)) {
            $loader = file_get_contents($this->temp_path . DIRECTORY_SEPARATOR . 'loader.php');
            $loader = str_replace('@ALIAS@', addslashes($this->alias), $loader);
        } else {
            $loader = file_get_contents($this->temp_path . DIRECTORY_SEPARATOR . 'loader.php');
            $loader = str_replace('@ALIAS@', addslashes(basename($file_path)), $loader);
            $this->alias = addslashes(basename($file_path));
        }
        $loader = str_replace('__COMPILER_HALT_OFFSET__', str_pad(strlen($loader),
            strlen('__COMPILER_HALT_OFFSET__')), $loader);
        fwrite($newfile, $loader);

        // relative offset from end of manifest
        $offset = 0;
        $manifest = array();
        // create the manifest
        foreach ($this->manifest as $savepath => $info) {
            $size = $info['originalsize'];
            $manifest[$savepath] = array(
                $size, // original size = 0
                time(), // save time = 1
                $info['actualsize'], // actual size in the .phar = 2
                $info['crc32'], // crc32 = 3
                $info['flags'], // flags = 4
                $info['metadata']); // metadata = 5
            $offset += $info['actualsize'];
        }
        $manifest = $this->serializeManifest($manifest);
        fwrite($newfile, $manifest);
        // save each file
        foreach ($this->manifest as $savepath => $info) {
            $file = fopen($info['tempfile'], 'rb');
            stream_copy_to_stream($file, $newfile);
            fclose($file);
        }
        fclose($newfile);
        if ($this->flags & PHP_Archive::SIG) {
            if ($this->sig == PHP_Archive::SHA1) {
                $sig = sha1_file($file_path, true);
            } elseif ($this->sig == PHP_Archive::MD5) {
                $sig = md5_file($file_path, true);
            }
            $fp = fopen($file_path, 'ab');
            fwrite($fp, $sig);
            // add signature indicator plus the magic indicator
            // ah to be immortalized in file format
            fwrite($fp, pack('V', $this->sig) . 'GBMB');
            fclose($fp);
        }
        return true;
    }

    /**
     * serialize the manifest in a C-friendly way
     *
     * @param array $manifest An array like so:
     * <code>
     *   $manifest[] = array(
     *      $savepath,
     *      $size, // original size = 0
     *      time(), // save time = 1
     *      $offset, // offset from start of files = 2
     *      $info['actualsize']); // actual size in the .phar = 3
     * </code>
     * @return string
     */
    public function serializeManifest($manifest)
    {
        $apiver = explode('.', '@API-VER@');
        // store API version and compression in 2 bytes
        $apiver = chr(((int) ((int) $apiver[0]) << 4) + ((int) $apiver[1])) .
            chr((int)($apiver[2] << 4) + ($this->compress ? 0x1 : 0));
        $ret = $apiver;
        $ret .= pack('V', $this->flags);
        $ret .= pack('V', strlen($this->alias)) . $this->alias;
        if ($this->metadata === null) {
            $ret .= pack('V', 0);
        } else {
            $metadata = serialize($this->metadata);
            $ret .= pack('V', strlen($metadata)) . $metadata;
        }
        foreach ($manifest as $savepath => $info) {
            // save the string length, then the string, then this info
            // uncompressed file size
            // save timestamp
            // compressed file size
            // crc32
            // flags
            // metadata
            $metadata = array_pop($info);
            $ret .= pack('V', strlen($savepath)) . $savepath . call_user_func_array('pack',
                array_merge(array('VVVVV'), $info));
            if ($metadata === null) {
                $ret .= pack('V', 0);
            } else {
                $metadata = serialize($metadata);
                $ret .= pack('V', strlen($metadata)) . $metadata;
            }
        }
        // save the size of the manifest
        return pack('VV', strlen($ret) + 4, count($manifest)) . $ret;
    }
}
?>