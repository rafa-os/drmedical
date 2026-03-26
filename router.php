<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $uri;

if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

$php_file = __DIR__ . $uri;
if (file_exists($php_file)) {
    require $php_file;
} else {
    http_response_code(404);
    echo '404 Not Found';
}
