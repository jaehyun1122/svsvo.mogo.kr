<?php
date_default_timezone_set('Asia/Seoul');
header('Content-Type: application/json');

define('DB_PATH', __DIR__ . '/../data/chat.db');
define('ENV_PATH', __DIR__ . '/../.env');

// 응답 함수
function jsonResponse($status, $msg, $result = null) {
    $res = [
        'status' => $status,
        'msg' => $msg,
        'time' => date('Y-m-d H:i:s')
    ];
    if ($result !== null) {
        $res['result'] = $result;
    }
    echo json_encode($res);
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
$key = $data['key'] ?? '';
$ips = $data['ip'] ?? [];

// post 요청 검사
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(2, '잘못된 요청 방식입니다.');
}

if (is_string($ips)) {
    $ips = [$ips];
}

$ips = array_filter(array_map('trim', $ips));

// 인증 실패
$env = parseEnv(ENV_PATH);
$realKey = $env['KEY'] ?? '';
if ($key !== $realKey) {
    jsonResponse(2, '인증에 실패했습니다.');
}

// IP 없음 예외
if (empty($ips)) {
    jsonResponse(2, '차단할 IP가 없습니다.');
}

// 차단 처리
try {
    $pdo = new PDO("sqlite:" . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("INSERT OR IGNORE INTO block (ip, time) VALUES (:ip, datetime('now', 'localtime'))");
    foreach ($ips as $ip) {
        $stmt->execute([':ip' => $ip]);
    }

    jsonResponse(1, '다음 IP를 차단했습니다.', array_values($ips));
} catch (Exception $e) {
    jsonResponse(2, '데이터베이스 처리 중 문제가 발생했습니다.');
}