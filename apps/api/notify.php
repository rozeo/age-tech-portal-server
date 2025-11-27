<?php

require_once __DIR__ . "/../index.php";

// validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SERVER['CONTENT_TYPE'] !== 'application/json') {
    http_response_code(400);
    echo "INVALID REQUEST.1";
    exit();
}

$request = json_decode(file_get_contents('php://input'), true);
if ($request === false) {
    http_response_code(422);
    echo "INVALID REQUEST.2";
    exit();
}

if (!isset($request['app_id']) || !isset($request['state'])) {
    http_response_code(400);
    echo "INVALID REQUEST.3";
    exit();
}

$appId = $request['app_id'];
$state = $request['state'];

if (!is_string($appId) || !in_array($state, ['GOOD_MORNING', 'GOOD_NIGHT'])) {
    http_response_code(400);
    echo "INVALID REQUEST.4";
    exit();
}
// end request validation

echo "OK";