<?php
/**
 * redefinir-senha.php — Redefinição de senha com checklist de requisitos
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../backend/bootstrap.php';

redirectIfAuthenticated('/dashboard.php');

$error = '';
$success = false;
$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));

if ($token === '') {
  $error = 'Token de redefinição inválido.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token !== '') {
  $password = (string) ($_POST['password'] ?? '');
  $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

  if ($password === '' || $passwordConfirm === '') {
    $error = 'Preencha todos os campos.';
  } elseif ($password !== $passwordConfirm) {
    $error = 'As senhas não coincidem.';
  } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/\d/', $password)) {
    $error = 'A senha deve ter ao menos 8 caracteres, uma letra maiúscula e um número.';
  } else {
    $tokenHash = hash('sha256', $token);
    $adminId = $adminRepository->consumeResetToken($tokenHash);

    if ($adminId === null) {
      $error = 'Token inválido ou expirado. Solicite um novo link de recuperação.';
    } else {
      $adminRepository->updatePassword($adminId, password_hash($password, PASSWORD_DEFAULT));
      $success = true;
    }
  }
}

$pageTitle = 'ImobiHub — Redefinir Senha';
require_once __DIR__ . '/includes/auth_header.php';
?>

<div class="auth-card auth-card-centered" id="form-card" <?= $success ? 'hidden' : '' ?>>
  <div class="auth-icon" aria-hidden="true">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
      <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
    </svg>
  </div>
  <h1 class="auth-heading">Redefinir senha</h1>
  <p class="auth-sub">Crie uma nova senha segura para sua conta de administrador.</p>

  <?php if ($error !== ''): ?>
    <div class="auth-alert auth-alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form class="auth-form" id="reset-form" method="post" novalidate>
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
    <label>
      Nova senha
      <div class="password-wrap">
        <input type="password" name="password" id="new-password"
               placeholder="••••••••" autocomplete="new-password" required>
        <button type="button" class="password-toggle"
                onclick="togglePassword('new-password', this)"
                aria-label="Mostrar senha">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
        </button>
      </div>
      <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
    </label>

    <label>
      Confirmar nova senha
      <div class="password-wrap">
        <input type="password" name="password_confirm" id="confirm-password"
           placeholder="••••••••" autocomplete="new-password" required>
        <button type="button" class="password-toggle"
                onclick="togglePassword('confirm-password', this)"
                aria-label="Mostrar senha">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
        </button>
      </div>
    </label>

    <ul class="password-requirements" id="req-list">
      <li id="req-length">M&iacute;nimo de 8 caracteres</li>
      <li id="req-upper">Letra mai&uacute;scula</li>
      <li id="req-number">N&uacute;mero</li>
    </ul>

    <button class="btn btn-primary btn-full" type="submit">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
      </svg>
      Redefinir senha
    </button>
  </form>
</div>

<div class="auth-card auth-card-centered" id="success-card" <?= $success ? '' : 'hidden' ?>>
  <div style="text-align:center;padding:8px 0">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2" style="margin-bottom:12px">
      <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
      <polyline points="22 4 12 14.01 9 11.01"/>
    </svg>
    <h2 class="auth-heading" style="margin-bottom:8px">Senha redefinida!</h2>
    <p class="auth-sub" style="margin-bottom:24px">
      Sua senha foi atualizada com sucesso. Fa&ccedil;a login com a nova senha.
    </p>
    <a href="/login.php" class="btn btn-primary btn-full">Ir para o login</a>
  </div>
</div>

<p class="auth-back-link">
  <a href="/">&larr; Voltar ao cat&aacute;logo p&uacute;blico</a>
</p>

<style>
  .password-requirements { list-style: none; padding: 0; margin: 4px 0 0; display: flex; flex-direction: column; gap: 4px; }
  .password-requirements li { font-size: 0.78rem; color: var(--muted); padding-left: 18px; position: relative; }
  .password-requirements li::before { content: "✗"; position: absolute; left: 0; color: var(--red); }
  .password-requirements li.ok::before { content: "✓"; color: var(--green); }
  .password-requirements li.ok { color: var(--green-text); }
</style>

<script>
  function togglePassword(id, btn) {
    const input = document.getElementById(id);
    if (input.type === 'password') {
      input.type = 'text';
      btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
    } else {
      input.type = 'password';
      btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    }
  }

  const pwInput   = document.getElementById('new-password');
  const fill      = document.getElementById('strength-fill');
  const reqLength = document.getElementById('req-length');
  const reqUpper  = document.getElementById('req-upper');
  const reqNumber = document.getElementById('req-number');

  pwInput.addEventListener('input', function () {
    const v = this.value;
    let score = 0;
    const hasLen = v.length >= 8; if (hasLen) score++; reqLength.classList.toggle('ok', hasLen);
    const hasUp  = /[A-Z]/.test(v); if (hasUp)  score++; reqUpper.classList.toggle('ok',  hasUp);
    const hasNum = /[0-9]/.test(v); if (hasNum) score++; reqNumber.classList.toggle('ok', hasNum);
    if (/[^A-Za-z0-9]/.test(v)) score++;
    fill.style.width      = (score / 4 * 100) + '%';
    fill.style.background = ['#EF4444', '#F59E0B', '#22C55E', '#16A34A'][Math.max(0, score - 1)] || '#EF4444';
  });

  document.getElementById('reset-form').addEventListener('submit', function (e) {
    const pw = document.getElementById('new-password').value;
    const cf = document.getElementById('confirm-password').value;
    if (pw !== cf) {
      alert('As senhas não coincidem.');
      e.preventDefault();
      return;
    }
    if (pw.length < 8 || !/[A-Z]/.test(pw) || !/[0-9]/.test(pw)) {
      alert('A senha deve ter ao menos 8 caracteres, uma letra maiúscula e um número.');
      e.preventDefault();
    }
  });
</script>

</body>
</html>
