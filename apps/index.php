<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE);

require_once __DIR__ . "/../vendor/autoload.php";

date_default_timezone_set('Asia/Tokyo');

define('NOTIFICATION_COOLDOWN', 30); // 30sec