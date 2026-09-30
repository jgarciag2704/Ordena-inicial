<div class="order-bar">
    <button class="bar-btn account" onclick="toggleAccountMenu()" aria-label="Mi cuenta">
        <span id="accountFabLabel">Ingresar</span>
    </button>
    <div class="account-menu" id="accountMenu">
        <button onclick="openMyOrders()">Mis pedidos</button>
        <button onclick="accountLogout()">Cerrar sesión</button>
    </div>
    <button class="bar-btn bar-cart" onclick="openCart()">
        <span>Carrito</span>
        <b id="cartTotal">$0</b>
        <span class="bar-badge" id="cartCount">0</span>
    </button>
</div>