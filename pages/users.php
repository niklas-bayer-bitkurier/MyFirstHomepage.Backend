<?php
require_once __DIR__.'/../config/database.php';

switch ($method) {
    default:
        http_response_code(405);
	echo json_encode(['error' => "Could not find method: $method for endpoint: $path"], JSON_UNESCAPED_SLASHES);
        break;
}
