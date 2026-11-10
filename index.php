<?php
require_once __DIR__.'/config/database.php';
require_once __DIR__.'/auth/token.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: null");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PATCH, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
  case 'OPTIONS':
    http_response_code(204);
    exit;
}

$session_token = $_COOKIE['__Host-session'];
$session = find_session($session_token, $pdo);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($path) {
  case '/':
    require_once __DIR__ .'/pages/index.php';
    break;
  case '/users':
    require_once __DIR__ .'/pages/users.php';
    break;
  case '/login':
    require_once __DIR__.'/api/login.php';
    break;
  case '/register':
    require_once __DIR__.'/api/register.php';
    break;
  case '/notes':
    require_once __DIR__ .'/api/notes.php';
    break;
  default:
    http_response_code(404);
    echo json_encode(['error' => "Could not find endpoint: $path"], JSON_UNESCAPED_SLASHES);
    break;
}
