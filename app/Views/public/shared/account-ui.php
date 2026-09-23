<button class="account-fab" onclick="toggleAccountMenu()" aria-label="Mi cuenta">
    <span id="accountFabLabel">Ingresar</span>
</button>
<div class="account-menu" id="accountMenu">
    <button onclick="openMyOrders()">Mis pedidos</button>
    <button onclick="accountLogout()">Cerrar sesión</button>
</div>

<div class="modal" id="accountModal">
    <section class="modalbox" id="accountContent"></section>
</div>