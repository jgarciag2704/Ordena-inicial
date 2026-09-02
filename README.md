# Ordena Inicial

Aplicación PHP MVC multitenant para pedidos directos de restaurantes sin comisión por venta.

## Stack

- PHP 8.2-FPM
- Nginx
- MariaDB 11
- Redis
- Docker Compose
- phpMyAdmin en `http://localhost:8089`
- App en `http://localhost:8088`

## Estructura

- `app/Controllers`: controladores públicos y admin.
- `app/Models`: acceso a datos con filtros por `negocio_id`.
- `app/Services`: contexto/resolución de tenant y carrito de sesión.
- `app/Views`: vistas PHP.
- `config`: configuración y helpers.
- `routes`: rutas web.
- `database`: esquema y seeders SQL.
- `public`: front controller y assets.

## Ejecutar

```bash
cp .env.example .env
docker compose up -d --build
```

Si ya existe el archivo `.env`, revisa que incluya:

```env
DB_HOST=db
DB_PORT=3306
DB_DATABASE=ordena
DB_USERNAME=ordena
DB_PASSWORD=tu_password
MYSQL_ROOT_PASSWORD=tu_root_password
```

## Migraciones y seeders

Aplicar esquema desde cero:

```bash
docker compose exec -T db sh -c 'mariadb -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < database/schema.sql
```

Aplicar datos semilla:

```bash
docker compose exec -T db sh -c 'mariadb -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < database/seed.sql
```

Seed incluido:

- Negocio: `La Burguería`
- Slug: `laburgueria`
- Admin: `admin@laburgueria.test`
- Password admin: `password`
- Categorías: Hamburguesas, Combos, Papas y extras, Bebidas.
- Opciones: queso extra, sin cebolla e indicaciones libres.

## Probar tenant local

URL compatible con query string:

```text
http://localhost:8088/?tenant=laburgueria
```

Panel administrativo:

```text
http://localhost:8088/admin/login?tenant=laburgueria
```

También se soporta resolución por subdominio:

```text
http://laburgueria.ordena.localhost:8088
http://laburgueria.ordena.localhost:8088/admin/login
```

Si tu sistema no resuelve subdominios de `.localhost`, agrega temporalmente esta entrada al archivo hosts:

```text
127.0.0.1 laburgueria.ordena.localhost
```

## Super Admin

Panel global para dar de alta negocios:

```text
http://localhost:8088/superadmin/login
```

Credenciales temporales iniciales:

```text
jgarciag2704@gmail.com
Temporal1
```

En el primer acceso el sistema obliga a cambiar la contraseña antes de mostrar el panel.

Si ya tenías la base creada antes de esta funcionalidad, aplica solo el SQL incremental:

```bash
docker compose exec -T db sh -c 'mariadb -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < database/superadmin.sql
```

En este panel puedes crear un negocio con slug, prefijo de folio, sucursal inicial y usuario admin del negocio.

Desde la lista de negocios puedes usar `Restablecer contraseña` para dejar la contraseña del admin del negocio en:

```text
Temporal1
```

En el siguiente login, ese admin tendrá que cambiarla antes de entrar al tablero.

Regla de contraseñas para admins y super admin:

- Mínimo 8 caracteres.
- Al menos una mayúscula.
- Puede incluir letras, números y símbolos.

## Administrar Menú

Desde el panel del negocio puedes agregar categorías y productos con foto:

```text
http://localhost:8088/admin/menu?tenant=laburgueria
```

Las fotos se procesan como thumbnails WebP de `640x420` en:

```text
public/uploads/products
```

Si tu base ya existía antes de esta función, aplica:

```bash
docker compose exec -T db sh -c 'mariadb -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < database/menu-images.sql
```

Para poder generar thumbnails, reconstruye PHP porque se habilitó la extensión `gd`:

```bash
docker compose up -d --build --force-recreate app nginx worker
```

En esa misma pantalla, cada producto tiene la sección `Extras y opciones` para crear:

- Grupos de selección múltiple, por ejemplo `Extras`.
- Grupos de selección única, por ejemplo `Término` o `Bebida`.
- Grupos de texto libre.
- Valores con precio extra, por ejemplo `Tocino extra +20`.
- Opciones obligatorias.

## Módulos Admin

El panel del negocio tiene navbar con módulos:

- `Pedidos`: tablero operativo.
- `Menú`: productos, fotos, categorías y extras.
- `Sucursales`: puntos de venta activos/inactivos.
- `Horarios`: días y horas disponibles para recibir pedidos por sucursal.
- `Personalización`: colores, fondo, fuente y textos de portada por negocio.

URLs locales:

```text
http://localhost:8088/admin?tenant=laburgueria
http://localhost:8088/admin/menu?tenant=laburgueria
http://localhost:8088/admin/branches?tenant=laburgueria
http://localhost:8088/admin/hours?tenant=laburgueria
http://localhost:8088/admin/branding?tenant=laburgueria
```

Si tu base ya existía antes de sucursales/horarios, aplica:

```bash
docker compose exec -T db sh -c 'mariadb -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < database/admin-modules.sql
```

Los horarios se guardan por sucursal en `sucursal_horarios`. La tabla anterior `negocio_horarios` puede existir en bases antiguas, pero el módulo actual usa horarios por sucursal.

La personalización se guarda en `negocios`. Si tu base ya existía antes de esta función, aplica:

```bash
docker compose exec -T db sh -c 'mariadb -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < database/branding.sql
```

## Flujo Público

1. Seleccionar modalidad: pickup, mesa o delivery.
2. Cargar categorías y productos desde MariaDB.
3. Personalizar productos con opciones e indicaciones.
4. Agregar cantidades; cada pieza se guarda como item separado del carrito.
5. Checkout con nombre y teléfono.
6. OTP simulado: `123456`.
7. Crear pedido real en estado `nuevo` con folio por negocio, por ejemplo `LB-1001`.

## Multitenancy

El tenant se resuelve desde `HTTP_HOST` para subdominios `*.ordena.garciacore.com` y `*.ordena.localhost`. En local también se acepta `?tenant=slug`.

Todas las consultas de negocio usan el `negocio_id` del contexto actual para evitar lectura o modificación cruzada entre tenants.
