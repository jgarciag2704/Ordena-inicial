<script>
window.ORDENA = <?= json_encode(['products' => $products, 'branches' => $branches, 'cart' => $cart, 'total' => $total], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="/assets/app.js"></script>
