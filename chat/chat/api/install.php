<?php
date_default_timezone_set('Asia/Seoul');
header('Content-Type: application/json');

define('DB_PATH', __DIR__ . '/../data/chat.db');
define('DATA_DIR', dirname(DB_PATH));
define('ENV_PATH', __DIR__ . '/../.env');

// 공통 응답 함수
function jsonResponse($status, $msg) {
    echo json_encode([
        'status' => $status,
        'msg' => $msg,
        'time' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// .env 파서
function parseEnv($file) {
    $vars = [];
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (!str_contains($line, '=')) continue;
        [$name, $value] = explode('=', $line, 2);
        $vars[trim($name)] = trim($value);
    }
    return $vars;
}

// 요청 파싱
$data = json_decode(file_get_contents("php://input"), true);
$requestKey = trim($data['key'] ?? '');

// post 요청 검사
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(2, '잘못된 요청 방식입니다.' . $_SERVER['REQUEST_METHOD']);
}

if ($requestKey === '') {
    jsonResponse(2, '인증 키를 입력해주세요.');
}

$env = parseEnv(ENV_PATH);
$realKey = $env['KEY'] ?? '';

if ($requestKey !== $realKey) {
    jsonResponse(2, '인증에 실패했습니다.');
}

// 디렉토리 없으면 생성
if (!is_dir(DATA_DIR)) {
    if (!mkdir(DATA_DIR, 0777, true)) {
        jsonResponse(2, '데이터 디렉토리 생성에 실패했습니다.');
    }
}

try {
    $pdo = new PDO("sqlite:" . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chat (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user TEXT NOT NULL,
            msg TEXT NOT NULL,
            ip TEXT,
            time TEXT DEFAULT (datetime('now', 'localtime'))
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS block (
            ip TEXT PRIMARY KEY,
            time TEXT DEFAULT (datetime('now', 'localtime'))
        );
    ");

    jsonResponse(1, '설치가 완료되었습니다.');
} catch (Exception $e) {
    jsonResponse(2, '데이터베이스 연결에 문제가 발생했습니다.');
}