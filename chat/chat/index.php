<?php
date_default_timezone_set('Asia/Seoul');
define('DB_PATH', __DIR__ . '/data/chat.db');

// JSON 응답 함수
function jsonResponse($status, $msg) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'msg' => $msg,
        'time' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// IP 구하기
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

$ip = getClientIP();

// DB 연결 및 차단 확인
try {
    $pdo = new PDO("sqlite:" . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT 1 FROM block WHERE ip = :ip");
    $stmt->execute([':ip' => $ip]);

    if ($stmt->fetch()) {
        $blockFile = __DIR__ . '/html/block.html';
        if (file_exists($blockFile)) {
            include $blockFile;
        } else {
            jsonResponse(2, '차단 안내 페이지가 존재하지 않습니다.');
        }
        exit;
    }
} catch (Exception $e) {
    jsonResponse(2, '데이터베이스 처리 중 문제가 발생했습니다.');
}

// 사용자 쿠키 확인 및 페이지 표시
if (empty($_COOKIE['chatUser'])) {
    $loginFile = __DIR__ . '/html/login.html';
    if (file_exists($loginFile)) {
        include $loginFile;
    } else {
        jsonResponse(2, '로그인 페이지가 존재하지 않습니다.');
    }
} else {
    $chatFile = __DIR__ . '/html/chat.html';
    if (file_exists($chatFile)) {
        include $chatFile;
    } else {
        jsonResponse(2, '채팅 페이지가 존재하지 않습니다.');
    }
}