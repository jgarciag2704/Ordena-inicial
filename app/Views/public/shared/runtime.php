<script>
window.ORDENA = <?= json_encode(['products' => $products, 'branches' => $branches, 'cart' => $cart, 'total' => $total, 'business' => ['comer_aqui_url' => $business['comer_aqui_url'] ?? null]], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="/assets/app.js?v=<?= filemtime(BASE_PATH . '/public/assets/app.js') ?>"></script>
