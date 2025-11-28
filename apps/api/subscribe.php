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
$token = $request['token'];

if (!is_string($appId) || !is_string($token)) {
    http_response_code(400);
    echo "INVALID REQUEST.4";
    exit();
}

if (!preg_match('/^[0-9a-f\-]+$/', $appId)) {
    http_response_code(400);
        echo "INVALID REQUEST.5";
        exit();
}

// end request validation

// fetch subscribe device targets
$kvsIndexKeyName = "subscribe_index_$appId";
$kvsDeviceTokenKeyPrefix = "subscribe_device_{$appId}_"; // + index number

// initialize subscribe index entry
apcu_add($kvsIndexKeyName, 0);

// increment device index
$newIndex = apcu_inc($kvsIndexKeyName, step: 1);

// set new token into kvs entry
apcu_store($kvsDeviceTokenKeyPrefix . $newIndex, $token);

echo "OK";