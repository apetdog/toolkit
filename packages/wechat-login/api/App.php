<?php

namespace App;

use PDO;
use PDOException;

class App
{
  private $pdo;
  private $config;

  public function __construct()
  {
    $this->loadConfig();
    $this->connectDb();
    $this->createTableIfNotExists();
  }

  private function loadConfig()
  {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    $this->config = [
      'appid' => $_ENV['WECHAT_APPID'],
      'secret' => $_ENV['WECHAT_SECRET'],
      'redirect_uri' => $_ENV['REDIRECT_URI'],
      'db_path' => $_ENV['DB_PATH'] ?? __DIR__ . '/database.sqlite',
    ];
  }

  private function connectDb()
  {
    try {
      $this->pdo = new PDO("sqlite:{$this->config['db_path']}");
      $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      $this->jsonResponse(['error' => 'Database connection failed: ' . $e->getMessage()], 500);
    }
  }

  private function createTableIfNotExists()
  {
    $sql = "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            openid TEXT NOT NULL UNIQUE,
            nickname TEXT,
            avatar TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
    $this->pdo->exec($sql);
  }

  public function run()
  {
    $route = $_GET['route'] ?? '';

    switch ($route) {
      case 'login':
        $this->login();
        break;
      case 'callback':
        $this->callback();
        break;
      case 'user':
        $this->getUser();
        break;
      case 'logout':
        $this->logout();
        break;
      default:
        $this->jsonResponse(['error' => 'Not Found'], 404);
    }
  }

  private function login()
  {
    $qrcode_url = "https://open.weixin.qq.com/connect/qrconnect?appid={$this->config['appid']}&redirect_uri=" . urlencode($this->config['redirect_uri']) . "&response_type=code&scope=snsapi_login&state=STATE#wechat_redirect";
    $this->jsonResponse(['qrcodeUrl' => $qrcode_url]);
  }

  private function callback()
  {
    if (!isset($_GET['code'])) {
      $this->jsonResponse(['error' => 'No code parameter received'], 400);
    }

    $code = $_GET['code'];

    $token_info = $this->getAccessToken($code);

    if (!isset($token_info['access_token'])) {
      $this->jsonResponse(['error' => 'Failed to get access_token'], 400);
    }

    $access_token = $token_info['access_token'];
    $openid = $token_info['openid'];

    $user_info = $this->getUserInfo($access_token, $openid);

    $user = $this->getUserByOpenid($openid);

    if (!$user) {
      $user = $this->createUser($user_info);
    } else {
      $this->updateUser($user['id'], $user_info);
    }

    $this->startSession($user['id']);

    header('Location: /');
    exit();
  }

  private function getUser()
  {
    $user_id = $this->getSessionUserId();
    if (!$user_id) {
      $this->jsonResponse(['user' => null]);
    }

    $user = $this->getUserById($user_id);
    $this->jsonResponse(['user' => $user]);
  }

  private function logout()
  {
    $this->destroySession();
    $this->jsonResponse(['success' => true]);
  }

  private function getUserByOpenid($openid)
  {
    $stmt = $this->pdo->prepare("SELECT * FROM users WHERE openid = :openid");
    $stmt->execute(['openid' => $openid]);
    return $stmt->fetch();
  }

  private function getUserById($id)
  {
    $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $id]);
    return $stmt->fetch();
  }

  private function createUser($user_info)
  {
    $stmt = $this->pdo->prepare("INSERT INTO users (openid, nickname, avatar) VALUES (:openid, :nickname, :avatar)");
    $stmt->execute([
      'openid' => $user_info['openid'],
      'nickname' => $user_info['nickname'],
      'avatar' => $user_info['headimgurl']
    ]);
    return $this->getUserByOpenid($user_info['openid']);
  }

  private function updateUser($id, $user_info)
  {
    $stmt = $this->pdo->prepare("UPDATE users SET nickname = :nickname, avatar = :avatar WHERE id = :id");
    $stmt->execute([
      'id' => $id,
      'nickname' => $user_info['nickname'],
      'avatar' => $user_info['headimgurl']
    ]);
  }

  private function getAccessToken($code)
  {
    $token_url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$this->config['appid']}&secret={$this->config['secret']}&code=$code&grant_type=authorization_code";
    $response = file_get_contents($token_url);
    return json_decode($response, true);
  }

  private function getUserInfo($access_token, $openid)
  {
    $user_info_url = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid";
    $user_info_response = file_get_contents($user_info_url);
    return json_decode($user_info_response, true);
  }

  private function startSession($user_id)
  {
    session_start();
    $_SESSION['user_id'] = $user_id;
  }

  private function getSessionUserId()
  {
    session_start();
    return $_SESSION['user_id'] ?? null;
  }

  private function destroySession()
  {
    session_start();
    session_destroy();
  }

  private function jsonResponse($data, $statusCode = 200)
  {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode([
      'status' => $statusCode < 400 ? 'success' : 'error',
      'data' => $data
    ]);
    exit;
  }
}
