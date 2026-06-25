<?php
/**
 * includes/footer.php
 * Rodapé padrão das páginas do painel.
 *
 * Variáveis esperadas antes de incluir:
 *   $footerText  (string) — texto do rodapé (opcional)
 *   $scripts     (array)  — [ ['src'=>'...', 'type'=>'module'] ] (opcional)
 */
$footerText = $footerText ?? '&copy; ' . date('Y') . ' ImobiHub';
$scripts    = $scripts    ?? [];
?>

<footer class="page-footer"><?= $footerText ?></footer>

<?php foreach ($scripts as $script): ?>
  <script
    <?php if (!empty($script['type'])): ?>type="<?= htmlspecialchars($script['type']) ?>"<?php endif; ?>
    src="<?= htmlspecialchars($script['src']) ?>">
  </script>
<?php endforeach; ?>

</body>
</html>
