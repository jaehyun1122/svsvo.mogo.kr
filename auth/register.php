<?php

header('Content-Type: application/json; charset=UTF-8');

// 타임존 설정 (대한민국 서울)
date_default_timezone_set('Asia/Seoul');

// 사용자의 클라이언트 ip 가져오기
function getIp() {
    $ipaddress = $_SERVER['HTTP_CLIENT_IP'] ??
                 $_SERVER['HTTP_X_FORWARDED_FOR'] ??
                 $_SERVER['HTTP_X_FORWARDED'] ??
                 $_SERVER['HTTP_FORWARDED_FOR'] ??
                 $_SERVER['HTTP_FORWARDED'] ??
                 $_SERVER['REMOTE_ADDR'] ??
                 'UNKNOWN';

    return $ipaddress;
}

// 응답 함수
function res($status, $msg) {
    echo json_encode([
        'status' => $status,
        'msg' => $msg,
        'time' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// HTTPS 확인
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    res(2, 'HTTPS를 사용해야 합니다.');
}

// POST 요청 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    res(2, 'POST 요청만 허용됩니다.');
}

// .env 파일 로드
$envPath = '../.env';
if (!file_exists($envPath)) {
    res(2, '환경설정 파일이 없습니다.');
}

// 환경 변수 로드
function loadEnv($path) {
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}

// 환경 변수 로드
loadEnv($envPath);

// 환경 변수 가져오기
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$dbname = getenv('DB_NAME');
$pass = getenv('DB_PASS');

if (!$host || !$user || !$dbname || !$pass) {
    res(2, '데이터베이스 설정이 잘못되었습니다.');
}

// DB 연결
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    res(2, '데이터베이스 연결에 실패하였습니다.');
}

// JSON 입력 받기
$data = json_decode(file_get_contents('php://input'), true);

// 필수값 확인
$req = ['id', 'email', 'pw', 'name', 'terms'];
foreach ($req as $r) {
    if (!isset($data[$r]) || trim($data[$r]) === '') {
        res(2, ucfirst($r) . ' 값이 없습니다.');
    }
}

// 값 정리
$id = trim($data['id']);
$email = trim($data['email']);
$pw = trim($data['pw']);
$name = trim($data['name']);
$terms = $data['terms'];
$ip = getIp();
$date = date('Y-m-d H:i:s');

// 아이디 유효성 검사 (영문, 숫자만 허용, 4~20자)
if (!preg_match('/^[a-zA-Z0-9]{4,20}$/', $id)) {
    res(2, '아이디는 영문과 숫자만 포함하며 4~20자 이내여야 합니다.');
}

// 비밀번호 길이 검사 (4~20자)
if (strlen($pw) < 4 || strlen($pw) > 20) {
    res(2, '비밀번호는 4~20자 이내여야 합니다.');
}

// 패스워드 해시 (`password_hash()` 적용)
$hashPw = password_hash($pw, PASSWORD_DEFAULT);

// 이메일 형식 확인
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    res(2, '이메일 형식이 올바르지 않습니다.');
}

// 이메일 길이 체크 (UTF-8 기준 50자 미만)
if (mb_strlen($email, 'UTF-8') > 50) {
    res(2, '이메일은 50자 미만이어야 합니다.');
}

// 약관 동의 확인
if ($terms !== true) {
    res(2, '약관에 동의해야 합니다.');
}

// 이름 길이 체크 (UTF-8 기준 10글자 미만)
if (mb_strlen($name, 'UTF-8') > 10) {
    res(2, '이름은 10글자 미만이어야 합니다.');
}

try {
    // 트랜잭션 시작
    $pdo->beginTransaction();

    // 아이디 중복 체크 (트랜잭션 내에서 실행)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM account WHERE id = :id FOR UPDATE");
    $stmt->execute([':id' => $id]);
    if ($stmt->fetchColumn() > 0) {
        $pdo->rollBack();
        res(2, '이미 사용 중인 아이디입니다.');
    }

    // 이메일 중복 체크
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM account WHERE email = :email FOR UPDATE");
    $stmt->execute([':email' => $email]);
    if ($stmt->fetchColumn() > 0) {
        $pdo->rollBack();
        res(2, '이미 사용 중인 이메일입니다.');
    }

    // 닉네임 중복 체크
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM account WHERE name = :name FOR UPDATE");
    $stmt->execute([':name' => $name]);
    if ($stmt->fetchColumn() > 0) {
        $pdo->rollBack();
        res(2, '이미 사용 중인 닉네임 입니다.');
    }

    // 회원 정보 저장
    $stmt = $pdo->prepare("INSERT INTO account (id, email, name, password, ip, date, token, block) 
                           VALUES (:id, :email, :name, :pw, :ip, :date, NULL, 0)");
    $stmt->execute([
        ':id' => $id,
        ':email' => $email,
        ':name' => $name,
        ':pw' => $hashPw,
        ':ip' => $ip,
        ':date' => $date
    ]);

    // 트랜잭션 커밋
    $pdo->commit();

    res(1, '회원가입이 완료되었습니다.');
} catch (PDOException $e) {
    // 오류 발생 시 롤백
    $pdo->rollBack();
    res(2, '회원가입 중 오류 발생하였습니다.');
}

?>