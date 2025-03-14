<?php
// rank.php

// ----------------------------------------------------
// 기본 설정 및 헤더 전송
// ----------------------------------------------------
date_default_timezone_set('Asia/Seoul');

$errorMsg = "";

/*-----------------------------------------------
  HTTPS 접속 여부 확인
-----------------------------------------------*/
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    $errorMsg = "HTTPS를 사용하여 접속하여야 합니다.";
    include('html/error.html');
    exit;
}

// ----------------------------------------------------
// 환경설정 파일 (.env) 확인 및 로드
// ----------------------------------------------------
$envPath = '../.env';
if (!file_exists($envPath)) {
    $errorMsg = "환경설정 파일이 존재하지 않습니다.";
    include('html/error.html');
    exit;
}

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

// ----------------------------------------------------
// 데이터베이스 연결 (PDO)
// ----------------------------------------------------
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

// ----------------------------------------------------
// account 쿠키를 통해 로그인 여부 확인
// ----------------------------------------------------
$currentUserId = null;
if (isset($_COOKIE['account'])) {
    $token = $_COOKIE['account'];
    try {
        $stmt = $pdo->prepare("SELECT id, block FROM account WHERE token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && $user['block'] != 1) {
            $currentUserId = $user['id'];
        }
    } catch (PDOException $e) {
        // 오류 발생 시 현재 사용자는 null로 유지
        $currentUserId = null;
    }
}

// ----------------------------------------------------
// click 테이블에서 상위 100명의 랭킹 데이터 조회
// ----------------------------------------------------
try {
    $sql = "SELECT id, name, count, date FROM click ORDER BY count DESC LIMIT 100";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMsg = "랭킹 데이터를 조회하는데 실패하였습니다.";
    include('html/error.html');
    exit;
}

// ----------------------------------------------------
// 랭킹 데이터 배열 생성 (순위, me 플래그 추가)
// ----------------------------------------------------
$rankings = [];
$rank = 0;
foreach ($results as $row) {
    $rank++;
    $rankings[] = [
        'rank'  => $rank,
        'name'  => $row['name'],
        'count' => (int)$row['count'],
        'date'  => $row['date'],
        'me'    => ($currentUserId !== null && $row['id'] === $currentUserId)
    ];
}

// ----------------------------------------------------
// 랭킹 페이지 포함 (html/rank.html)
// ----------------------------------------------------
include('html/rank.html');

?>