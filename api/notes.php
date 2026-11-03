<?php

require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../auth/token.php';

$existing_session = find_session($session_token, $pdo);

if (!($existing_session)) {
  http_response_code(401);
  echo json_encode(['error' => 'Not authorized to retrieve users']);
  exit;
}

if (!$existing_session['is_valid']) {
  http_response_code(400);
  echo json_encode(['error' => 'Outdated session']);
  exit;
}

switch ($method) {
  case 'GET':
    $stmt = $pdo->prepare('
      SELECT id, name, role FROM users
      WHERE id = :id
    ');
    $stmt->execute(['id' => $existing_session['user_id']]);
    $requester = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!isset($_GET['user'])) {
      $stmt = prepare_select_notes($pdo);
      $stmt->execute(['user' => $existing_session['user_id']]);
      $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
      echo json_encode($notes);
      exit;
    }
    
    $user = filter_input(INPUT_GET, 'user', FILTER_VALIDATE_INT);

    if ($user == '-1') {
      if ($requester['role'] !='r') {
        http_response_code(401);
        echo json_encode(['error' => "Not authorized to retrieve all notes"]);
        exit;
      }
      $stmt = $pdo->prepare('
        SELECT * FROM notes;
      ');
      $stmt->execute();
      $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
      echo json_encode($notes);
      exit;
    }

    if ($requester['id'] != $user && $requester['role'] != 'r') {
      http_response_code(401);
      echo json_encode([
        'error' => 'Not authorized not retrieve note for anyone else except you'
      ]);
      exit;
    }

    $stmt = prepare_select_notes($pdo);
    $stmt->execute(['user' => $user]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($notes);
    break;
  default:
    http_response_code(405);
    echo json_encode(['error' => "Unhandled method $method at endpoint $path"]);
    break;
}

function prepare_select_notes($pdo) {
  return $pdo->prepare('
    SELECT notes.id AS "id", title, content, users.name AS "owner", creationDate, lastUpdated FROM notes
    JOIN users ON notes.ownerId = users.id
    WHERE ownerId = :user
  ');
}
