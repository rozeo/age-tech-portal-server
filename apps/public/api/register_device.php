<?php

require_once __DIR__ . "/../../index.php";
require_once __DIR__ . "/../../sql/connection.php";

use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

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

if (!isset($request['app_id']) || !isset($request['token'])) {
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

$uuidRegex = "/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/";
if (!preg_match($uuidRegex, $appId)) {
    http_response_code(400);
        echo "INVALID REQUEST.5";
        exit();
}
// end request validation

// check valid fcm token format
$messagingClient = new Factory()->createMessaging();
$message = CloudMessage::new()->withData(['type' => 'validation']);
try {
    $messagingClient->send($message->toToken($token), validateOnly: true);
} catch (Exception $e) {
    http_response_code(400);
    echo "INVALID REQUEST.6";
    exit();
}

$pdo = createConnection();
$pdo->beginTransaction();

try {
    $checkStatement = $pdo->prepare("SELECT * FROM devices WHERE app_id = ? FOR UPDATE");
    $checkStatement->execute([$appId]);

    $currentDatetime = new DateTime()->format(DateTime::ATOM);

    if ($checkStatement->rowCount() === 0) {
        // register device record if not registered
        $insertStatement = $pdo->prepare("INSERT INTO devices (app_id, device_token, created_at, updated_at) VALUES (?, ?, ?, ?)");
        $insertStatement->execute([$appId, $token, $currentDatetime, $currentDatetime]);
    } else {
        // update device token if already registered
        $updateStatement = $pdo->prepare("UPDATE devices SET device_token = ?, updated_at = ? WHERE app_id = ?");
        $updateStatement->execute([$token, $currentDatetime, $appId]);
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollback();
    fwrite(STDERR, $e->getMessage());
    http_response_code(500);
    exit();
}

echo "OK.";
