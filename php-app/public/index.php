<?php
/**
 * index.php — Catálogo público de imóveis
 * Página principal servida pelo servidor PHP.
 */
require_once __DIR__ . '/includes/auth.php';

$config = require __DIR__ . '/../backend/config/config.php';
$whatsAppNumber = preg_replace('/\D+/', '', (string) ($config['contact_whatsapp'] ?? '')) ?: '5541999998888';
$cssVersion = (string) (filemtime(__DIR__ . '/assets/styles.css') ?: time());
$catalogVersion = (string) (filemtime(__DIR__ . '/assets/js/catalog.js') ?: time());

$pageTitle = 'ImobiHub — Catálogo de Imóveis';
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
        var logoBg = rgbToHex(clamp(rgb.r + 198), clamp(rgb.g + 198), clamp(rgb.b + 198));

        root.style.setProperty('--brand', brand);
        root.style.setProperty('--brand-dark', dark);
        root.style.setProperty('--brand-hover', hover);
        root.style.setProperty('--bg', bg);
        root.style.setProperty('--text', text);
        root.style.setProperty('--logo-bg', logoBg);
      } catch (e) {}
    })();

    document.addEventListener('DOMContentLoaded', function () {
      try {
        var logo = localStorage.getItem('imobihub_logo_v1');
        if (!/^data:image\/[a-zA-Z0-9.+-]+;base64,/.test(logo || '')) return;

        document.querySelectorAll('.brand svg, .auth-logo svg').forEach(function (svg) {
          var img = document.createElement('img');
          img.src = logo;
          img.alt = 'ImobiHub';
          if (svg.hasAttribute('class')) img.setAttribute('class', svg.getAttribute('class'));
          if (svg.hasAttribute('width')) img.setAttribute('width', svg.getAttribute('width'));
          if (svg.hasAttribute('height')) img.setAttribute('height', svg.getAttribute('height'));
          img.style.objectFit = 'contain';
          svg.parentNode.replaceChild(img, svg);
        });
      } catch (e) {}
    });
  </script>
  <link rel="stylesheet" href="/assets/styles.css?v=<?= htmlspecialchars($cssVersion) ?>">
</head>
<body data-whatsapp="<?= htmlspecialchars($whatsAppNumber) ?>">

  <header class="topbar">
    <div class="container topbar-inner">
      <a href="/" class="brand">
        <svg class="brand-icon" width="36" height="36" viewBox="0 0 36 36" fill="none" aria-hidden="true">
          <rect width="36" height="36" rx="8" fill="var(--logo-bg, #EBF1FF)"/>
          <path d="M18 8L6 18h4v10h6v-6h4v6h6V18h4L18 8Z" fill="var(--brand, #1B4FBB)"/>
        </svg>
        <div class="brand-text">
          <span class="brand-name">ImobiHub</span>
          <span class="brand-sub">Conectando Im&oacute;veis &amp; Neg&oacute;cios</span>
        </div>
      </a>
      <a class="btn btn-dark btn-sm" href="/login.php">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12l7-7 7 7"/></svg>
        Anunciar im&oacute;vel
      </a>
    </div>
  </header>

  <main class="container page-content">

    <section class="panel">
      <form id="filter-form">
        <div class="filters-bar">

          <label>
            Neg&oacute;cio
            <div class="select-wrap">
              <select name="dealType">
                <option value="todos">Todos</option>
                <option value="comprar">Comprar</option>
                <option value="alugar">Alugar</option>
              </select>
            </div>
          </label>

          <label>
            Tipo
            <div class="select-wrap">
              <select name="propertyType">
                <option value="todos">Todos os tipos</option>
                <option value="apartamento">Apartamento</option>
                <option value="casa">Casa</option>
                <option value="imovel-comercial">Comercial</option>
                <option value="studio">Studio</option>
                <option value="cobertura">Cobertura</option>
                <option value="terreno">Terreno</option>
              </select>
            </div>
          </label>

          <label>
            Busca
            <input type="text" name="q" placeholder="Cidade, bairro, descri&ccedil;&atilde;o&hellip;">
          </label>

          <label>
            Ordena&ccedil;&atilde;o
            <div class="select-wrap">
              <select name="sort">
                <option value="default">Padr&atilde;o</option>
                <option value="latest">Mais recentes</option>
                <option value="affordable">Mais baratos</option>
              </select>
            </div>
          </label>

          <div class="filters-bottom">
            <button class="btn btn-primary" type="submit">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
              Buscar
            </button>
            <label class="checkline">
              <input type="checkbox" name="showSold" value="1">
              Mostrar vendidos
            </label>
          </div>

        </div>
      </form>
    </section>

    <div class="result-header">
      <h2 id="result-count">Resultados (0)</h2>
    </div>

    <section id="cards" class="cards"></section>

    <section id="empty-state" class="panel empty-state" hidden>
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;color:#94A3B8"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      <h3>Nenhum im&oacute;vel encontrado</h3>
      <p>Tente ajustar os filtros para encontrar o que procura.</p>
    </section>

  </main>

  <footer class="page-footer">
    &copy; <?= date('Y') ?> ImobiHub &mdash; Conectando Im&oacute;veis &amp; Neg&oacute;cios
  </footer>

  <script type="module" src="/assets/js/catalog.js?v=<?= htmlspecialchars($catalogVersion) ?>"></script>
</body>
</html>
