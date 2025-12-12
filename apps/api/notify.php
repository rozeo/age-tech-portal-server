<?php

require_once __DIR__ . "/../index.php";

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\CloudMessage;

$messagingClient = new Factory()->createMessaging();

// validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !str_starts_with($_SERVER['CONTENT_TYPE'], 'application/json')) {
    http_response_code(400);
    echo "INVALID REQUEST.1, " . ($_SERVER['CONTENT_TYPE'] ?? '');
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

if (!preg_match('/^[0-9a-f\-]+$/', $appId)) {
    http_response_code(400);
        echo "INVALID REQUEST.5";
        exit();
}
// end request validation

// fetch subscribe device targets
$kvsIndexKeyName = "subscribe_index_$appId";
$kvsDeviceTokenKeyPrefix = "subscribe_device_{$appId}_"; // + index number
$kvsCooldownKeyName = "subscribe_cooldown_$appId";

$deviceCount = (int) apcu_fetch($kvsIndexKeyName);

$targetTokens = [];
for ($i = 1; $i <= $deviceCount; $i++) {
     $kvsDeviceTokenKeyName = $kvsDeviceTokenKeyPrefix . $i;
     $deviceToken = apcu_fetch($kvsDeviceTokenKeyName, $isSuccess);
     if ($isSuccess) {
        $targetTokens[] = $deviceToken;
     }
}

if (count($targetTokens) > 0) {
    // check notification cooldown time.
    // apcu_add return falsy if key already exists, so check falsy result equals exists checking
    if (!apcu_add($kvsCooldownKeyName, 'COOLDOWN', ttl: NOTIFICATION_COOLDOWN)) {
        http_response_code(429);
        echo "NOTIFICATION COOLDOWN NOW.";
        exit();
    }

    $message = CloudMessage::new()
        ->withData([
            'type' => 'message', 
            'app_id' => $appId,
            'state' => $state,
        ]);

    foreach ($targetTokens as $targetToken) {
        $messagingClient->send($message->toToken($targetToken));
    }
}

echo "Send notifications to " . count($targetTokens) . " devices.";