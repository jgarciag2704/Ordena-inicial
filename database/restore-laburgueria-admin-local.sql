UPDATE usuarios
SET password_hash = '$2y$10$8cRKhiTL.OzTpg0MgLZR0ukOR5XHl4ZVOJKSvdBzIHjVmG6M5gdqG',
    debe_cambiar_password = 0,
    activo = 1
WHERE email = 'admin@laburgueria.test';
