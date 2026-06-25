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
  <script>
    (function () {
      try {
        var raw = localStorage.getItem('imobihub_theme_v1');
        if (!raw) return;
        var theme = JSON.parse(raw);
        if (!theme || typeof theme !== 'object') return;
        var root = document.documentElement;
        var safe = function (color, fallback) {
          return /^#[0-9a-fA-F]{6}$/.test(color || '') ? color : fallback;
        };
        var brand = safe(theme.brand, '#1B4FBB');
        var dark = safe(theme.dark, '#0D2B69');
        var bg = safe(theme.bg, '#EEF2F8');
        var text = safe(theme.text, '#0F2557');
        var toRgb = function (hex) {
          return {
            r: parseInt(hex.slice(1, 3), 16),
            g: parseInt(hex.slice(3, 5), 16),
            b: parseInt(hex.slice(5, 7), 16)
          };
        };
        var clamp = function (v) { return Math.max(0, Math.min(255, v)); };
        var rgbToHex = function (r, g, b) {
          var h = function (n) { return n.toString(16).padStart(2, '0'); };
          return '#' + h(r) + h(g) + h(b);
        };
        var rgb = toRgb(brand);
        var hover = rgbToHex(clamp(rgb.r - 18), clamp(rgb.g - 18), clamp(rgb.b - 18));

        root.style.setProperty('--brand', brand);
        root.style.setProperty('--brand-dark', dark);
        root.style.setProperty('--brand-hover', hover);
        root.style.setProperty('--bg', bg);
        root.style.setProperty('--text', text);
      } catch (e) {}
    })();
  </script>
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
