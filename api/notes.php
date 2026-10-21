<?php

require_once __DIR__ . '/../config/database.php';

switch ($method) {
    case 'GET':
        if (!isset($_GET['user'])) {
            http_response_code(400);
            echo json_encode(['error' => "GET must specify a query"]);
            exit;
        }
        $user = filter_input(INPUT_GET, 'user', FILTER_VALIDATE_INT);
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
