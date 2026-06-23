<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$base = '/decode-api';   // <-- CHANGE if your folder name is different

if (strpos($path, $base) === 0) {
    $path = substr($path, strlen($base));
}
$path = trim($path, '/');

// Default to health
if ($path === '') $path = 'health';

// Route
if ($path === 'health' || $path === 'api/health') {
    require_once 'api/health.php';
    exit();
}

if ($path === 'items' || $path === 'api/items') {
    require_once 'api/items.php';
    exit();
}

if (preg_match('/^(?:api\/)?items\/(\d+)$/', $path, $matches)) {
    $_GET['id'] = $matches[1];
    require_once 'api/items.php';
    exit();
}

http_response_code(404);
echo json_encode(['success' => false, 'error' => 'Not Found', 'message' => 'Endpoint not found']);
?>