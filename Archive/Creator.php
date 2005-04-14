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
 * Require System, for temporary dir functions
 */
require_once 'System.php';

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
        $this->temp_path = System::mktemp('phr');
        $tar = new Archive_Tar($this->temp_path);
        $contents = trim(str_replace(array('<?php', '?>'), array('', ''),
            file_get_contents(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'Archive.php')));
        // make sure .phars added to CVS don't get checksum errors because of CVS tags
        $contents = str_replace('* @version $Id', '* @version Id', $contents);
        $unpack_code = <<<PHP
        
        
        
        
        
        
        
        

if (!class_exists('PHP_Archive')) {
$contents
}
if (PHP_Archive::APIVersion() != '0.5') {
die('Error: PHP_Archive must be API version 0.5 - use bundled PHP_Archive for success');
}
if (!in_array('phar', stream_get_wrappers())) {
    stream_wrapper_register('phar', 'PHP_Archive');
}
PHP;

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
<?php __HALT_PHP_PARSER__; ?>

PHP;
        $tar->addString('<?php #PHP_ARCHIVE_HEADER-0.5.0.php', $unpack_code);
        
        $this->code = $unpack_code;
        
        $this->tar =& $tar;
    }
    
    /**
     * PHP4 Compatible Constructor
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
    
    function addFile($file, $save_path, $magicrequire = false)
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
    
    function addString($file_contents, $save_path, $magicrequire = false)
    {
        if ($magicrequire) {
            $file_contents = str_replace("require_once '", "require_once 'phar://$magicrequire/",
                $file_contents);
            $file_contents = str_replace("include_once '", "include_once 'phar://$magicrequire/",
                $file_contents);
        }
        if ($this->compress) {
            $file_contents = '1' . base64_encode(
                pack("C1C1C1C1VC1C1", 0x1f, 0x8b, 8, 0, time(), 2, 0xFF) .
                gzdeflate($file_contents, 9) .
                pack("VV",crc32($file_contents),strlen($file_contents)));
        } else {
            $file_contents = '0' . $file_contents;
        }
        clearstatcache(); // a newly created archive could be erased if this is not performed
        return $this->tar->addString($save_path, $file_contents);
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
     * @access private
     */
    function _checkIgnore($file, $path, $return = 1)
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
     * @access private
     */
    function _setupIgnore($ignore, $index)
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
     * @access private
     */
    function _getRegExpableSearchString($s)
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
     * @access private
     * @param string directory name of entry
     * @param string name
     */
    function _testFile($directory, $entry)
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
    function dirList($directory, $toplevel = null)
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
    
    function addDir($dir, $ignore = array(), $include = array(), $magicrequire = false)
    {
        $this->_setupIgnore($ignore, 1);
        $this->_setupIgnore($include, 0);
        $list = $this->dirList($dir);
        return $this->addArray($list, $magicrequire);
    }
    
    /**
     * Add an array of files to the archive
     *
     * @param array $files This is an associative array of the format 'file_to_archive' => 'save_path_in_archive'
     * @return boolean
     */
     
    function addArray($files, $magicrequire = false)
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
     * Save the PHAR Archive
     *
     * @param string $file_path The file path of where to save the file
     * @return void
     */
    
    function savePhar($file_path = null)
    {
        copy($this->temp_path, $file_path);
    }
    
}
?>