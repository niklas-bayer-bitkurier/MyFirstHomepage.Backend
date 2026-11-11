<?php

require_once __DIR__.'/../config/database.php';

$retval = [];

if (!isset($session) || $session === null) {
  http_response_code(401);
  $retval['status'] = "Error: Not authorized to access endpoint $path";
  echo json_encode($retval);
  exit;
}

$user = $session['user'];

switch ($method) {
  case 'GET':
    if (!isset($_GET['user'])) {
      $stmt = prepare_select_notes($pdo);
      $stmt->execute(['user' => $user['id']]);
      $retval['notes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $retval['status'] = 'Success';
      echo json_encode($retval);
      exit;
    }
    
    $requestee_id = filter_input(INPUT_GET, 'user', FILTER_VALIDATE_INT);

    if ($requestee_id === -1) {
      if ($user['role'] !=='r') {
        http_response_code(401);
        $retval['status'] = 'Not authorized to retrieve all notes';
        echo json_encode($retval);
        exit;
      }
      $stmt = $pdo->prepare('
        SELECT * FROM notes
        ORDER BY last_updated;
      ');
      $stmt->execute();
      $retval['notes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $retval['status'] = 'Success';
      echo json_encode($retval);
      exit;
    }

    if ($user['id'] !== $requestee_id && $user['role'] !== 'r') {
      http_response_code(401);
      $retval['status'] = 'Error: Not authorized to retrieve foreign notes';
      echo json_encode($retval);
      exit;
    }

    $stmt = prepare_select_notes($pdo);
    $stmt->execute(['user' => $requestee_id]);
    $retval['notes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $retval['status'] = 'Success';
    echo json_encode($retval);
    break;
  case 'POST':
    $current_time = time();
    $data = json_decode(file_get_contents("php://input"), true);
    $creation_id = $data['creation_id'];
    $title = $data['title'];
    $content = $data['content'];

    if (!(isset($creation_id) && isset($title) && isset($content))) {
      http_response_code(400);
      $retval['status'] = 'Error: CreationID, title and content must be specified';
      echo json_encode($retval);
      exit;
    }

    $stmt = $pdo->prepare('
      INSERT INTO notes (title, content, owner_id, created_at, last_updated)
      VALUES (:title, :content, :owner_id, :created_at, :last_updated)
    ');
    $stmt->execute(['title' => $title, 'content' => $content, 'owner_id' => $user['id'], 'created_at' => $current_time, 'last_updated' => $current_time]);
    $id = (int) $pdo->lastInsertId();
    $stmt = $pdo->prepare('
      SELECT * from notes
      WHERE id = :id
    ');
    $stmt->execute(['id' => $id]);
    $note = $stmt->fetch(PDO::FETCH_ASSOC);
    $note['creation_id'] = $creation_id;
    $retval['note'] = $note;
    $retval['status'] = 'Success';
    echo json_encode($retval);
    break;
  case 'PATCH':
    $current_time = time();
    $data = json_decode(file_get_contents("php://input"), true);
    $id = $data['id'];
    $title = $data['title'];
    $content = $data['content'];

    if (!(isset($id) && isset($title) && isset($content))) {
      http_response_code(400);
      $retval['status'] = 'ID, title and content must be specified';
      echo json_encode($retval);
      exit;
    }

    $stmt = $pdo->prepare('
      SELECT owner_id FROM notes
      WHERE id = :id
    ');
    $stmt->execute(['id' => $id]);
    $owner_id = $stmt->fetch(PDO::FETCH_ASSOC)['owner_id'];

    if (!$owner_id) {
      http_response_code(400);
      $retval['status'] = 'Error: Specified note not found';
      echo json_encode($retval);
      exit;
    }

    if (!$owner_id !== $user['id'] && $user['role'] !== 'r') {
      http_response_code(401);
      $retval['status'] = 'Error: Not authorized to updated specified note';
      echo json_encode($retval);
      exit;
    }

    $stmt = $pdo->prepare('
      UPDATE notes
      SET title = :title, content = :content, last_updated = :last_updated
      WHERE id = :id
    ');
    $stmt->execute(['title' => $title, 'content' => $content, 'last_updated' => $current_time, 'id' => $id]);

    $stmt = $pdo->prepare('
      SELECT * FROM notes
      WHERE id = :id
    ');
    $stmt->execute(['id' => $id]);
    $note = $stmt->fetch(PDO::FETCH_ASSOC);
    $note['creation_id'] = null;
    $retval['note'] = $note;
    $retval['status'] = 'Success';
    echo json_encode($retval);
    break;
  default:
    http_response_code(405);
    $retval['status'] = "Error: Unhandled method $method on endpoint $path";
    echo json_encode($retval);
    break;
}

function prepare_select_notes($pdo) {
  return $pdo->prepare('
    SELECT notes.id AS "id", title, content, users.name AS "owner", created_at, last_updated FROM notes
    JOIN users ON notes.owner_id = users.id
    WHERE owner_id = :user
    ORDER BY last_updated
  ');
}
