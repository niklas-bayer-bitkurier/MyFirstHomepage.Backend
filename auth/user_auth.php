<?php

function create_salt() {
  $salt = bin2hex(random_bytes(32));
  return $salt;
}

function compute_hash($password, $salt) {
  $combined = $password.$salt;
  $hash = hash('sha256', $combined);
  return $hash;
}

function get_id_if_matches($user_name, $password, $pdo) {
  $stmt = $pdo->prepare('
    SELECT id, password, salt FROM users
    WHERE name = :name
    LIMIT 2
  ');
  $stmt->execute(['name' => $user_name]);
  $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $user_count = count($users);

  if ($user_count > 1) {
    throw new RuntimeException("Multiple users with the same username found: $user_name, count: $user_count");
  }
  else if ($user_count === 0) {
    return -1;
  }
  $user = $users[0];
  $salted = compute_hash($password, $user['salt']);
  return $salted === $user['password'] ? $user['id'] : -1;
}

function register_user($user, $pdo) {
  $stmt = $pdo->prepare('
    SELECT COUNT(id) FROM users
    WHERE name = :name
  ');
  $stmt->execute(['name' => $user['name']]);
  $count = $stmt->fetch(PDO::FETCH_ASSOC)['COUNT(id)'];
  
  if ($count !== 0) {
    return -1;
  }

  if (!isset($user['email']))
    $user['email'] = null;

  if (!isset($user['role']))
    $user['role'] = 'u';

  $stmt = $pdo->prepare('
    INSERT INTO users (name, email, password, salt, role)
    VALUES (:name, :email, :password, :salt, :role)
  ');
  $stmt->execute($user);
  $id = (int) $pdo->lastInsertId();
  return $id;
}
