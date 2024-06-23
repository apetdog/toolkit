<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

return [
  'secretId' => $_ENV['COS_SECRET_ID'],
  'secretKey' => $_ENV['COS_SECRET_KEY'],
  'region' => $_ENV['COS_REGION'],
  'bucket' => $_ENV['COS_BUCKET'],
  'uploadDir' => $_ENV['COS_UPLOAD_DIR'],
];