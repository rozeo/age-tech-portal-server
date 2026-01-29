<?php

require_once __DIR__ . "/../../index.php";
require_once __DIR__ . "/../../sql/connection.php";
require_once __DIR__ . "/../../stores/subscriptions.php";

// appId = 通知元デバイス
// targetAppId = 通知先デバイス

// validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !str_starts_with($_SERVER['CONTENT_TYPE'], 'application/json')) {
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

if (!isset($request['app_id']) || !isset($request['target_app_id'])) {
    http_response_code(400);
    echo "INVALID REQUEST.3";
    exit();
}

$appId = $request['app_id'];
$targetAppId = $request['target_app_id'];

if (!is_string($appId) || !is_string($targetAppId)) {
    http_response_code(400);
    echo "INVALID REQUEST.4";
    exit();
}

$uuidRegex = "/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/";
if (!preg_match($uuidRegex, $appId) || !preg_match($uuidRegex, $targetAppId)) {
    http_response_code(400);
        echo "INVALID REQUEST.5";
        exit();
}

// end request validation

storeSubscription($appId, $targetAppId);

echo "OK";