<?php

if (!$_COOKIE["account"]) {
  header("location: /login");
  exit;
}

?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
  <title>로그아웃</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --bg-color: #121212;
      --text-color: #e0e0e0;
      --primary-color: #6a5acd;
      --secondary-color: #4a3cb7;
      --error-color: #ff5252;
      --success-color: #4caf50;
      --input-bg: #2d2d2d;
      --link-color: #9f86ff;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background-color: var(--bg-color);
      color: var(--text-color);
      min-height: 100vh;
      padding: 40px 20px;
      line-height: 1.6;
    }

    .container {
      max-width: 480px;
      margin: 0 auto;
      position: relative;
      z-index: 1;
    }

    .logout-header {
      text-align: center;
      margin-bottom: 40px;
    }

    .logout-header h1 {
      font-size: 2.5rem;
      margin-bottom: 10px;
      background: linear-gradient(90deg, var(--primary-color), #9f86ff);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }

    .logout-header p {
      font-size: 1.1rem;
      opacity: 0.8;
    }

    .checkbox-container {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 14px;
      margin-bottom: 20px;
      background-color: var(--input-bg);
      padding: 10px;
      border-radius: 8px;
    }

    .checkbox-container input[type="checkbox"] {
      width: 18px;
      height: 18px;
      accent-color: var(--primary-color);
    }

    .logout-button {
      width: 100%;
      padding: 16px;
      background: linear-gradient(to right, var(--primary-color), #9f86ff);
      color: white;
      border: none;
      border-radius: 12px;
      font-size: 18px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
    }

    .logout-button i {
      font-size: 20px;
    }

    .logout-button:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(106, 90, 205, 0.3);
    }

    .logout-button:active {
      transform: translateY(0);
    }

    .success-container {
      text-align: center;
      display: none;
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 1000;
      width: 100%;
      max-width: 480px;
      padding: 20px;
      border-radius: 10px;
      color: white;
      background: transparent;  /* 배경을 투명으로 변경 */
    }

    .success-icon {
      font-size: 80px;
      color: var(--success-color);
      margin-bottom: 20px;
      display: inline-flex;
      justify-content: center;
      align-items: center;
      width: 120px;
      height: 120px;
      background-color: rgba(76, 175, 80, 0.1);
      border-radius: 50%;
    }

    .success-container h2 {
      font-size: 2rem;
      margin-bottom: 16px;
      color: var(--success-color);
    }

    .toast-container {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
    }

    .toast {
      background-color: rgba(30, 30, 30, 0.9);
      color: var(--text-color);
      padding: 12px 20px;
      border-radius: 8px;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      max-width: 300px;
      border: 2px solid transparent;
      position: relative;
      opacity: 0;
      transform: translateX(100%);
      transition: opacity 0.5s ease, transform 0.5s ease;
    }

    .toast.success {
      border-color: var(--success-color);
    }

    .toast.error {
      border-color: var(--error-color);
    }

    .toast.info {
      border-color: var(--primary-color);
    }

    .toast-icon {
      margin-right: 10px;
      font-size: 18px;
    }

    .additional-links {
      text-align: center;
      margin-top: 20px;
      font-size: 0.95rem;
      line-height: 1.5;
    }
    
    .additional-links p {
      margin: 10px 0;
    }
    
    .additional-links a {
      color: var(--link-color);
      text-decoration: none;
      transition: color 0.2s ease;
    }
    
    .additional-links a:hover {
      text-decoration: underline;
      color: #b39dff;
    }
    
    .navigation-buttons {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin-top: 20px;
    }
    
    .nav-btn {
      color: var(--link-color);
      text-decoration: none;
      font-size: 1rem;
      transition: color 0.2s ease;
    }
    
    .nav-btn:hover {
      color: #b39dff;
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <div class="container">
    <div class="logout-header">
      <h1><i class="fas fa-sign-out-alt"></i> 로그아웃</h1>
      <p><i class="fas fa-exclamation-circle"></i> 로그아웃을 하시려면 체크박스를 체크해주세요</p>
    </div>

    <form id="logoutForm">
      <div class="checkbox-container">
        <input type="checkbox" id="terms">
        <label for="terms">정말로 로그아웃을 하시겠습니까?</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="logoutAll">
        <label for="logoutAll">전체 모든기기에서 로그아웃</label>
      </div>

      <button type="button" class="logout-button" onclick="logout()">
        <i class="fas fa-power-off"></i> 로그아웃
      </button>
    </form>

    <div class="additional-links">
      <p>
        <i class="fas fa-key"></i>
        비밀번호를 잊으셨나요? <a href="/password-lose">재설정하기</a>
      </p>
      <p>
        <i class="fas fa-sign-in-alt"></i>
        이미 회원이신가요? <a href="/login">로그인</a>
      </p>
    </div>
    <div class="navigation-buttons">
        <a href="javascript:history.back()" class="nav-btn"><i class="fas fa-arrow-left"></i> 뒤로가기</a>
        <a href="/" class="nav-btn"><i class="fas fa-home"></i> 홈으로가기</a>
    </div>
  </div>

  <div class="success-container" id="successPage">
    <div class="success-icon">
      <i class="fas fa-check"></i>
    </div>
    <h2>로그아웃 성공!</h2>
    <p id="countdownText">5초 후 로그인 화면으로 이동합니다...</p>
    <p>혹은 <a href="/login">여기를 클릭</a>하여 바로 이동할 수 있습니다.</p>
  </div>

  <div class="toast-container" id="toastContainer"></div>

  <script>
    function showToast(message, type = 'info') {
      const toast = document.createElement('div');
      toast.className = `toast ${type}`;
      let icon = '';
      let color = '';
      if (type === 'success') {
        icon = '<i class="fas fa-check-circle toast-icon"></i>';
        color = 'var(--success-color)';
      } else if (type === 'error') {
        icon = '<i class="fas fa-exclamation-circle toast-icon"></i>';
        color = 'var(--error-color)';
      } else {
        icon = '<i class="fas fa-info-circle toast-icon"></i>';
        color = 'var(--primary-color)';
      }
      toast.innerHTML = `${icon}${message}`;
      toast.style.borderColor = color;
      document.getElementById('toastContainer').appendChild(toast);
      
      setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
      }, 10);
      
      setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 500);
      }, 2000);
    }

    async function logout() {
      const terms = document.getElementById("terms").checked;
      const logoutAll = document.getElementById("logoutAll").checked;
      const successPage = document.getElementById("successPage");
      const logoutForm = document.getElementById("logoutForm");
      const logoutHeader = document.querySelector(".logout-header");

      if (!terms) {
        showToast('로그아웃 확인 체크박스는 필수입니다.', 'error');
        return;
      }

      try {
        const res = await fetch("auth/logout", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ terms: true, all: logoutAll })
        });

        const data = await res.json();

        if (data.status === 1) {
          showToast('로그아웃 성공!', 'success');
          
          logoutForm.style.display = 'none';
          logoutHeader.style.display = 'none';
          document.querySelector('.additional-links').style.display = 'none';
          document.querySelector('.navigation-buttons').style.display = 'none';
          successPage.style.display = 'block';

          
          startCountdown();
        } else {
          showToast(data.msg || '로그아웃에 실패하였습니다.', 'error');
        }
      } catch (e) {
        showToast('서버 연결에 실패하였습니다.', 'error');
      }
    }

    function startCountdown() {
      let countdown = 5;
      const countdownText = document.getElementById("countdownText");
      const interval = setInterval(() => {
        countdown--;
        countdownText.innerText = `${countdown}초 후 로그인 화면으로 이동합니다...`;
        if (countdown === 0) {
          clearInterval(interval);
          window.location.href = '/login';
        }
      }, 1000);
    }
  </script>

</body>
</html>
