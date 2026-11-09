<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../auth/token.php';

$existing_session = find_session($session_token, $pdo);
$retval = [];

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
        $retval['status'] = 'Error: Invalid user id: '.$id;
        echo json_encode($retval);
		    exit;
      }

      $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = :id');
	    $stmt->execute(['id' => $id]);
	    $user = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$user) {
        http_response_code(404);
        $retval['status'] = 'Error: No user with id: '.$id;
        echo json_encode($retval);
		    exit;
      }

      $retval['user'] = $user;
      $retval['status'] = 'Success';
	    echo json_encode($retval);
	  } else {
      // Retrieve all users
      $stmt = $pdo->query("SELECT id, name, email FROM users");
      $retval['users'] = $stmt->fetch(PDO::FETCH_ASSOC);
      $retval['status'] = 'Success';
      echo json_encode($retval);
	  }
	  break;
  default:
    http_response_code(405);
    $retval['status'] = "Error: Unhandled method $method on endpoint $path";
	  echo json_encode($retval);
	  break;
}

function no_authorization() {
  http_response_code(401);
  $retval['status'] = "No authorization to access endpoint $path";
  echo json_encode($retval);
  exit;
}
