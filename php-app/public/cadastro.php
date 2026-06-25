<?php
/**
 * cadastro.php — Criação de conta de administrador
 */
require_once __DIR__ . '/includes/auth.php';

$bootstrapError = null;
try {
  require_once __DIR__ . '/../backend/bootstrap.php';
} catch (Throwable $exception) {
  $bootstrapError = $exception->getMessage();
}

redirectIfAuthenticated('/dashboard.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($bootstrapError !== null) {
    $error = 'O servidor PHP nao conseguiu iniciar o banco de dados. Verifique as extensoes SQLite no php-fpm.';
  } else {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if ($name === '' || $email === '' || $password === '' || $passwordConfirm === '') {
      $error = 'Preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = 'Informe um e-mail válido.';
    } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/\d/', $password)) {
      $error = 'A senha deve ter ao menos 8 caracteres, uma letra maiúscula e um número.';
    } elseif ($password !== $passwordConfirm) {
      $error = 'As senhas não coincidem.';
    } elseif ($adminRepository->findByEmail($email) !== null) {
      $error = 'Já existe uma conta para este e-mail.';
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $adminRepository->create($name, $email, $hash);

      header('Location: /login.php?success=' . urlencode('Conta criada com sucesso. Faça login para continuar.'));
      exit;
    }
  }
}

$pageTitle = 'ImobiHub — Criar Conta';
require_once __DIR__ . '/includes/auth_header.php';
?>

<div class="auth-card auth-card-centered">
  <div class="auth-icon" aria-hidden="true">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
      <circle cx="9" cy="7" r="4"/>
      <line x1="19" y1="8" x2="19" y2="14"/>
      <line x1="22" y1="11" x2="16" y2="11"/>
    </svg>
  </div>
  <h1 class="auth-heading">Criar conta de administrador</h1>
  <p class="auth-sub">Preencha os dados para criar sua conta de gerenciamento.</p>

  <?php if ($bootstrapError !== null): ?>
    <div class="auth-alert auth-alert-error">Banco de dados indisponivel no servidor. PHP-FPM precisa carregar sqlite/pdo_sqlite.</div>
  <?php endif; ?>

  <?php if ($error !== ''): ?>
    <div class="auth-alert auth-alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form class="auth-form" id="register-form" method="post" novalidate>
    <label>
      Nome completo
      <input type="text" name="name" placeholder="Seu nome" autocomplete="name" required
             value="<?= htmlspecialchars((string) ($_POST['name'] ?? '')) ?>">
    </label>

    <label>
      E-mail
      <input type="email" name="email" placeholder="seu@email.com" autocomplete="email" required
             value="<?= htmlspecialchars((string) ($_POST['email'] ?? '')) ?>">
    </label>

    <label>
      Senha
      <div class="password-wrap">
        <input type="password" name="password" id="reg-password"
               placeholder="••••••••" autocomplete="new-password" required>
        <button type="button" class="password-toggle"
                onclick="togglePassword('reg-password', this)"
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
      Confirmar senha
      <div class="password-wrap">
        <input type="password" name="password_confirm" id="reg-confirm"
               placeholder="••••••••" autocomplete="new-password" required>
        <button type="button" class="password-toggle"
                onclick="togglePassword('reg-confirm', this)"
                aria-label="Mostrar senha">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
        </button>
      </div>
    </label>

    <button class="btn btn-primary btn-full" type="submit">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <line x1="19" y1="8" x2="19" y2="14"/>
        <line x1="22" y1="11" x2="16" y2="11"/>
      </svg>
      Criar conta
    </button>
  </form>

  <div class="auth-link-row">
    J&aacute; tem conta? <a href="/login.php">Fazer login</a>
  </div>
</div>

<p class="auth-back-link">
  <a href="/">&larr; Voltar ao cat&aacute;logo p&uacute;blico</a>
</p>

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

  const pwInput = document.getElementById('reg-password');
  const fill    = document.getElementById('strength-fill');

  pwInput.addEventListener('input', function () {
    const v = this.value;
    let score = 0;
    if (v.length >= 8)          score++;
    if (/[A-Z]/.test(v))        score++;
    if (/[0-9]/.test(v))        score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    fill.style.width      = (score / 4 * 100) + '%';
    fill.style.background = ['#EF4444', '#F59E0B', '#22C55E', '#16A34A'][Math.max(0, score - 1)] || '#EF4444';
  });

  document.getElementById('register-form').addEventListener('submit', function (e) {
    const pw = document.getElementById('reg-password').value;
    const cf = document.getElementById('reg-confirm').value;
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
