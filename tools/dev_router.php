<?php
$requestPath = rawurldecode((string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));
if (preg_match('~(?:^|/)\.(?:env(?:\..*)?|git)(?:/|$)~i', $requestPath)) {
    http_response_code(404);
    exit;
}

$requestedFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . ltrim($requestPath, '/\\');
if ($requestPath !== '/' && is_file($requestedFile)) return false;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'index.php';
?>
