<?php
/**
 * PHP_Archive exception
 *
 * @package PHP_Archive
 * @category PHP
 */
/**
 * base class for all PEAR exceptions
 */
require_once 'PEAR/Exception.php';
/**
 * PHP_Archive exception
 *
 * @package PHP_Archive
 * @category PHP
 */
class PHP_Archive_Exception extends PEAR_Exception {}
class PHP_Archive_ExceptionExtended extends PHP_Archive_Exception
{
    const NOOPEN = 1;
    const NOTPHAR = 2;
    const NOHALTCOMPILER = 3;
    const MANIFESTOVERFLOW = 4;
    const MANIFESTENTRIESOVERFLOW = 5;
    const MANIFESTENTRIESUNDERFLOW = 6;
    const MANIFESTENTRIESTRUNCATEDENTRY = 7;
    const FILELOCATIONINVALID = 8;
    const FILETRUNCATED = 9;
    const UNKNOWNAPI = 10;
    const FILECORRUPTEDGZ = 11;
    const FILECORRUPTEDSIZE = 12;
    const FILECORRUPTEDCRC = 13;
    const NOSIGNATUREMAGIC = 14;
    const BADSIGNATURE = 15;
    private static $_messages = array(
        'en' => array(
            self::NOOPEN => 'Cannot open "%archive%"',
            self::NOTPHAR => '"%archive%" is not a PHP_Archive-based phar',
            self::NOHALTCOMPILER => '"%archive%" is not a phar, has no __HALT_COMPILER();',
            self::MANIFESTOVERFLOW => '"%archive%" has a manifest larger than 1 MB, too large',
            self::MANIFESTENTRIESOVERFLOW => '"%archive%" has too many manifest entries for the manifest size',
            self::MANIFESTENTRIESUNDERFLOW => '"%archive%" has a truncated manifest',
            self::MANIFESTENTRIESTRUNCATEDENTRY => '"%archive%" has a truncated manifest entry after last known entry "%last%" (%cur% of %size% entries) in entry "%current%"',
            self::FILELOCATIONINVALID => '"%archive%" manifest entry "%file%" has a starting location that cannot be located "%loc%" in a file of size "%size%"',
            self::FILETRUNCATED => '"%archive%" file "%file%" is truncated.  File begins at "%loc%"',
            self::FILECORRUPTEDGZ => '"%archive%" file "%file%" has corrupted gzipped content.  File begins at "%loc%"',
            self::FILECORRUPTEDSIZE => '"%archive%" file "%file%" is %actual% bytes, but size indicator at file start says it should be %expected% bytes',
            self::FILECORRUPTEDCRC => '"%archive%" file "%file%" has a crc32 of "%actual%" but was expecting "%expected%"',
            self::UNKNOWNAPI => '"%archive%" has unknown API version "%ver%"',
            self::NOSIGNATUREMAGIC => '%archive% has a signature, but does not have the magic "GBMB" flags',
            self::UNKNOWNSIGTYPE => '%archive% has unknown signature type "%type%"',
            self::BADSIGNATURE => '%archive% is corrupted: signature does not match',
        )
    );
    private static $_lang = 'en';
    private $_errorData = array();
    public function __construct($code, $errorData = array())
    {
        $this->_errorData = $errorData;
        if (isset(self::$_messages[self::$_lang][$code])) {
            $message = self::$_messages[self::$_lang][$code];
        } else {
            $message = "ERROR UNKNOWN PHP_Archive_Exception CODE: '$code'";
        }
        foreach ($errorData as $var => $value) {
            if (!is_string($value) && !is_int($value)) {
                die('Fatal Error: only strings/ints can be used in errorData for PHP_Archive_ExceptionExtended');
            }
            $message = str_replace('%' . $var . '%', $value, $message);
        }
        parent::__construct($message);
    }

    public static function setLang($lang)
    {
        if (!isset(self::$_messages[$lang])) {
            throw new PHP_Archive_Exception('Error, unknown language "' . $lang . '", ' .
                'must be one of ' . implode(', ', array_keys(self::$_messages)));
        }
        self::$_lang = $lang;
    }

    public function getErrorData()
    {
        return $this->_errorData;
    }
}
?>