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
