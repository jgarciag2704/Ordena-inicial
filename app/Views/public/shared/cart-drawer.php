<button class="cart" onclick="openCart()">Ver carrito · <span id="cartTotal">$0</span></button>

<div class="drawer" id="drawer">
    <aside class="panel">
        <button class="chip" onclick="closeAll()">Cerrar x</button>
        <h2>Tu carrito</h2>
        <div id="cartItems"></div>
        <div class="totals"><div class="row"><b>Total</b><b id="totalAside">$0</b></div></div>
        <button class="primary" style="width:100%" onclick="checkout()">Continuar pedido</button>
    </aside>
</div>
