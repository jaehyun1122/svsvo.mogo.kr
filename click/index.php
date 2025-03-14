<?php
// index.php

// 타임존 설정 (서울)
date_default_timezone_set('Asia/Seoul');

// 에러 메시지 변수, 쿠키, 환경파일 체크 등 기존 코드 유지
$errorMsg = "";

/*-----------------------------------------------
  HTTPS 접속 여부 확인
-----------------------------------------------*/
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    $errorMsg = "HTTPS를 사용하여 접속하여야 합니다.";
    include('html/error.html');
    exit;
}

/*-----------------------------------------------
  쿠키 "account" 존재 여부 확인
-----------------------------------------------*/
if (!isset($_COOKIE['account'])) {
    include('html/login.html');
    exit;
}

/*-----------------------------------------------
  환경설정 파일 (.env) 존재 여부 확인
-----------------------------------------------*/
$envPath = '../.env';
if (!file_exists($envPath)) {
    $errorMsg = "환경설정 파일이 존재하지 않습니다.";
    include('html/error.html');
    exit;
}

/*-----------------------------------------------
  환경변수 로드
-----------------------------------------------*/
function loadEnv($path) {
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}
loadEnv($envPath);

/*-----------------------------------------------
  데이터베이스 연결 (PDO)
-----------------------------------------------*/
$host   = getenv('DB_HOST');
$dbUser = getenv('DB_USER');
$dbName = getenv('DB_NAME');
$dbPass = getenv('DB_PASS');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    $errorMsg = "데이터베이스 연결에 실패하였습니다.";
    include('html/error.html');
    exit;
}

/*-----------------------------------------------
  account 테이블에서 사용자 인증 (쿠키 토큰 기준)
-----------------------------------------------*/
$token = $_COOKIE['account'];
try {
    $stmt = $pdo->prepare("SELECT id, name, block FROM account WHERE token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMsg = "데이터베이스 계정 연결에 실패하였습니다.";
    include('html/error.html');
    exit;
}

if (!$user) {
    $errorMsg = "유효하지 않은 로그인 토큰입니다.";
    include('html/error.html');
    exit;
} elseif ($user['block'] == 1) {
    $errorMsg = "차단된 사용자입니다.";
    include('html/error.html');
    exit;
}

/*-----------------------------------------------
  click 테이블에서 사용자 등록 및 확인
-----------------------------------------------*/
// 사용자가 이미 등록되어 있는지 확인
try {
    $stmt = $pdo->prepare("SELECT * FROM click WHERE id = ?");
    $stmt->execute([$user['id']]);
    $clickUser = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMsg = "데이터베이스 클릭왕 연결에 실패하였습니다.";
    include('html/error.html');
    exit;
}

// 등록된 정보가 없으면 신규 등록 후 다시 조회
if (!$clickUser) {
    // 서울 시간 문자열
    $currentDateSeoul = date("Y-m-d H:i:s");
    
    $stmt = $pdo->prepare("INSERT INTO click (id, name, count, date) VALUES (?, ?, 0, ?)");
    $stmt->execute([$user['id'], $user['name'], $currentDateSeoul]);
    
    $stmt = $pdo->prepare("SELECT * FROM click WHERE id = ?");
    $stmt->execute([$user['id']]);
    $clickUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$clickUser) {
    $errorMsg = "클릭왕 등록에 실패하였습니다.";
    include('html/error.html');
    exit;
} elseif ($clickUser['block'] == 1) {
    $errorMsg = "차단된 사용자입니다.";
    include('html/error.html');
    exit;
}

/*-----------------------------------------------
  CSRF 토큰 생성 (없으면 생성)
-----------------------------------------------*/
if (!isset($_COOKIE['csrf_token'])) {
    $csrfToken = bin2hex(random_bytes(32));
    setcookie("csrf_token", $csrfToken, time()+86400, "/", "", isset($_SERVER['HTTPS']), true);
} else {
    $csrfToken = $_COOKIE['csrf_token'];
}

/*-----------------------------------------------
  전달할 정보 설정
-----------------------------------------------*/
$stmt = $pdo->prepare("SELECT count, date FROM click_total WHERE id = 1");
$stmt->execute();
$clickTotal = $stmt->fetch(PDO::FETCH_ASSOC);

$reNum = rand(100000, 999999);

$info = [
    'id'         => $clickUser['id'],
    'name'       => $clickUser['name'],
    'click_user_total'      => $clickUser['count'],
    'click_user_date'  => $clickUser['date'],
    'click_total'      => $clickTotal['count'],
    'click_total_date' => $clickTotal['date'],
    'key_update' => $reNum,
    'csrf_token' => $csrfToken
];

include('html/main.html');
?>