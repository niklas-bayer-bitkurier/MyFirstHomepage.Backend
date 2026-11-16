<?php

require_once __DIR__.'/../../config/database.php';

if (!isset($session) || $session === null) {
  http_response_code(401);
  echo json_encode(['status' => "Error: Not authorized to access endpoint $path"]);
  exit;
}

$user = $session['user'];

switch ($method) {
  case 'POST':
    $currentTime = time();
    $data = json_decode(file_get_contents('php://input'), true);
    $note_id = $data['noteId'];
    $recipient_name = $data['recipientName'];

    if (!isset($note_id) || !isset($recipient_name)) {
      http_response_code(400);
      echo json_encode(['status' => 'Note id and recipient name must be specified']);
      exit;
    }

    if ($recipient_name === $user['name']) {
      http_response_code(400);
      echo json_encode(['status' => 'Cannot transfer note yourself']);
      exit;
    }

    $stmt = $pdo->prepare('
      SELECT owner_id AS "id", users.name AS "owner_name" FROM notes
      JOIN users ON owner_id = users.id
      WHERE notes.id = :id
    ');
    $stmt->execute(['id' => $note_id]);
    $owner = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($owner['id'] !== $user['id'] && $user['role'] !== 'r') {
      http_response_code(404);
      echo json_encode(['status' => 'Specified resource does not exist']);
      exit;
    }

    $stmt = $pdo->prepare('
      SELECT id FROM users
      WHERE name = :name
    ');
    $stmt->execute(['name' => $recipient_name]);
    $recipient_id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];

    if (!isset($recipient_id)) {
      http_response_code(400);
      echo json_encode(['status' => 'Unknown recipient']);
      exit;
    }

    $pdo->beginTransaction();
    $stmt = $pdo->prepare('
      INSERT INTO note_transfers (
        note_id, owner_id, recipient_id, initiator_id, created_at, accepted_at
      ) VALUES (
        :note_id, :owner_id, :recipient_id, :initiator_id, :created_at, null
      )
    ');
    $stmt->execute([
      'note_id' => $note_id,
      'owner_id' => $owner['id'],
      'recipient_id' => $recipient_id,
      'initiator_id' => $user['id'],
      'created_at' => $currentTime
    ]);
    $transfer_id = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare('
      UPDATE notes
      SET is_in_transfer = TRUE
      WHERE id = :note_id
    ');
    $stmt->execute([
      'note_id' => $note_id
    ]);

    $stmt = $pdo->prepare('
      INSERT INTO messages (sender_id, recipient_id, subject, content, sent_at, reference_type, reference_id)
      VALUES (
        :sender_id, :recipient_id, :subject, :content, :sent_at, :reference_type, :reference_id
      )
    ');
    $stmt->execute([
      'sender_id' => $owner['id'],
      'recipient_id' => $recipient_id,
      'subject' => 'Note transfer request',
      'content' => "{$owner['id']} wants to transfer a note to you",
      'sent_at' => $currentTime,
      'reference_type' => 'transfer',
      'reference_id' => $transfer_id
    ]);
    $pdo->commit();
    echo json_encode(['status' => 'Transfer initialized']);
    break;
  default:
    http_response_code(405);
    echo json_encode(['status' => "Error: Unhandled method $method on endpoint $path"]);
    break;
}
