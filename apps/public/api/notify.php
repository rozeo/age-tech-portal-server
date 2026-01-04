<?php

require_once __DIR__ . "/../../index.php";
require_once __DIR__ . "/../../sql/connection.php";

use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Factory;
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

if (!is_string($appId) || !in_array($state, ['GOOD_MORNING', 'GOOD_NIGHT', 'HELP_ME'])) {
    http_response_code(400);
    echo "INVALID REQUEST.4";
    exit();
}

$uuidRegex = "/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/";
if (!preg_match('/^[0-9a-f\-]+$/', $appId)) {
    http_response_code(400);
        echo "INVALID REQUEST.5";
        exit();
}
// end request validation

// start action
$pdo = createConnection();

// fetch subscribe device targets
$kvsCooldownKeyName = "subscribe_cooldown_$appId";

$deviceFetchStatement = $pdo->prepare(
    "SELECT 
        subscribes.app_id, subscribes.target_app_id, devices.device_token 
    FROM subscribes 
    LEFT JOIN devices ON subscribes.target_app_id = devices.app_id
    WHERE subscribes.app_id = ?");
$deviceFetchStatement->execute([$appId]);

$subscribes = $deviceFetchStatement->fetchAll();

$completions = 0;
if (count($subscribes) > 0) {
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

    foreach ($subscribes as $subscribe) {
        $targetAppId = $subscribe['target_app_id'];
        $targetToken = $subscribe['device_token'];

        try {
            debug_log("Send notification $appId -> $targetAppId");
            $messagingClient->send($message->toToken($targetToken));
            $completions++;
        } catch (Throwable $e) {
            debug_log("Failed send fcm notification message, reason = " . $e->getMessage());
        }
    }
}

debug_log("Send notifications to $completions tokens.");