UPDATE usuarios
SET password_hash = '$2y$10$B7PX89RAaY0AgWtJcwzhDu2Mfi.5gr1APXwn.rueimREKywyIa5g2',
    debe_cambiar_password = 1,
    activo = 1
WHERE email = 'admin@laburgueria.test';
