<?php

require_once __DIR__ . '/cloudstorage.php';

function storeDeviceToken(string $appId, string $deviceToken): void
{
    $bucket = createGoogleCloudStorageClient();
    $objectKey = "device_tokens/" . substr($appId, 0, 2) . "/" . $appId;

    $bucket->upload($deviceToken, ['name' => $objectKey]);
}

function fetchDeviceToken(string $appId): ?string
{
    $bucket = createGoogleCloudStorageClient();
    $objectKey = "device_tokens/" . substr($appId, 0, 2) . "/" . $appId;

    $object = $bucket->object($objectKey);

    return $object->exists() ? $object->downloadAsString() : null;
}