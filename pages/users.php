<?php
require_once __DIR__.'/../config/database.php';

switch ($method) {
    case 'GET':
        // Retrieve all users
        $stmt = $pdo->query("SELECT id, name, email FROM users");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	break;
    default:
	http_response_code(405);
	echo json_encode(['error' => "Could not find method: $method for endpoint: $path"], JSON_UNESCAPED_SLASHES);
	break;
}
