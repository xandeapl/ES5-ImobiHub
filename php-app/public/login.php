<?php
/**
 * login.php — Página de login do administrador
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../backend/bootstrap.php';

redirectIfAuthenticated('/dashboard.php');

$error = trim((string) ($_GET['error'] ?? ''));
$success = trim((string) ($_GET['success'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = strtolower(trim((string) ($_POST['email'] ?? '')));
  $password = (string) ($_POST['password'] ?? '');

  if ($email === '' || $password === '') {
    $error = 'Preencha e-mail e senha.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Informe um e-mail válido.';
  } else {
    $admin = $adminRepository->findByEmail($email);
    $isValid = is_array($admin) && password_verify($password, (string) $admin['password_hash']);

    if (!$isValid) {
      $error = 'E-mail ou senha inválidos.';
    } else {
      loginAdmin((int) $admin['id']);
      header('Location: /dashboard.php');
      exit;
    }
  }
}

$pageTitle = 'ImobiHub — Login';
require_once __DIR__ . '/includes/auth_header.php';
?>

<div class="auth-card">
  <h1 class="auth-heading">Login do Administrador</h1>
  <p class="auth-sub">Acesse o painel de gerenciamento do site.</p>

  <?php if ($error !== ''): ?>
    <div class="auth-alert auth-alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success !== ''): ?>
    <div class="auth-alert auth-alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form class="auth-form" id="login-form" method="post" novalidate>
    <label>
      E-mail
      <input type="email" name="email" placeholder="seu@email.com" autocomplete="email" required
             value="<?= htmlspecialchars((string) ($_POST['email'] ?? '')) ?>">
    </label>

    <label>
      Senha
      <div class="password-wrap">
        <input type="password" name="password" id="login-password"
               placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
               autocomplete="current-password" required>
        <button type="button" class="password-toggle"
                onclick="togglePassword('login-password', this)"
                aria-label="Mostrar senha">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
        </button>
      </div>
    </label>

    <div class="forgot-link">
      <a href="/recuperar-senha.php">Esqueceu sua senha?</a>
    </div>

    <button class="btn btn-dark btn-full" type="submit">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
        <polyline points="10 17 15 12 10 7"/>
        <line x1="15" y1="12" x2="3" y2="12"/>
      </svg>
      Entrar
    </button>
  </form>

  <div class="auth-link-row">
    N&atilde;o tem conta? <a href="/cadastro.php">Criar conta de administrador</a>
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

</script>

</body>
</html>
