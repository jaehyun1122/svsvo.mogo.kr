<?php
date_default_timezone_set('Asia/Seoul');
header('Content-Type: application/json');

define('DB_PATH', __DIR__ . '/../data/chat.db');

// 공통 응답
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

// 요청 데이터 파싱
$data = json_decode(file_get_contents("php://input"), true);
$lastId = isset($data['last_id']) ? (int)$data['last_id'] : null;

// post 요청 검사
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(2, '잘못된 요청 방식입니다.');
}

try {
    $pdo = new PDO("sqlite:" . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($lastId) {
        // 이후 메시지 50개 (오름차순)
        $stmt = $pdo->prepare("SELECT id, user, msg, time FROM chat WHERE id > :id ORDER BY id ASC LIMIT 50");
        $stmt->execute([':id' => $lastId]);
    } else {
        // 최근 메시지 50개 (내림차순 → 오름차순 정렬)
        $stmt = $pdo->query("SELECT id, user, msg, time FROM chat ORDER BY id DESC LIMIT 50");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $rows = array_reverse($rows); // 오래된 순으로 보기 위해 뒤집음
    }

    if (!isset($rows)) {
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $result = [];
    foreach ($rows as $row) {
        $result[$row['id']] = [
            'user' => $row['user'],
            'msg' => $row['msg'],
            'time' => date('Y-m-d H:i:s', strtotime($row['time'] ?? 'now'))
        ];
    }

    jsonResponse(1, '채팅 목록을 불러왔습니다.', $result);
} catch (Exception $e) {
    jsonResponse(2, '데이터베이스 처리 중 문제가 발생했습니다.');
}