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
 * @copyright Copyright ? Gregory Beaver
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
        $this->validate();
        $fp = fopen($this->_archiveName, 'rb');
        $header = fread($fp, strlen('<?php #PHP_ARCHIVE_HEADER-'));
        if ($header == '<?php #PHP_ARCHIVE_HEADER-') {
            $version = '';
            while (!feof($fp) && $c = fgetc($fp)) {
                $version .= $c;
            }
            if (version_compare($version, '0.7.1', '<')) {
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
     * validate a phar prior to manipulating it
     * @throws PHP_Archive_Exception
     */
    public function validate($strict = false)
    {
        require_once 'PHP/Archive/Exception.php';
        $errors = array();
        $warnings = array();
        $fp = fopen($this->_archiveName, 'rb');
        if (!$fp) {
            throw new PHP_Archive_ExceptionExtended(PHP_Archive_ExceptionExtended::NOOPEN,
                array('archive' => $this->_archiveName));
        }
        $header = fread($fp, strlen('<?php #PHP_ARCHIVE_HEADER-'));
        if ($header == '<?php #PHP_ARCHIVE_HEADER-') {
            $version = '';
            while (!feof($fp) && $c = fgetc($fp)) {
                $version .= $c;
            }
            if (version_compare($version, '0.7', '<')) {
                throw new PHP_Archive_Exception($phar . ' was created with obsolete PHP_Archive',
                    $errors);
            }
            $this->_version = $version;
        }
        // seek to __HALT_COMPILER_OFFSET__
        $prev = '';
        $found = false;
        while (!feof($fp) && false != ($next = fread($fp, 8092))) {
            $prev .= $next;
            if (false != ($t = strpos($prev, '__HALT_COMPILER();'))) {
                if ($t + strlen('__HALT_COMPILER();') - strlen($next)) {
                    // seek backwards to location
                    fseek($fp, $t + strlen('__HALT_COMPILER();') - strlen($next), SEEK_CUR);
                }
                $found = true;
                break;
            }
            $prev = $next;
        }
        if (!$found) {
            throw new PHP_Archive_ExceptionExtended(PHP_Archive_ExceptionExtended::NOTPHAR,
                array('archive' => $this->_archiveName));
        }
        $manifest_length = unpack('Vlen', fread($fp, 4));
        $manifest_length = $manifest_length['len'];
        if ($manifest_length > 1048576) {
            if ($strict) {
                throw new PHP_Archive_ExceptionExtended(
                    PHP_Archive_ExceptionExtended::MANIFESTOVERFLOW, array(
                    'archive' => $this->_archiveName));
            }
            $errors[] = new PHP_Archive_ExceptionExtended(
                PHP_Archive_ExceptionExtended::MANIFESTOVERFLOW, array(
                'archive' => $this->_archiveName));
        }
        $manifest = fread($fp, $manifest_length);
        // retrieve the number of files in the manifest
        $info = unpack('V', substr($manifest, 0, 4));
        if ($info[1] > $manifest_length * 16) {
            $errors[] = new PHP_Archive_ExceptionExtended(
                PHP_Archive_ExceptionExtended::MANIFESTENTRIESOVERFLOW,array(
                'archive' => $this->_archiveName));
            throw new PHP_Archive_Exception('invalid phar "' . $this->_archiveName . '"', $errors);
        }
        $manifest = substr($manifest, 4);
        if (strlen($manifest) < 4) {
            $errors[] = new PHP_Archive_ExceptionExtended(
                PHP_Archive_ExceptionExtended::MANIFESTENTRIESUNDERFLOW, array(
                'archive' => $this->_archiveName));
            throw new PHP_Archive_Exception('invalid phar "' . $this->_archiveName . '"', $errors);
        }
        $ret = array();
        for ($i = 0; $i < $info[1]; $i++) {
            if (strlen($manifest) < 4) {
                if (isset($savepath)) {
                    $errors[] = new PHP_Archive_ExceptionExtended(
                        PHP_Archive_ExceptionExtended::MANIFESTENTRIESTRUNCATEDENTRY, array(
                        'archive' => $this->_archiveName, 'last' => $savepath,
                        'current' => '*unknown*', 'size' => $info[1], 'cur' => $i));
                    throw new PHP_Archive_Exception('invalid phar "' . $this->_archiveName . '"', $errors);
                } else {
                    $errors[] = new PHP_Archive_ExceptionExtended(
                        PHP_Archive_ExceptionExtended::MANIFESTENTRIESTRUNCATEDENTRY, array(
                        'archive' => $this->_archiveName, 'last' => '*none*',
                        'current' => '*unknown*', 'size' => $info[1], 'cur' => $i));
                    throw new PHP_Archive_Exception('invalid phar "' . $this->_archiveName . '"', $errors);
                }
            }
            // length of the file name
            $len = unpack('V', substr($manifest, 0, 4));
            if (strlen($manifest) < $len[1] + 4) {
                if (isset($savepath)) {
                    $errors[] = new PHP_Archive_ExceptionExtended(
                        PHP_Archive_ExceptionExtended::MANIFESTENTRIESTRUNCATEDENTRY, array(
                        'archive' => $this->_archiveName, 'last' => $savepath,
                        'current' => '*unknown*', 'size' => $info[1], 'cur' => $i));
                    throw new PHP_Archive_Exception('invalid phar "' . $this->_archiveName . '"', $errors);
                } else {
                    $errors[] = new PHP_Archive_ExceptionExtended(
                        PHP_Archive_ExceptionExtended::MANIFESTENTRIESTRUNCATEDENTRY, array(
                        'archive' => $this->_archiveName, 'last' => '*none*',
                        'current' => '*unknown*', 'size' => $info[1], 'cur' => $i));
                    throw new PHP_Archive_Exception('invalid phar "' . $this->_archiveName . '"', $errors);
                }
            }
            // file name
            if (!isset($savepath)) {
                $last = '*none*';
            } else {
                $last = $savepath;
            }
            $savepath = substr($manifest, 4, $len[1]);
            $manifest = substr($manifest, $len[1] + 4);
            if (strlen($manifest) < 16) {
                if (isset($savepath)) {
                    $errors[] = new PHP_Archive_ExceptionExtended(
                        PHP_Archive_ExceptionExtended::MANIFESTENTRIESTRUNCATEDENTRY, array(
                        'archive' => $this->_archiveName, 'last' => $last,
                        'current' => $savepath, 'size' => $info[1], 'cur' => $i));
                    throw new PHP_Archive_Exception('invalid phar "' . $this->_archiveName . '"', $errors);
                } else {
                    $errors[] = new PHP_Archive_ExceptionExtended(
                        PHP_Archive_ExceptionExtended::MANIFESTENTRIESTRUNCATEDENTRY, array(
                        'archive' => $this->_archiveName, 'last' => $last,
                        'current' => $savepath, 'size' => $info[1], 'cur' => $i));
                    throw new PHP_Archive_Exception('invalid phar "' . $this->_archiveName . '"', $errors);
                }
            }
            // retrieve manifest data:
            // 0 = uncompressed file size
            // 1 = timestamp of when file was added to phar
            // 2 = offset of file within phar relative to internal file's start
            // 3 = compressed file size (actual size in the phar)
            $ret[$savepath] = array_values(unpack('Va/Vb/Vc/Vd', substr($manifest, 0, 16)));
            $manifest = substr($manifest, 16);
        }
        $this->_manifest =  $ret;
        $this->_fileStart = ftell($fp);
        foreach ($this->_manifest as $path => $info) {
            $currentFilename = $path;
            // actual file length in file includes 8-byte header
            $internalFileLength = $info[3] - 8;
            // seek to offset of file header within the .phar
            if (!@fseek($this->fp, $this->_fileStart + $info[2])) {
                $errors[] = new PHP_Archive_ExceptionExtended(
                    PHP_Archive_ExceptionExtended::FILELOCATIONINVALID,
                    array('file' => $path, 'loc' => $this->_fileStart + $info[2],
                    'size' => filesize($this->_archiveName)));
                continue;
            }
            $tdata = @fread($this->fp, 8);
            if (strlen($tdata) < 8) {
                $errors[] = new PHP_Archive_ExceptionExtended(
                    PHP_Archive_ExceptionExtended::FILETRUNCATED,
                    array('file' => $path, 'loc' => $this->_fileStart + $info[2]));
                continue;
            }
            $temp = @unpack("Vcrc32/Visize", $tdata);
            if (!$temp) {
                $errors[] = new PHP_Archive_ExceptionExtended(
                    PHP_Archive_ExceptionExtended::FILECORRUPTEDCRCSIZE,
                    array('file' => $path, 'loc' => $this->_fileStart + $info[2]));
                continue;
            }
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
                $data = @gzinflate($data);
                if ($data === false) {
                    $errors[] = new PHP_Archive_ExceptionExtended(
                        PHP_Archive_ExceptionExtended::FILECORRUPTEDGZ,
                        array('file' => $path, 'loc' => $this->_fileStart + $info[2]));
                }
            }
            if ($temp['isize'] != $info[0]) {
                $errors[] = new PHP_Archive_ExceptionExtended(
                    PHP_Archive_ExceptionExtended::FILECORRUPTEDSIZE,
                    array('file' => $path, 'expected' => $temp['isize'],
                        'actual' => $info[0]));
            }
            if ($temp['crc32'] != crc32($data)) {
                $errors[] = new PHP_Archive_ExceptionExtended(
                    PHP_Archive_ExceptionExtended::FILECORRUPTEDCRC,
                    array('file' => $path, 'expected' => $temp['crc32'],
                        'actual' => crc32($data)));
            }
        }
        if (count($errors)) {
            throw new PHP_Archive_Exception('invalid phar "' . $this->_archiveName . '"', $errors);
        }
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