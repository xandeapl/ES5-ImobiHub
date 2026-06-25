<?php
/**
 * recuperar-senha.php — Solicitação de recuperação de senha
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../backend/bootstrap.php';

redirectIfAuthenticated('/dashboard.php');

$error = '';
$showSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = strtolower(trim((string) ($_POST['email'] ?? '')));

  if ($email === '') {
    $error = 'Informe o e-mail cadastrado.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Informe um e-mail válido.';
  } else {
    $admin = $adminRepository->findByEmail($email);

    if (is_array($admin)) {
      $token = bin2hex(random_bytes(32));
      $tokenHash = hash('sha256', $token);
      $expiresAt = (new DateTimeImmutable())->modify('+30 minutes');
      $adminRepository->createResetToken((int) $admin['id'], $tokenHash, $expiresAt);

      $baseUrl = rtrim((string) ($config['app_url'] ?? 'http://localhost:8000'), '/');
      $resetUrl = $baseUrl . '/redefinir-senha.php?token=' . urlencode($token);

      $subject = 'Recuperação de senha - ImobiHub';
      $name = trim((string) ($admin['full_name'] ?? 'Administrador'));

      $textBody = "Olá {$name},\n\n";
      $textBody .= "Recebemos uma solicitação para redefinir sua senha no ImobiHub.\n";
      $textBody .= "Use o link abaixo para criar uma nova senha:\n\n{$resetUrl}\n\n";
      $textBody .= "Este link expira em 30 minutos.\n";
      $textBody .= "Se você não solicitou esta alteração, ignore este e-mail.\n";

      $htmlBody = '<p>Olá ' . htmlspecialchars($name) . ',</p>';
      $htmlBody .= '<p>Recebemos uma solicitação para redefinir sua senha no ImobiHub.</p>';
      $htmlBody .= '<p><a href="' . htmlspecialchars($resetUrl) . '">Clique aqui para redefinir sua senha</a></p>';
      $htmlBody .= '<p>Este link expira em 30 minutos.</p>';
      $htmlBody .= '<p>Se você não solicitou esta alteração, ignore este e-mail.</p>';

      try {
        $mailer->send((string) $admin['email'], $subject, $textBody, $htmlBody);
      } catch (Throwable) {
        $error = 'Não foi possível enviar o e-mail de recuperação agora. Tente novamente em instantes.';
      }
    }

    if ($error === '') {
      $showSuccess = true;
    }
  }
}

$pageTitle = 'ImobiHub — Recuperar Senha';
require_once __DIR__ . '/includes/auth_header.php';
?>

<div class="auth-card auth-card-centered" id="form-card" <?= $showSuccess ? 'hidden' : '' ?>>
  <div class="auth-icon" aria-hidden="true">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <rect x="3" y="5" width="18" height="14" rx="2"/>
      <polyline points="3 7 12 13 21 7"/>
    </svg>
  </div>
  <h1 class="auth-heading">Recuperar senha</h1>
  <p class="auth-sub">Informe seu e-mail de administrador e enviaremos um link para você criar uma nova senha.</p>

  <?php if ($error !== ''): ?>
    <div class="auth-alert auth-alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form class="auth-form" id="recover-form" method="post" novalidate>
    <label>
      E-mail cadastrado
      <input type="email" name="email" placeholder="seu@email.com" autocomplete="email" required
             value="<?= htmlspecialchars((string) ($_POST['email'] ?? '')) ?>">
    </label>

    <button class="btn btn-primary btn-full" type="submit">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.18 2 2 0 0 1 3.6 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.6a16 16 0 0 0 6 6l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
      </svg>
      Enviar link de recupera&ccedil;&atilde;o
    </button>
  </form>

  <div class="auth-link-row"><a href="/login.php">&larr; Voltar para o login</a></div>
</div>

<div class="auth-card auth-card-centered" id="success-card" <?= $showSuccess ? '' : 'hidden' ?>>
  <div style="text-align:center;padding:8px 0">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2" style="margin-bottom:12px">
      <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
      <polyline points="22 4 12 14.01 9 11.01"/>
    </svg>
    <h2 class="auth-heading" style="margin-bottom:8px">E-mail enviado!</h2>
    <p class="auth-sub" style="margin-bottom:24px">
      Verifique sua caixa de entrada e siga as instru&ccedil;&otilde;es para redefinir sua senha.
    </p>
    <a href="/login.php" class="btn btn-primary btn-full">Voltar ao login</a>
  </div>
</div>

<p class="auth-back-link">
  <a href="/">&larr; Voltar ao cat&aacute;logo p&uacute;blico</a>
</p>

</body>
</html>
