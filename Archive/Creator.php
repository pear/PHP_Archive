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
 * @copyright Copyright © David Shafik and Synaptic Media 2004. All rights reserved.
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
     * @var boolean Whether or not a file has been added to the archive
     */
    protected $modified = false;

    /**
     * Used to construct the internal manifest, or listing of files/directories
     *
     * @var array
     */
    protected $manifest = array();

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
     * @param string $alias alias name like "go-pear.phar" to be used for opening
     *                      files from this phar
     * @param bool $relyOnPhar if true, then a slim, phar extension-dependent .phar will be
     *                         created
     */
    public function __construct($init_file = 'index.php', $compress = false,
                                $allow_direct_access = false, $alias = null, $relyOnPhar = false)
    {
        $this->compress = $compress;
        $this->temp_path = System::mktemp(array('-d', 'phr'));
        $contents = trim(str_replace(array('<?php', '?>'), array('', ''),
            file_get_contents(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'Archive.php')));
        // make sure .phars added to CVS don't get checksum errors because of CVS tags
        $contents = str_replace('* @version $Id', '* @version Id', $contents);
        $unpack_code = "<?php #PHP_ARCHIVE_HEADER-@API-VER@
error_reporting(E_ALL);
if (!class_exists('PHP_Archive')) {
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('ASCII');
}
";
        if ($relyOnPhar) {
            $unpack_code .= $contents;
            $unpack_code .= "if (!function_exists('stream_get_wrappers')) {function stream_get_wrappers(){return array();}}
if (!in_array('phar', stream_get_wrappers())) {
    stream_wrapper_register('phar', 'PHP_Archive');
}
";
        } else {
            $unpack_code .= 'die("Error - phar extension not loaded");';
        }
        $unpack_code .= <<<PHP
}
if (PHP_Archive::APIVersion() != '@API-VER@') {
die('Error: PHP_Archive must be API version @API-VER@');
}
@ini_set('memory_limit', -1);
PHP;

        $this->alias = $alias;
        $alias = $this->alias ? $this->alias : '@ALIAS@';
        $unpack_code .= 'PHP_Archive::mapPhar("' . addslashes($alias) . '", ' .
            ($compress ? 'true' : 'false') . (!$relyOnPhar ? ', __FILE__, __COMPILER_HALT_OFFSET__' : '') . ');';
        if (!$allow_direct_access) {
            $unpack_code .= <<<PHP

require_once 'phar://' . basename(__FILE__) . '/$init_file';
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
        
if (count(get_included_files()) > 1) {
    return;
} else {
    exit;
}
?>
<?php __HALT_COMPILER();
PHP;
        file_put_contents($this->temp_path . DIRECTORY_SEPARATOR . 'loader.php', $unpack_code);
    }

    /**
     * Add a file to the PHP Archive
     *
     * @param string $file Path of the File to add
     * @param string $save_path The save location of the file in the archive
     * @return boolean
     */
    
    public function addFile($file, $save_path, $magicrequire = false)
    {
        return $this->addString(file_get_contents($file), $save_path, $magicrequire);
    }

    /**
     * Add a string to the PHP Archive as a file
     *
     * @param string $file_contents Contents of the File to add
     * @param string $save_path The save location of the file in the archive
     * @return boolean
     */
    
    public function addString($file_contents, $save_path, $magicrequire = false)
    {
        $save_path = self::processFile($save_path);
        if ($magicrequire) {
            $file_contents = str_replace("require_once '", "require_once 'phar://$magicrequire/",
                $file_contents);
            $file_contents = str_replace("include_once '", "include_once 'phar://$magicrequire/",
                $file_contents);
        }
        if (!file_exists($this->temp_path . DIRECTORY_SEPARATOR . 'contents')) {
            mkdir($this->temp_path . DIRECTORY_SEPARATOR . 'contents');
        }
        $size = strlen($file_contents);
        // save crc32 of file and the uncompressed file size, so we
        // can do a sanity check on the file when opening it from the phar
        $file_contents =
            pack("VV",crc32($file_contents),strlen($file_contents)) .
            ($this->compress ? gzdeflate($file_contents, 9) : $file_contents);
        System::mkdir(array('-p', dirname($this->temp_path . DIRECTORY_SEPARATOR . 'contents' .
            DIRECTORY_SEPARATOR . $save_path)));
        if (file_exists($this->temp_path . DIRECTORY_SEPARATOR . 'contents' .
              DIRECTORY_SEPARATOR . $save_path)) {
            die('ERROR: path "' . $save_path . '" already exists');
        }
        file_put_contents($this->temp_path . DIRECTORY_SEPARATOR . 'contents' .
            DIRECTORY_SEPARATOR . $save_path, $file_contents);
        $this->manifest[$save_path] =
            array(
                'tempfile' => $this->temp_path . DIRECTORY_SEPARATOR . 'contents' .
                    DIRECTORY_SEPARATOR . $save_path,
                'originalsize' => $size,
                'actualsize' => strlen($file_contents));
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
     *               "require_once 'phar://$magicrequire/"
     * @return boolean
     */
    
    public function addDir($dir, $ignore = array(), $include = array(), $magicrequire = false)
    {
        $this->_setupIgnore($ignore, 1);
        $this->_setupIgnore($include, 0);
        $list = $this->dirList($dir);
        return $this->addArray($list, $magicrequire);
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
        $loader = fopen($this->temp_path . DIRECTORY_SEPARATOR . 'loader.php', 'rb');
        if (isset($this->alias)) {
            stream_copy_to_stream($loader, $newfile);
            fclose($loader);
        } else {
            fclose($loader);
            $loader = file_get_contents($this->temp_path . DIRECTORY_SEPARATOR . 'loader.php');
            $loader = str_replace('@ALIAS@', addslashes(basename($file_path)), $loader);
            fwrite($newfile, $loader);
        }
        // relative offset from end of manifest
        $offset = 0;
        $manifest = array();
        // create the manifest
        foreach ($this->manifest as $savepath => $info) {
            $size = $info['originalsize'];
            $manifest[$savepath] = array(
                $size, // original size = 0
                time(), // save time = 1
                $offset, // offset from start of files = 2
                $info['actualsize']); // actual size in the .phar = 3
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
        $ret = '';
        foreach ($manifest as $savepath => $info) {
            // save the string length, then the string, then this info
            // uncompressed file size
            // save timestamp
            // byte offset from the start of internal files within the phar
            // compressed file size (actual size in the phar)
            $ret .= pack('V', strlen($savepath)) . $savepath . call_user_func_array('pack',
                array_merge(array('VVVV'), $info));
        }
        // save the size of the manifest
        return pack('VV', strlen($ret) + 4, count($manifest)) . $ret;
    }
}
?>