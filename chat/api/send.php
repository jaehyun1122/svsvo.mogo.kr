<?php
date_default_timezone_set('Asia/Seoul');
header('Content-Type: application/json');

define('DB_PATH', __DIR__ . '/../data/chat.db');

// 응답 템플릿
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

// 클라이언트 IP
function getClientIP() {
    $headers = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];

    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ipList = explode(',', $_SERVER[$header]);
            foreach ($ipList as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
    }

    return '0.0.0.0'; // fallback
}

// JSON 요청 파싱
$data = json_decode(file_get_contents("php://input"), true);
$msg = trim($data['msg'] ?? '');
$user = trim($_COOKIE['chatUser'] ?? '');
$ip = getClientIP();

// 검증
if ($user === '') {
    jsonResponse(2, '닉네임이 설정되어 있지 않습니다.');
}
if ($msg === '') {
    jsonResponse(2, '메시지를 입력해주세요.');
}
if (mb_strlen($msg) > 50) {
    jsonResponse(2, '메시지 내용이 너무 깁니다.');
}

try {
    $pdo = new PDO("sqlite:" . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 차단 확인
    $stmt = $pdo->prepare("SELECT 1 FROM block WHERE ip = :ip");
    $stmt->execute([':ip' => $ip]);
    if ($stmt->fetch()) {
        jsonResponse(2, '현재 사용자의 IP는 차단되어 있습니다.');
    }

    $time = date('Y-m-d H:i:s');

    // XSS 방지
    $safeUser = htmlspecialchars($user, ENT_QUOTES | ENT_HTML5);
    $safeMsg = htmlspecialchars($msg, ENT_QUOTES | ENT_HTML5);

    // 저장
    $stmt = $pdo->prepare("INSERT INTO chat (user, msg, ip, time) VALUES (:user, :msg, :ip, :time)");
    $stmt->execute([
        ':user' => $safeUser,
        ':msg' => $safeMsg,
        ':ip' => $ip,
        ':time' => $time
    ]);

    $id = $pdo->lastInsertId();

    jsonResponse(1, '메시지를 전송했습니다.', [
        $id => [
            'user' => $safeUser,
            'msg' => $safeMsg,
            'time' => $time
        ]
    ]);
} catch (Exception $e) {
    jsonResponse(2, '데이터베이스 처리 중 문제가 발생했습니다.');
}