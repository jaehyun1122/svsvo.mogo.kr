<?php

header('Content-Type: application/json; charset=UTF-8');

// 타임존 설정 (대한민국 서울)
date_default_timezone_set('Asia/Seoul');

// 응답 함수
function res($status, $msg) {
    echo json_encode([
        'status' => $status,
        'msg' => $msg,
        'time' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// HTTPS 확인
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    res(2, 'HTTPS를 사용해야 합니다.');
}

// POST 요청 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    res(2, 'POST 요청만 허용됩니다.');
}

// .env 파일 로드
$envPath = '../.env';
if (!file_exists($envPath)) {
    res(2, '환경설정 파일이 없습니다.');
}

// 환경 변수를 안전하게 불러오는 함수
function loadEnv($path) {
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue; // 주석 무시
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!empty($key) && !empty($value)) {
            putenv("$key=$value");
        }
    }
}

// 환경 변수 로드
loadEnv($envPath);

// 환경 변수 가져오기
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$dbname = getenv('DB_NAME');
$pass = getenv('DB_PASS');

if (!$host || !$user || !$dbname || !$pass) {
    res(2, '데이터베이스 설정이 잘못되었습니다.');
}

// DB 연결
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    res(2, '데이터베이스 연결에 실패하였습니다.');
}

// 로그인 토큰 확인
if (!isset($_COOKIE['account']) || empty($_COOKIE['account'])) {
    res(2, '로그인이 필요합니다.');
}

$token = $_COOKIE['account'];

// JSON 입력
$data = json_decode(file_get_contents('php://input'), true);

// 필수값 확인
$req = ['pw', 'terms'];
foreach ($req as $r) {
    if (!isset($data[$r]) || trim($data[$r]) === '') {
        res(2, ucfirst($r) . ' 값이 없습니다.');
    }
}

// 값 정리
$pw = trim($data['pw']);
$terms = $data['terms'];

// 약관 동의 확인
if ($terms !== true) {
    res(2, '약관에 동의해야 합니다.');
}

// 계정 확인 (토큰으로 사용자 찾기)
$stmt = $pdo->prepare("SELECT id, password FROM account WHERE token = :token");
$stmt->execute([':token' => $token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    res(2, '유효하지 않은 로그인 상태입니다.');
}

// 비밀번호 확인
if (!password_verify($pw, $user['password'])) {
    res(2, '비밀번호가 올바르지 않습니다.');
}

// 계정 삭제
try {
    $stmt = $pdo->prepare("DELETE FROM account WHERE id = :id");
    $stmt->execute([':id' => $user['id']]);

    // 쿠키 삭제
    setcookie("account", "", [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);

    res(1, '계정이 삭제되었습니다.');
} catch (PDOException $e) {
    res(2, '계정 삭제 중 오류가 발생하였습니다.');
}

?>