<?php
date_default_timezone_set('Asia/Seoul');
header('Content-Type: application/json');

// 응답 함수
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

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(2, '잘못된 요청 방식입니다.');
}

// 쿠키 삭제 (만료 시간 과거로 설정)
setcookie('chatUser', '', time() - 3600, '/', '', false, false);

// 응답 전송
jsonResponse(1, '로그아웃되었습니다.', ['user' => null]);