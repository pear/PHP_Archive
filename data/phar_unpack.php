
require_once 'PHP/Archive.php';
stream_register_wrapper('phar', 'PHP_Archive');
require_once 'phar://phar_default.php';
exit;
?>