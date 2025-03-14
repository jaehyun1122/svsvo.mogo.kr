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

// 환경 변수 로드
function loadEnv($path) {
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}

// 환경 변수 로드
loadEnv($envPath);

// 환경 변수 가져오기
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$dbname = getenv('DB_NAME');
$pass = getenv('DB_PASS');
$setupAccount = getenv('SETUP_ACCOUNT');

if (!$host || !$user || !$dbname || !$pass || !$setupAccount) {
    res(2, '데이터베이스 설정이 잘못되었습니다.');
}

// DB 연결
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    res(2, '데이터베이스 연결에 실패하였습니다.');
}

// JSON 입력 받기
$data = json_decode(file_get_contents('php://input'), true);

// 필수값 확인
if (!isset($data['password'])) {
    res(2, '잘못된 요청입니다.');
}

// 설치 비밀번호 확인
if ($data['password'] !== $setupAccount) {
    res(2, '설치 권한이 없습니다.');
}

// 테이블 존재 여부 확인
$stmt = $pdo->query("SHOW TABLES LIKE 'account'");
$tableExists = $stmt->rowCount() > 0;

// 설치되지 않았는데 재설치 요청 시 예외 처리
if (!$tableExists && isset($data['reinstall']) && $data['reinstall'] === true) {
    res(2, '설치되지 않은 상태에서 재설치를 요청할 수 없습니다.');
}

// 설치된 상태에서 설치 요청 시 예외 처리
if ($tableExists && isset($data['install']) && $data['install'] === true) {
    res(2, '이미 설치된 상태에서 다시 설치할 수 없습니다.');
}

// 기존 테이블 삭제 후 재설치 처리
if ($tableExists && isset($data['reinstall']) && $data['reinstall'] === true) {
    try {
        $pdo->exec("DROP TABLE IF EXISTS account");
    } catch (PDOException $e) {
        res(2, '기존 테이블 삭제 실패하였습니다.');
    }
}

// account 테이블 생성
try {
    $query = "CREATE TABLE IF NOT EXISTS account (
        id VARCHAR(20) PRIMARY KEY,
        email VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(10) NOT NULL,
        ip VARCHAR(50) NOT NULL,
        block TINYINT(1) NOT NULL DEFAULT 0,
        token VARCHAR(64) UNIQUE DEFAULT NULL,
        date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($query);

    // 성공 응답
    res(1, '성공적으로 처리되었습니다.');

} catch (PDOException $e) {
    res(2, '테이블 생성 실패하였습니다.');
}

?>