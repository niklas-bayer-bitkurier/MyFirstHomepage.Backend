<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../auth/token.php';

$existing_session = find_session($session_token, $pdo);

if (!isset($existing_session)) {
  no_authorization();
}

$stmt = $pdo->prepare('
  SELECT name, role FROM users
  WHERE id = :id
');
$stmt->execute(['id' => $existing_session['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user['role'] != 'r') {
  no_authorization();
}

switch ($method) {
    case 'GET':
	      if (isset($_GET['id'])) {
	          $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            
            if ($id === false || $id === null || $id < 1) {
		            http_response_code(400);
		            echo json_encode(['error' => "Invalid id: $id"]);
		            exit;
            }

            $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = :id');
	          $stmt->execute(['id' => $id]);
	          $user = $stmt->fetch(PDO::FETCH_ASSOC);

	          if (!$user) {
	              http_response_code(404);
		            echo json_encode(['error' => "Could not find user with id $id"]);
		            exit;
	          }
	          echo json_encode($user);
	      } else {
            // Retrieve all users
            $stmt = $pdo->query("SELECT id, name, email FROM users");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	      }
	      break;
    default:
	      http_response_code(405);
	      echo json_encode(['error' => "Could not find method: $method for endpoint: $path"], JSON_UNESCAPED_SLASHES);
	      break;
}

function no_authorization() {
  http_response_code(401);
  echo json_encode(['error' => 'No authorization to access this resource']);
  exit;
}
