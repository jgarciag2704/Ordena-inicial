<script>
window.ORDENA = <?= json_encode(['tenant' => $business['slug'] ?? '', 'products' => $products, 'branches' => $branches, 'zonesByBranch' => $zonesByBranch, 'radioZonesByBranch' => $radioZonesByBranch, 'cart' => $cart, 'total' => $total], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="/assets/app.js?v=<?= filemtime(BASE_PATH . '/public/assets/app.js') ?>"></script>
