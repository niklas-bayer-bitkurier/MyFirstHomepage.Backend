<?php

require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../auth/token.php';

switch ($method) {
  case 'GET':
    $existing_session = find_session(hash('sha256', $session_token), $pdo);

    if (!isset($existing_session)) {
      http_response_code(401);
      echo json_encode(['error' => 'Not authrized to retrieve users']);
      exit;
    }

    if (!$existing_session['is_valid']) {
      http_response_code(400);
      echo json_encode(['error' => 'Outdated session']);
    }

    $stmt = $pdo->prepare('
      SELECT id, name, role FROM users
      WHERE id = :id
    ');
    $stmt->execute(['id' => $existing_session['user_id']]);
    $requester = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!isset($_GET['user'])) {
      if ($requester['role'] != 'r') {
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
    
    $user = filter_input(INPUT_GET, 'user', FILTER_VALIDATE_INT);

    if ($requester['id'] != $user && $requester['role'] != 'r') {
      http_response_code(401);
      echo json_encode([
        'error' => 'Not authorized not retrieve note for anyone else except you'
      ]);
      exit;
    }

    $stmt = $pdo->prepare("
      SELECT notes.id AS 'noteId', title, content, users.name FROM notes
      JOIN users ON notes.ownerId = users.id
      WHERE ownerId = :user
    ");
    $stmt->execute(['user' => $user]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($notes);
    break;
  default:
    http_response_code(405);
    echo json_encode(['error' => "Unhandled method $method at endpoint $path"]);
    break;
}
