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


define('PHP_ARCHIVE_MANAGER_COMPRESSED_GZ', 0x1);

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
    private $_alias;
    private $_archiveName;
    private $_apiVersion;
    private $_compressed;
    private $_knownAPIVersions = array('0.8.0');
    private $_manifest;
    private $_fileStart;
    private $_manifestSize;
    private $_html;
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
    public function __construct($phar)
    {
        $this->_archiveName = $phar;
        $this->validate();
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
            while (!feof($fp) && (false !== $c = fgetc($fp))) {
                if ((ord($c) < ord('0') || ord($c) > ord('9')) && $c != '.') {
                    break;
                }
                $version .= $c;
            }
            if (version_compare($version, '0.8.0', '<')) {
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
                fseek($fp, $t - strlen($prev) + strlen('__HALT_COMPILER();'), SEEK_CUR);
                $found = true;
                break;
            }
            $prev = $next;
        }
        if (!$found) {
            throw new PHP_Archive_ExceptionExtended(PHP_Archive_ExceptionExtended::NOTPHAR,
                array('archive' => $this->_archiveName));
        }
        $manifest_length = fread($fp, 4);
        $manifest_length = unpack('Vlen', $manifest_length);
        $this->_manifestSize = $manifest_length = $manifest_length['len'];
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
        if ($info[1] * 17 > $manifest_length) {
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
        // get API version and compressed flag
        $apiver = substr($manifest, 0, 2);
        $apiver = bin2hex($apiver);
        $this->_apiVersion = hexdec($apiver[0]) . '.' . hexdec($apiver[1]) .
            '.' . hexdec($apiver[2]);
        if (!in_array($this->_apiVersion, $this->_knownAPIVersions)) {
            $errors[] = new PHP_Archive_ExceptionExtended(
                PHP_Archive_ExceptionExtended::UNKNOWNAPI, array(
                'archive' => $this->_archiveName, 'ver' => $this->_apiVersion));
            throw new PHP_Archive_Exception('phar "' . $this->_archiveName . '" cannot be analyzed', $errors);
        }
        $manifest = substr($manifest, 2);
        if (strlen($manifest) < 4) {
            $errors[] = new PHP_Archive_ExceptionExtended(
                PHP_Archive_ExceptionExtended::MANIFESTENTRIESUNDERFLOW, array(
                'archive' => $this->_archiveName));
            throw new PHP_Archive_Exception('invalid phar "' . $this->_archiveName . '"', $errors);
        }
        // get alias
        $aliaslen = unpack('V', substr($manifest, 0, 4));
        $aliaslen = $aliaslen[1];
        $manifest = substr($manifest, 4);
        if (strlen($manifest) < $aliaslen) {
            $errors[] = new PHP_Archive_ExceptionExtended(
                PHP_Archive_ExceptionExtended::MANIFESTENTRIESUNDERFLOW, array(
                'archive' => $this->_archiveName));
            throw new PHP_Archive_Exception('invalid phar "' . $this->_archiveName . '"', $errors);
        }
        $this->_alias = substr($manifest, 0, $aliaslen);
        $manifest = substr($manifest, $aliaslen);
        $ret = array();
        $offset = 0;
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
            if (strlen($manifest) < 17) {
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
            // 1 = save timestamp
            // 2 = compressed file size
            // 3 = crc32
            // 4 = flags
            $ret[$savepath] = array_values(unpack('Va/Vb/Vc/Vd/Ce', substr($manifest, 0, 17)));
            $ret[$savepath][5] = $offset;
            $this->_compressed = $ret[$savepath][4] & PHP_ARCHIVE_MANAGER_COMPRESSED_GZ;
            $offset += $ret[$savepath][2];
            $manifest = substr($manifest, 17);
        }
        $this->_manifest =  $ret;
        $this->_fileStart = ftell($fp);
        foreach ($this->_manifest as $path => $info) {
            $currentFilename = $path;
            $internalFileLength = $info[2];
            // seek to offset of file header within the .phar
            if (fseek($fp, $this->_fileStart + $info[5])) {
                $errors[] = new PHP_Archive_ExceptionExtended(
                    PHP_Archive_ExceptionExtended::FILELOCATIONINVALID,
                    array('archive' => $this->_archiveName, 'file' => $path, 'loc' => $this->_fileStart + $info[5],
                    'size' => filesize($this->_archiveName)));
                continue;
            }
            $temp = array('crc32' => $info[3], 'isize' => $info[0]);
            $data = '';
            $count = $internalFileLength;
            while ($count) {
                if ($count < 8192) {
                    $data .= @fread($fp, $count);
                    $count = 0;
                } else {
                    $count -= 8192;
                    $data .= @fread($fp, 8192);
                }
            }
            if ($info[4] & PHP_ARCHIVE_MANAGER_COMPRESSED_GZ) {
                $data = @gzinflate($data);
                if ($data === false) {
                    $errors[] = new PHP_Archive_ExceptionExtended(
                        PHP_Archive_ExceptionExtended::FILECORRUPTEDGZ,
                        array('archive' => $this->_archiveName, 'file' => $path, 'loc' => $this->_fileStart + $info[2]));
                }
            }
            if ($temp['isize'] != strlen($data)) {
                $errors[] = new PHP_Archive_ExceptionExtended(
                    PHP_Archive_ExceptionExtended::FILECORRUPTEDSIZE,
                    array('archive' => $this->_archiveName, 'file' => $path, 'expected' => $temp['isize'],
                        'actual' => strlen($data)));
            }
            if ($temp['crc32'] != crc32($data)) {
                $errors[] = new PHP_Archive_ExceptionExtended(
                    PHP_Archive_ExceptionExtended::FILECORRUPTEDCRC,
                    array('archive' => $this->_archiveName, 'file' => $path, 'expected' => $temp['crc32'],
                        'actual' => crc32($data)));
            }
        }
        @fclose($fp);
        if (count($errors)) {
            throw new PHP_Archive_Exception('invalid phar "' . $this->_archiveName . '"', $errors);
        }
    }

    /**
     * Display information on a phar
     *
     * @param bool
     */
    public function dump($return_array = false)
    {
        if (!$return_array) {
            echo $this;
            return;
        }
        $filesize = filesize($this->_archiveName);
        $ret = array(
            'Phar name' => $this->_archiveName,
            'Size' => $filesize,
            'API version' => $this->_apiVersion,
            'Manifest size (bytes)' => $this->_manifestSize,
            'Manifest entries' => count($this->_manifest),
            'Alias' => $this->_alias,
            'Global compressed flag' => $this->_compressed,
        );
        // 0 = uncompressed file size
        // 1 = save timestamp
        // 2 = compressed file size
        // 3 = crc32
        // 4 = flags
        $offset = 0;
        foreach ($this->_manifest as $file => $info) {
            $ret['File phar://' . $this->_alias . '/' . $file . ' size'] = $info[0];
            $ret['File phar://' . $this->_alias . '/' . $file . ' save date'] =
                date('Y-m-d H:i', $info[1]);
            $ret['File phar://' . $this->_alias . '/' . $file . ' crc'] = $info[3];
            $ret['File phar://' . $this->_alias . '/' . $file . ' size in archive'] = $info[2];
            $ret['File phar://' . $this->_alias . '/' . $file . ' offset in archive'] = $offset;
            $ret['File phar://' . $this->_alias . '/' . $file . ' GZ compressed'] =
                $info[4] & PHP_ARCHIVE_MANAGER_COMPRESSED_GZ ? 'yes' : 'no';
            $offset += $info[2];
        }
        return $ret;
    }

    public function __toString()
    {
        $ret = $this->dump(true);
        if ($this->_html) {
            array_walk($ret, create_function('&$a, $b', '$a = "<strong>$b:</strong> $a";'));
            $ret = implode("<br />\n", $ret);
        } else {
            array_walk($ret, create_function('&$a, $b', '$a = "$b: $a";'));
            $ret = implode("\n", $ret);
        }
        return $ret;
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
     * For display of data in a browser
     *
     * @return PHP_Archive_Manager
     */
    public function inHtml()
    {
        $this->_html = true;
        $a = clone $this;
        $this->_html = false;
        return $a;
    }
}
?>