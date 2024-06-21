<?php
if (preg_match('/\/pic\/(\d+)x(\d+)/', $_SERVER['REQUEST_URI'], $matches)) {
  $width = (int)$matches[1];
  $height = (int)$matches[2];

  $imageUrl = "https://picsum.photos/{$width}/{$height}";

  $ch = curl_init($imageUrl);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HEADER, false);

  $response = curl_exec($ch);

  if (curl_errno($ch)) {
    http_response_code(500);
    echo 'Failed to return image.';
  } else {
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    header('Content-Type: ' . $contentType);

    echo $response;
    exit;
  }
} else {
  http_response_code(404);
  echo 'Invalid image dimensions.';
  exit;
}
