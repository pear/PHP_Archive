<?php
/**
 * PHP_Archive Manager Class creator (allows debugging/manipulation of phar files)
 *
 * @package PHP_Archive
 * @category PHP
 */
/**
 * Needed for file manipulation
 */
require_once 'System.php';
/**
 *
 * @copyright Copyright © Gregory Beaver
 * @author Greg Beaver <cellog@php.net>
 * @version $Id$
 * @package PHP_Archive
 * @category PHP
 */
class PHP_Archive_Manager
{
    private $_archiveName;
    private $_compressed;
    private $_version = 'unknown';
    private $_manifest;
    private $_fileStart;
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
    public function __construct($phar, $compressed = false)
    {
        $this->_archiveName = $phar;
        $this->_compressed = $compressed;
        $fp = fopen($this->_archiveName, 'rb');
        $header = fread($fp, strlen('<?php #PHP_ARCHIVE_HEADER-'));
        if ($header == '<?php #PHP_ARCHIVE_HEADER-') {
            $version = '';
            while (!feof($fp) && $c = fgetc($fp)) {
                $version .= $c;
            }
            if (version_compare($version, '0.7', '<')) {
                require_once 'PHP/Archive/Exception.php';
                throw new PHP_Archive_Exception($phar . ' was created with obsolete PHP_Archive');
            }
            $this->_version = $version;
        }
        // seek to __HALT_COMPILER_OFFSET__
        $prev = '';
        while (!feof($fp) && false != ($next = fread($fp, 8092))) {
            $prev .= $next;
            if (false != ($t = strpos($prev, '__HALT_COMPILER();'))) {
                if ($t + strlen('__HALT_COMPILER();') - strlen($next)) {
                    // seek backwards to location
                    fseek($fp, $t + strlen('__HALT_COMPILER();') - strlen($next), SEEK_CUR);
                }
                break;
            }
            $prev = $next;
        }
        $manifest_length = unpack('Vlen', fread($fp, 4));
        $this->_manifest =
            $this->_unserializeManifest(fread($fp, $manifest_length['len']));
        $this->_fileStart = ftell($fp);
        fclose($fp);
    }

    /**
     * Display information on a phar
     *
     */
    public function dump()
    {
        
    }

    /**
     * Extract the .phar to a particular location
     *
     * @param string $toHere
     */
    public function unPhar($toHere)
    {
        
    }

    /**
     * Re-make the phar from a previously after having done work on an unPharred phar
     *
     * @param string $fromHere
     */
    public function rePhar($fromHere)
    {
        
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $manifest
     * @return unknown
     */
    private function _unserializeManifest($manifest)
    {
        // retrieve the number of files in the manifest
        $info = unpack('V', substr($manifest, 0, 4));
        $manifest = substr($manifest, 4);
        $ret = array();
        for ($i = 0; $i < $info[1]; $i++) {
            // length of the file name
            $len = unpack('V', substr($manifest, 0, 4));
            // file name
            $savepath = substr($manifest, 4, $len[1]);
            $manifest = substr($manifest, $len[1] + 4);
            // retrieve manifest data:
            // 0 = uncompressed file size
            // 1 = timestamp of when file was added to phar
            // 2 = offset of file within phar relative to internal file's start
            // 3 = compressed file size (actual size in the phar)
            $ret[$savepath] = array_values(unpack('Va/Vb/Vc/Vd', substr($manifest, 0, 16)));
            $manifest = substr($manifest, 16);
        }
        return $ret;
    }
}
?>