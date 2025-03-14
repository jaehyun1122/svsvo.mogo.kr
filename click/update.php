<?php

date_default_timezone_set('Asia/Seoul');
header('Content-Type: application/json; charset=UTF-8');

/*-----------------------------------------------
  JSON 응답 함수
-----------------------------------------------*/
function res($status, $msg, $result = null) {
    $response = [
        'status' => $status,
        'msg'    => $msg,
        'time'   => date('Y-m-d H:i:s')
    ];
    if ($result !== null) {
        $response['result'] = $result;
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

/*-----------------------------------------------
  환경설정 파일 로드 및 DB 연결
-----------------------------------------------*/
$envPath = '../.env';
if (!file_exists($envPath)) {
    res("2", "환경설정 파일이 존재하지 않습니다.");
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

$host   = getenv('DB_HOST');
$dbUser = getenv('DB_USER');
$dbName = getenv('DB_NAME');
$dbPass = getenv('DB_PASS');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    res("2", "데이터베이스 연결에 실패하였습니다.");
}

/*-----------------------------------------------
  차단 처리 함수
-----------------------------------------------*/
function blockUserAndExit() {
    global $pdo, $user;
    if (isset($pdo) && $pdo && isset($user['id'])) {
        try {
            $stmt = $pdo->prepare("UPDATE click SET block = 1 WHERE id = ?");
            $stmt->execute([$user['id']]);
        } catch (PDOException $e) {
            res("2", "서버 내부 오류가 발생했습니다. 잠시 후 다시 시도해 주시기 바랍니다.");
        }
    }
    res("2", "비정상적인 요청으로 계정이 정지되었습니다. 관리자에게 문의해 주시기 바랍니다.");
}

/*-----------------------------------------------
  보안 및 요청 검사
-----------------------------------------------*/
if (!isset($_COOKIE['account'])) {
    res("2", "로그인을 해주세요.");
}

$token = $_COOKIE['account'];
try {
    $stmt = $pdo->prepare("SELECT id, block FROM account WHERE token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    res("2", "데이터베이스 오류가 발생하였습니다.");
}
if (!$user) {
    res("2", "로그인을 해주세요.");
}
if ($user['block'] == 1) {
    res("2", "차단된 사용자입니다.");
}

// 1. HTTPS 요청 확인
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    if (isset($_COOKIE['account'])) {
        blockUserAndExit();
    } else {
        res("2", "로그인을 해주세요.");
    }
}

// 2. POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (isset($_COOKIE['account'])) {
        blockUserAndExit();
    } else {
        res("2", "로그인을 해주세요.");
    }
}

// 3. Content-Type이 JSON인지 확인
if (!isset($_SERVER['CONTENT_TYPE']) || stripos($_SERVER['CONTENT_TYPE'], 'application/json') === false) {
    blockUserAndExit();
}

// 4. JSON 파싱
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);
if (!$data) {
    blockUserAndExit();
}

// 5. CSRF 토큰 검사
if (!isset($data['csrf_token']) || !isset($_COOKIE['csrf_token']) || $data['csrf_token'] !== $_COOKIE['csrf_token']) {
    blockUserAndExit();
}

// 6. HTTP_REFERER 체크
if (!isset($_SERVER['HTTP_REFERER']) || parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) !== 'svsvo.mogo.kr') {
    blockUserAndExit();
}

// 7. count와 key_update 파라미터 확인
if (!isset($data['count']) || !isset($data['key_update'])) {
    blockUserAndExit();
}
$count      = $data['count'];
$key_update = $data['key_update'];

// 8. count 값 (숫자, 500 이하)
if (!is_numeric($count) || $count > 500 || $count <= 0) {
    blockUserAndExit();
}

// 9. key_update 값 (숫자, 100000 ~ 999999)
if (!is_numeric($key_update) || $key_update <= 100000 || $key_update >= 999999) {
    blockUserAndExit();
}

/*-----------------------------------------------
  click 테이블에서 사용자 정보 조회
-----------------------------------------------*/
try {
    $stmt = $pdo->prepare("SELECT id, count, block, date FROM click WHERE id = ?");
    $stmt->execute([$user['id']]);
    $clickUser = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    blockUserAndExit();
}

if (!$clickUser) {
    blockUserAndExit();
}
if ($clickUser['block'] == 1) {
    res("2", "차단된 사용자입니다.");
}

/*-----------------------------------------------
  요청 속도 제한
-----------------------------------------------*/
// 1초에 최대 n 개가 넘어가면 계정 차단
$maxCount = 30;

if (!empty($clickUser['date'])) {
    $lastTimestamp   = strtotime($clickUser['date']);
    $currentTime     = time();
    $elapsedSeconds  = $currentTime - $lastTimestamp;
    $maxAllowedCount = $elapsedSeconds * $maxCount;
    if ($maxAllowedCount <= 0) {
        $maxAllowedCount = $maxCount;
    }
    if ($count > $maxAllowedCount) {
        blockUserAndExit();
    }
}

/*-----------------------------------------------
  DB 업데이트 처리
-----------------------------------------------*/
// 1) PHP에서 만든 서울 시간(문자열) 준비
$currentDateSeoul = date("Y-m-d H:i:s");

try {
    // click 테이블 업데이트
    $stmt = $pdo->prepare("UPDATE click 
                           SET count = count + ?, date = ? 
                           WHERE id = ?");
    $stmt->execute([$count, $currentDateSeoul, $user['id']]);

    // click_total 테이블 업데이트 (id=1)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM click_total WHERE id = 1");
    $stmt->execute();
    $rowCount = $stmt->fetchColumn();

    if ($rowCount == 0) {
        // 첫 레코드 삽입
        $stmt = $pdo->prepare("INSERT INTO click_total (id, count, date) 
                               VALUES (1, ?, ?)");
        $stmt->execute([$count, $currentDateSeoul]);
    } else {
        // 기존 레코드 업데이트
        $stmt = $pdo->prepare("UPDATE click_total 
                               SET count = count + ?, date = ? 
                               WHERE id = 1");
        $stmt->execute([$count, $currentDateSeoul]);
    }

    // 업데이트 후 최신 데이터 조회
    $stmt = $pdo->prepare("SELECT count, date 
                           FROM click 
                           WHERE id = ?");
    $stmt->execute([$user['id']]);
    $updatedClickUser = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT count, date 
                           FROM click_total 
                           WHERE id = 1");
    $stmt->execute();
    $updatedClickTotal = $stmt->fetch(PDO::FETCH_ASSOC);

    // 응답에 포함할 결과
    $result = [
        'click_user_input' => $count,
        'click_user_total' => $updatedClickUser['count'],
        'click_user_date'  => $updatedClickUser['date'],
        'click_total'      => $updatedClickTotal['count'],
        'click_total_date' => $updatedClickTotal['date']
    ];

    res("1", "업데이트가 성공적으로 완료되었습니다.", $result);

} catch (PDOException $e) {
    res("2", "데이터베이스 업데이트에 실패하였습니다.");
}

?>