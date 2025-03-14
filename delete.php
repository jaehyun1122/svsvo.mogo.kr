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
  <title>회원 탈퇴</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
    }
    
    .delete-header {
      text-align: center;
      margin-bottom: 40px;
    }
    
    .delete-header h1 {
      font-size: 2.5rem;
      margin-bottom: 10px;
      background: linear-gradient(90deg, var(--primary-color), #9f86ff);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }
    
    .delete-header p {
      font-size: 1.1rem;
      opacity: 0.8;
    }
    
    .form-group {
      margin-bottom: 24px;
      position: relative;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
    }
    
    .input-wrapper {
      position: relative;
    }
    
    .input-icon {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--primary-color);
    }
    
    .input-field {
      width: 100%;
      padding: 14px 14px 14px 40px;
      background-color: var(--input-bg);
      border: 2px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      color: var(--text-color);
      font-size: 16px;
      transition: all 0.3s ease;
    }
    
    .input-field:focus {
      border-color: var(--primary-color);
      outline: none;
      box-shadow: 0 0 0 3px rgba(106, 90, 205, 0.25);
    }
    
    .input-field::placeholder {
      color: rgba(224, 224, 224, 0.5);
    }
    
    .terms-container {
      display: flex;
      align-items: flex-start;
      font-size: 15px;
      margin: 30px 0;
      line-height: 1.4;
    }
    
    .custom-checkbox {
      appearance: none;
      -webkit-appearance: none;
      width: 20px;
      height: 20px;
      margin-right: 12px;
      margin-top: 2px;
      border: 2px solid rgba(255, 255, 255, 0.2);
      border-radius: 6px;
      background-color: var(--input-bg);
      cursor: pointer;
      position: relative;
      flex-shrink: 0;
    }
    
    .custom-checkbox:checked {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
    }
    
    .custom-checkbox:checked::after {
      content: '\f00c';
      font-family: 'Font Awesome 6 Free';
      font-weight: 900;
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      color: white;
      font-size: 12px;
    }
    
    .terms-text a {
      color: var(--link-color);
      text-decoration: none;
      font-weight: 500;
      transition: all 0.2s;
    }
    
    .terms-text a:hover {
      text-decoration: underline;
      color: #b39dff;
    }
    
    .delete-button {
      width: 100%;
      padding: 16px;
      background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
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
    
    .delete-button:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(106, 90, 205, 0.3);
    }
    
    .delete-button:active {
      transform: translateY(0);
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
    
    .success-container {
      text-align: center;
      display: none;
      animation: fadeIn 0.5s ease;
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 100%;
      max-width: 480px;
      z-index: 1000;
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
    
    .success-container p {
      font-size: 1.1rem;
      opacity: 0.8;
      margin-bottom: 20px;
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
    
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    
    @media (max-width: 480px) {
      .delete-header h1 {
        font-size: 2rem;
      }
      .container {
        padding: 0;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div id="deleteForm">
      <div class="delete-header">
        <h1>회원 탈퇴</h1>
        <p>정말로 탈퇴하시겠습니까?</p>
      </div>
      <form id="deleteAccountForm">
        <div class="form-group">
          <label for="password">비밀번호</label>
          <div class="input-wrapper">
            <i class="fas fa-lock input-icon"></i>
            <input type="password" id="password" class="input-field" placeholder="비밀번호를 입력하세요">
          </div>
        </div>
        <div class="terms-container">
          <input type="checkbox" id="terms" class="custom-checkbox">
          <div class="terms-text">
            <label for="terms">
              <a href="/terms" target="_blank">이용약관</a> 및 
              <a href="/privacy" target="_blank">개인정보 처리방침</a>에 동의합니다
            </label>
          </div>
        </div>
        <button type="submit" class="delete-button">
          <i class="fas fa-trash-alt"></i>
          탈퇴하기
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
      <h2>회원 탈퇴 완료!</h2>
      <p id="countdownText">5초 후 로그인 화면으로 이동합니다...</p>
      <p>혹은 <a href="/login">여기를 클릭</a>하여 바로 이동할 수 있습니다.</p>
    </div>
  </div>
  
  <div class="toast-container" id="toastContainer"></div>
  
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const deleteAccountForm = document.getElementById('deleteAccountForm');
      const deleteForm = document.getElementById('deleteForm');
      const successPage = document.getElementById('successPage');
      const toastContainer = document.getElementById('toastContainer');
      const passwordInput = document.getElementById('password');
      
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
        toastContainer.appendChild(toast);
        
        // 자연스럽게 나타나도록 (0.5초 transition)
        setTimeout(() => {
          toast.style.opacity = '1';
          toast.style.transform = 'translateX(0)';
        }, 10);
        
        // 2초 후 자연스럽게 사라짐
        setTimeout(() => {
          toast.style.opacity = '0';
          toast.style.transform = 'translateX(100%)';
          setTimeout(() => toast.remove(), 500);
        }, 2000);
      }
      
      function startCountdown() {
        let countdown = 5;
        const countdownText = document.getElementById('countdownText');
        const interval = setInterval(() => {
          countdown--;
          countdownText.innerText = `${countdown}초 후 로그인 화면으로 이동합니다...`;
          if (countdown === 0) {
            clearInterval(interval);
            window.location.href = '/login';
          }
        }, 1000);
      }
      
      deleteAccountForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const password = passwordInput.value.trim();
        
        if (!password) {
          showToast('비밀번호를 입력해주세요', 'error');
          return;
        }
        if (!document.getElementById('terms').checked) {
          showToast('이용약관 및 개인정보 처리방침에 동의해주세요', 'error');
          return;
        }
        
        const data = {
          pw: password,
          terms: true
        };
        
        fetch('auth/delete', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
          if (result.status === 1) {
            showToast('회원 탈퇴가 완료되었습니다!', 'success');
            deleteForm.style.display = 'none';
            successPage.style.display = 'block';
            startCountdown();
          } else {
            showToast(result.msg || '회원 탈퇴에 실패했습니다', 'error');
          }
        })
        .catch(() => showToast('서버 연결 중 오류가 발생했습니다', 'error'));
      });
    });
  </script>
</body>
</html>
