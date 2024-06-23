<?php
require 'vendor/autoload.php';
$config = require 'config.php';

use Qcloud\Cos\Client;

header('Content-Type: application/json');

$cosClient = new Client([
  'region' => $config['region'],
  'schema' => 'https',
  'credentials' => [
    'secretId' => $config['secretId'],
    'secretKey' => $config['secretKey']
  ]
]);

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = ltrim(substr($path, strpos($path, '/api')), '/api');
$path = '/' . ltrim($path, '/');

error_log("Request Method: $method, Path: $path");

$routes = [
  'GET' => [
    '#^/list(\?.*)?$#' => fn() => listImages($cosClient, $config)
  ],
  'POST' => [
    '/upload' => fn() => uploadImage($cosClient, $config)
  ],
  'DELETE' => [
    '#^/delete/(.+)$#' => fn($key) => deleteImage($cosClient, $config, urldecode($key))
  ]
];

if (isset($routes[$method])) {
  foreach ($routes[$method] as $route => $handler) {
    if ($route[0] === '#') {
      if (preg_match($route, $path, $matches)) {
        $handler($matches[1]);
        exit;
      }
    } elseif ($route === $path) {
      $handler();
      exit;
    }
  }
}

http_response_code(404);
echo json_encode(['success' => false, 'error' => 'Not Found']);

function listImages($cosClient, $config)
{
  try {
    $marker = $_GET['marker'] ?? '';
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;

    $result = $cosClient->listObjects([
      'Bucket' => $config['bucket'],
      'Prefix' => $config['uploadDir'] . '/',
      'Marker' => $marker,
      'MaxKeys' => $limit,
    ]);

    $images = array_map(function ($content) use ($cosClient, $config) {
      $key = $content['Key'];
      if (substr($content['Key'], -1) !== '/') {
        return [
          'url' => $cosClient->getObjectUrl($config['bucket'], $key),
          'filename' => basename($key),
          'key' => $key
        ];
      };
    }, $result['Contents'] ?? []);

    $images = array_filter($images);

    $response = [
      'success' => true,
      'images' => $images,
      'isTruncated' => $result['IsTurncated'],
      'nextMarker' => $result['NextMarker'] ?? null,
    ];

    echo json_encode($response);
  } catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
  }
}

function uploadImage($cosClient, $config)
{
  if (!isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No image file provided']);
    return;
  }

  $file = $_FILES['image'];
  $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
  $fileName = time() . '.' . $fileExtension;

  try {
    $cosClient->putObject([
      'Bucket' => $config['bucket'],
      'Key' => $config['uploadDir'] . '/' . $fileName,
      'Body' => fopen($file['tmp_name'], 'rb')
    ]);
    echo json_encode(['success' => true, 'message' => 'Image uploaded successfully']);
  } catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
  }
}

function deleteImage($cosClient, $config, $key)
{
  try {
    $cosClient->deleteObject([
      'Bucket' => $config['bucket'],
      'Key' => $key
    ]);
    echo json_encode(['success' => true, 'message' => 'Image deleted successfully']);
  } catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
  }
}