<?php

use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\StorageClient;

function createGoogleCloudStorageClient(): Bucket
{
    $bucketName = getenv('CLOUD_STORAGE_BUCKET_NAME');

    $client = new StorageClient();
    return $client->bucket($bucketName);
}