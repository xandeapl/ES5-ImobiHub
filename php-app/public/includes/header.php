<?php
/**
 * includes/header.php
 * Cabeçalho padrão das páginas do painel (dashboard, admin).
 *
 * Variáveis esperadas antes de incluir:
 *   $pageTitle  (string) — título da aba do navegador
 *   $navLinks   (array)  — [ ['href'=>'...', 'label'=>'...', 'icon'=>'...'] ]
 *                          ícones: 'home' | 'settings' | 'logout'
 *   $extraHead  (string) — HTML adicional no <head> (opcional)
 */
$pageTitle = $pageTitle ?? 'ImobiHub';
$navLinks  = $navLinks  ?? [];
$extraHead = $extraHead ?? '';

$iconMap = [
    'home'     => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
    'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/>',
    'grid'     => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>',
    'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
];
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
<body>

<header class="topbar">
  <div class="container topbar-inner">
    <a href="/" class="brand">
      <svg class="brand-icon" width="32" height="32" viewBox="0 0 36 36" fill="none" aria-hidden="true">
        <rect width="36" height="36" rx="8" fill="#EBF1FF"/>
        <path d="M18 8L6 18h4v10h6v-6h4v6h6V18h4L18 8Z" fill="#1B4FBB"/>
      </svg>
      <span class="brand-name">ImobiHub</span>
    </a>
    <?php if (!empty($navLinks)): ?>
    <nav class="dash-header-nav">
      <?php foreach ($navLinks as $link): ?>
        <?php $svgPath = $iconMap[$link['icon'] ?? ''] ?? ''; ?>
        <a href="<?= htmlspecialchars($link['href']) ?>" class="btn btn-outline-muted btn-sm">
          <?php if ($svgPath): ?>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $svgPath ?></svg>
          <?php endif; ?>
          <?= htmlspecialchars($link['label']) ?>
        </a>
      <?php endforeach; ?>
      <?php if (!empty($headerButton)): ?>
        <button class="btn btn-primary btn-sm" <?= $headerButton['attrs'] ?? '' ?>>
          <?= htmlspecialchars($headerButton['label']) ?>
        </button>
      <?php endif; ?>
    </nav>
    <?php endif; ?>
  </div>
</header>
