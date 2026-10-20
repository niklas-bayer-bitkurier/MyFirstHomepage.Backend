<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($path) {
    case '/users':
        require_once __DIR__.'/pages/users.php';
        break;
    default:
        http_response_code(404);
	echo json_encode(['error' => "Could not find endpoint: $path"], JSON_UNESCAPED_SLASHES);
	break;
}
