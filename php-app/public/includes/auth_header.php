<?php
/**
 * includes/auth_header.php
 * Cabeçalho para páginas de autenticação (login, cadastro, etc.).
 *
 * Variáveis esperadas antes de incluir:
 *   $pageTitle  (string)
 *   $extraHead  (string) — HTML extra no <head> (opcional)
 */
$pageTitle = $pageTitle ?? 'ImobiHub';
$extraHead = $extraHead ?? '';
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="/assets/styles.css">
  <?= $extraHead ?>
</head>
<body class="auth-page">

<a href="/" class="auth-logo">
  <svg width="40" height="40" viewBox="0 0 36 36" fill="none" aria-hidden="true">
    <rect width="36" height="36" rx="8" fill="#EBF1FF"/>
    <path d="M18 8L6 18h4v10h6v-6h4v6h6V18h4L18 8Z" fill="#1B4FBB"/>
  </svg>
  <div class="brand-text">
    <span class="brand-name">ImobiHub</span>
    <span class="brand-sub">Conectando Im&oacute;veis &amp; Neg&oacute;cios</span>
  </div>
</a>
