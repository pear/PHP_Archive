<?php
if ($_SERVER['PATH_INFO'] == '/images.php' || $_SERVER['PATH_INFO'] == '/image.php') {
    $file_info = pathinfo($_GET['file']);
    switch (strtolower($file_info['extension'])) {
        case 'jpg':
        case 'jpeg':
            $mimetype = 'image/jpeg';
            break;
        case 'gif':
            $mimetype = 'image/gif';
            break;
        case 'png':
            $mimetype = 'image/png';
            break;
        default:
            exit;
    }
    
    header('Content-Type: ' .$mimetype);
    include 'phar://images/' . $_GET['file'];
    exit;
}


if (isset($_SERVER['PATH_INFO'])) {
    require_once 'phar:/' .$_SERVER['PATH_INFO'];
} else {
    require_once 'phar://index.htm';
}

?>