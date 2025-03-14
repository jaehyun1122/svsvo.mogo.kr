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
    ]);
    exit;
}

res(2, 'The password recovery feature will be available soon. Please contact customer support.');

?>