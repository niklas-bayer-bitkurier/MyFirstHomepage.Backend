<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../helpers/array_helper.php';
require_once __DIR__.'/../auth/token.php';
require_once __DIR__.'/../auth/user_auth.php';

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
    echo json_encode(['loggedin' => $is_logged_in, 'username' => $username]);
    break;
  case 'POST':
    $data = json_decode(file_get_contents("php://input"), true);
    $username = $data['name'];
    $password = $data['password'];

    if (!(isset($username) && isset($password))) {
      http_response_code(400);
      echo json_encode(['error' => "Username and password must be specified."]);
      exit;
    }

    /*if (strlen($password) < 8) {
      http_response_code(400);
      echo json_encode(['error' => "Password too short"]);
      exit;
    }*/

    $user_id = get_id_if_matches($username, $password, $pdo);

    if ($user_id === -1) {
      http_response_code(401);
      echo json_encode(['error' => "Please check your username and password"]);
      exit;
    }

    $existing_session = find_session($session_token, $pdo);
    $retval = [];
    
    if (isset($existing_session)) {
      if ($existing_session['is_valid']) {
        if ($existing_session['user_id'] == $user_id) {
          echo json_encode([
            'session_found' => "User $username is already logged in ..."
          ]);
          exit;
        } else {
          $str = $existing_session['user_id'];
          $retval['session_found'] = "Found another session for user: $str";
        }
      } else {
        $str = $existing_session['user_id'];
        $retval['session_found'] = "Outdated session found for user: $str";
      }
    } else {
      $retval['session_found'] = "No session found";
    }
    $retval['token'] = create_token($user_id, $pdo);
    echo json_encode($retval);
    break;
  default:
    http_response_code(405);
    echo json_encode(['error' => "Unhadled method $method on endpoint $path"]);
    break;
}
