<?php

require_once __DIR__.'/../config/database.php';

switch ($method) {
  case 'POST':

    break;
  default:
    http_response_code(405);
    echo json_enode(['error' => "Unhandled method $method on endpoint $path"]);
    break;
}
