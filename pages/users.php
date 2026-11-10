<?php
require_once __DIR__.'/../config/database.php';

$retval = [];

if (!isset($session)) {
  no_authorization();
}

$user = $session['user'];

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
	    $requestee = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$requestee) {
        http_response_code(404);
        $retval['status'] = 'Error: No user with id: '.$id;
        echo json_encode($retval);
		    exit;
      }

      $retval['user'] = $requestee;
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
