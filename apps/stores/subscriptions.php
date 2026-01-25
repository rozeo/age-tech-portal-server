<?php

require_once __DIR__ . '/cloudstorage.php';

function storeSubscription(string $appId, string $subscribeAppId): void
{
    $bucket = createGoogleCloudStorageClient();
    $objectKey = "subscriptions/" . substr($appId, 0, 2) . "/$appId/$subscribeAppId";

    $bucket->upload('', ['name' => $objectKey]);
}

function fetchSubscriptions(string $appId): array
{
    $bucket = createGoogleCloudStorageClient();
    $prefix = "subscriptions/" . substr($appId, 0, 2) . "/$appId/";

    $objects = $bucket->objects(['prefix' => $prefix . "*"]);

    $subscribeAppIds = [];
    foreach ($objects as $ob) {
        $subscribeAppIds[] = str_replace($prefix, '', $ob->name());
    }

    return $subscribeAppIds;
}

function removeSubscription(string $appId, string $subscribeAppId): void
{
    $bucket = createGoogleCloudStorageClient();
    $objectKey = "subscriptions/" . substr($appId, 0, 2) . "/$appId/$subscribeAppId";

    $bucket->delete(['name' => $objectKey]);
}