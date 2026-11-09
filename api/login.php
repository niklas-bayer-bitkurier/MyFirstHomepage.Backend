<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../helpers/array_helper.php';
require_once __DIR__.'/../auth/token.php';
require_once __DIR__.'/../auth/user_auth.php';

$retval = [];

switch ($method) {
  case 'GET':
    $existing_session = find_session($session_token, $pdo);
    $is_logged_in = isset($existing_session) && $existing_session['is_valid'];
    $username = null;
    if ($is_logged_in) {
      $stmt = $pdo->prepare("
        SELECT name FROM users
        WHERE id = :id
      ");
      $stmt->execute(['id' => $existing_session['user_id']]);
      $username = $stmt->fetch(PDO::FETCH_ASSOC)['name'];
    }
    $retval['logged_in'] = $is_logged_in;
    $retval['username'] = $username;
    echo json_encode($retval);
    break;
  case 'POST':
    $data = json_decode(file_get_contents("php://input"), true);
    $username = $data['name'];
    $password = $data['password'];

    if (!(isset($username) && isset($password))) {
      http_response_code(400);
      $retval['status'] = 'Error: Username and password must be specfied';
      echo json_encode($retval);
      exit;
    }

    $user_id = get_id_if_matches($username, $password, $pdo);

    if ($user_id === -1) {
      http_response_code(401);
      $retval['status'] = "Error: Password mismatch"
      echo json_encode($retval);
      exit;
    }

    $existing_session = find_session($session_token, $pdo);
    
    if (isset($existing_session)) {
      if ($existing_session['is_valid']) {
        if ($existing_session['user_id'] == $user_id) {
          $retval['status'] = 'Session found';
          echo json_encode($retval);
          exit;
        } else {
          $retval['status'] = 'Session found for another user, creating new session';
        }
      } else {
        $retval['status'] = 'Outdated session, creating new session';
      }
    } else {
      $retval['status'] = 'No session found, creating new session';
    }

    create_token($user_id, $pdo);
    echo json_encode($retval);
    break;
  default:
    http_response_code(405);
    echo json_encode(['error' => "Unhadled method $method on endpoint $path"]);
    break;
}
