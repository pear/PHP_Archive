if (isset($_SERVER) && isset($_SERVER['REQUEST_URI'])) {
    $uri = parse_url($_SERVER['REQUEST_URI']);
    $archive = realpath($_SERVER['SCRIPT_FILENAME']);
    $subpath = str_replace('/' . basename($archive), '', $uri['path']);
    $mimetypes = @mime@;
    $phpfiles = @php@;
    $phpsfiles = @phps@;
    $deny = @deny@;
    $subpath = str_replace('/' . basename($archive), '', $uri['path']);
    if (!$subpath || $subpath == '/') {
        $subpath = '/@initfile@';
    }
    if ($subpath[0] != '/') {
        $subpath = '/' . $subpath;
    }
    if (!@file_exists('phar://' . $archive . $subpath)) {
        header("HTTP/1.0 404 Not Found");
        exit;
    }

    foreach ($deny as $pattern) {
        if (preg_match($pattern, $subpath)) {
            header("HTTP/1.0 404 Not Found");
            exit;
        }
    }
    $inf = pathinfo(basename($subpath));
    if (!isset($inf['extension'])) {
        header('Content-Type: text/plain');
        header('Content-Length: ' . filesize('phar://' . $archive . $subpath));
        readfile('phar://' . $archive . $subpath);
        exit;
    }
    if (isset($phpfiles[$inf['extension']])) {
        include 'phar://' . $archive . '/' . $subpath;
        exit;
    }
    if (isset($mimetypes[$inf['extension']])) {
        header('Content-Type: ' . $mimetypes[$inf['extension']]);
        header('Content-Length: ' . filesize('phar://' . $archive . $subpath));
        readfile('phar://' . $archive . $subpath);
        exit;
    }
    if (isset($phpsfiles[$inf['extension']])) {
        header('Content-Type: text/html');
        $c = highlight_file('phar://' . $archive . $subpath, true);
        header('Content-Length: ' . strlen($c));
        echo $c;
        exit;
    }
    header('Content-Type: text/plain');
    header('Content-Length: ' . filesize('phar://' . $archive . '/' . $subpath));
    readfile('phar://' . $archive . '/' . $subpath);
    exit;
}