<?php

header('Content-Type: application/json; charset=UTF-8');

// 타임존 설정 (대한민국 서울)
date_default_timezone_set('Asia/Seoul');

/*-----------------------------------------------
  응답 함수 정의
  - 상태와 메시지를 JSON으로 출력하고 종료합니다.
-----------------------------------------------*/
function res($status, $msg) {
    echo json_encode([
        'status' => $status,
        'msg'    => $msg,
        'time'   => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/*-----------------------------------------------
  HTTPS 접속 여부 확인
-----------------------------------------------*/
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    res(2, 'HTTPS를 사용하여 접속하여야 합니다.');
}

/*-----------------------------------------------
  POST 요청 여부 확인
-----------------------------------------------*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    res(2, 'POST 요청만 허용됩니다.');
}

/*-----------------------------------------------
  환경설정 파일(.env) 존재 여부 확인 및 로드
-----------------------------------------------*/
$envPath = '../.env';
if (!file_exists($envPath)) {
    res(2, '환경설정 파일이 존재하지 않습니다.');
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

/*-----------------------------------------------
  데이터베이스 연결 정보 및 설치 비밀번호 확인
-----------------------------------------------*/
$host       = getenv('DB_HOST');
$dbUser     = getenv('DB_USER');
$dbName     = getenv('DB_NAME');
$dbPass     = getenv('DB_PASS');
$setupClick = getenv('SETUP_CLICK');

if (!$host || !$dbUser || !$dbName || !$dbPass || !$setupClick) {
    res(2, '데이터베이스 설정이 올바르지 않습니다.');
}

/*-----------------------------------------------
  데이터베이스 연결 (PDO)
-----------------------------------------------*/
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    res(2, '데이터베이스 연결에 실패하였습니다.');
}

/*-----------------------------------------------
  JSON 입력 데이터 처리 및 필수값 확인
-----------------------------------------------*/
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['password'])) {
    res(2, '비밀번호 값이 제공되지 않았습니다.');
}
if ($data['password'] !== $setupClick) {
    res(2, '비밀번호가 일치 하지 않습니다.');
}

/*-----------------------------------------------
  테이블 존재 여부 및 설치 요청 조건 확인
-----------------------------------------------*/
$stmt = $pdo->query("SHOW TABLES LIKE 'click'");
$tableExists = $stmt->rowCount() > 0;

// 재설치 요청이지만 설치되지 않은 경우
if (!$tableExists && isset($data['reinstall']) && $data['reinstall'] === true) {
    res(2, '설치되지 않은 상태에서 재설치를 요청할 수 없습니다.');
}

// 이미 설치된 상태에서 설치를 요청한 경우
if ($tableExists && isset($data['install']) && $data['install'] === true) {
    res(2, '이미 설치된 상태에서 다시 설치할 수 없습니다.');
}

/*-----------------------------------------------
  재설치 요청인 경우 기존 테이블 삭제 처리
-----------------------------------------------*/
if ($tableExists && isset($data['reinstall']) && $data['reinstall'] === true) {
    try {
        $pdo->exec("DROP TABLE IF EXISTS `click`");
        $pdo->exec("DROP TABLE IF EXISTS `click-total`");
    } catch (PDOException $e) {
        res(2, '기존 테이블 삭제에 실패하였습니다.');
    }
}

/*-----------------------------------------------
  click 테이블 생성
-----------------------------------------------*/
try {
    $queryClick = "CREATE TABLE IF NOT EXISTS `click` (
        id VARCHAR(20) PRIMARY KEY,
        name VARCHAR(10) NOT NULL,
        block TINYINT(1) NOT NULL DEFAULT 0,
        count INT UNSIGNED NOT NULL DEFAULT 0,
        date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($queryClick);
} catch (PDOException $e) {
    res(2, 'click 테이블 생성에 실패하였습니다.');
}

/*-----------------------------------------------
  click-total 테이블 생성
-----------------------------------------------*/
try {
    $queryClickTotal = "CREATE TABLE IF NOT EXISTS `click_total` (
        id INT UNSIGNED NOT NULL PRIMARY KEY,
        count INT UNSIGNED NOT NULL DEFAULT 0,
        date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($queryClickTotal);

    // 정상적으로 모든 작업이 완료된 경우 성공 응답을 전송합니다.
    res(1, '성공적으로 처리되었습니다.');
} catch (PDOException $e) {
    res(2, 'click-total 테이블 생성에 실패하였습니다.');
}

?>