<?php
date_default_timezone_set('Asia/Seoul');
header('Content-Type: application/json');

// 공통 응답 함수
function jsonResponse($status, $msg, $result = null) {
    $res = [
        'status' => $status,
        'msg' => $msg,
        'time' => date('Y-m-d H:i:s')
    ];
    if (!is_null($result)) {
        $res['result'] = $result;
    }
    echo json_encode($res);
    exit;
}

// JSON 파싱
$data = json_decode(file_get_contents('php://input'), true);
$user = trim($data['user'] ?? '');

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(2, '잘못된 요청 방식입니다.');
}

if ($user === '') {
    jsonResponse(2, '닉네임을 입력해주세요.');
}

// XSS 방지
$safeUser = htmlspecialchars($user, ENT_QUOTES | ENT_HTML5);

// 쿠키 재설정 (30일 유지)
setcookie('chatUser', $safeUser, time() + 60 * 60 * 24 * 30, '/', '', false, true);

// 성공 응답
jsonResponse(1, '닉네임이 변경되었습니다.', ['user' => $safeUser]);