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

// JSON 입력
$data = json_decode(file_get_contents('php://input'), true);

// 필수값 확인
$req = ['id', 'pw'];
foreach ($req as $r) {
    if (!isset($data[$r]) || trim($data[$r]) === '') {
        res(2, ucfirst($r) . ' 값이 없습니다.');
    }
}

// 값 정리
$login = trim($data['id']);
$pw = trim($data['pw']);

// 이메일인지 아이디인지 판별
$field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'id';

// 사용자 조회 (비밀번호 포함)
$stmt = $pdo->prepare("SELECT id, password, token, name, block FROM account WHERE $field = :login");
$stmt->execute([':login' => $login]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 계정 존재 확인
if (!$user) {
    res(2, '이메일 또는 패스워드가 틀렸습니다.');
}

// 비밀번호 확인
if (!password_verify($pw, $user['password'])) {
    res(2, '이메일 또는 패스워드가 틀렸습니다.');
}

// 계정 차단 확인
if ($user['block'] == 1) {
    res(2, '계정이 차단되었습니다.');
}

// 토큰 확인 및 발급
$token = $user['token'];
if (!$token) {
    $token = bin2hex(random_bytes(32));
    $pdo->prepare("UPDATE account SET token = :token WHERE id = :id")->execute([':token' => $token, ':id' => $user['id']]);
}

// 쿠키 설정 (Secure, HttpOnly)
setcookie("account", $token, [
    'expires' => time() + 259200, // 3일
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

// 성공 응답
res(1, '로그인에 성공하였습니다.');

?>