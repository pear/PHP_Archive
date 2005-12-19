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
    private static $_messages = array(
        'en' => array(
            self::NOOPEN => 'Cannot open "%archive%"',
            self::NOTPHAR => '"%archive%" is not a PHP_Archive-based phar',
            self::NOHALTCOMPILER => '"%archive%" is not a phar, has no __HALT_COMPILER();',
            self::MANIFESTOVERFLOW => '"%archive%" has a manifest larger than 1 MB, too large',
            self::MANIFESTENTRIESOVERFLOW => '"%archive%" has too many manifest entries for the manifest size',
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
            if (!is_string($value)) {
                die('Fatal Error: only strings can be used in errorData for PHP_Archive_ExceptionExtended');
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