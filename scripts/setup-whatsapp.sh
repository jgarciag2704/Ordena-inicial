#!/bin/bash
set -e

# ============================================
# Script de setup para WhatsApp (Evolution API)
# Ejecutar DESPUÉS de: docker-compose -f docker-compose.prod.yml up -d
# ============================================

echo "=== Setup WhatsApp (Evolution API) ==="
echo ""

# ============================================
# PASO 1: Variables en .env (si faltan)
# ============================================
echo "[1/3] Verificando variables en .env..."

if ! grep -q "EVOLUTION_API_URL" .env; then
    echo ""
    echo "⚠️  Faltan variables de Evolution API en .env"
    echo ""
    read -p "¿Generar valores aleatorios y agregarlos? (s/n): " RESP
    if [ "$RESP" = "s" ] || [ "$RESP" = "S" ]; then
        API_KEY=$(openssl rand -hex 16)
        EVOLUTION_ROOT_PASS=$(openssl rand -base64 16 | tr -d '/+=')
        EVOLUTION_PASS=$(openssl rand -base64 16 | tr -d '/+=')
        
        cat >> .env <<EOF

# Evolution API (WhatsApp)
EVOLUTION_API_URL=http://evolution-api:8080
EVOLUTION_API_API_KEY=$API_KEY
EVOLUTION_DB_ROOT_PASSWORD=$EVOLUTION_ROOT_PASS
EVOLUTION_DB_PASSWORD=$EVOLUTION_PASS
EOF
        
        echo ""
        echo "✅ Variables agregadas al .env"
        echo ""
        echo "🔑 GUARDA ESTAS CREDENCIALES:"
        echo "   EVOLUTION_API_API_KEY:       $API_KEY"
        echo "   EVOLUTION_DB_ROOT_PASSWORD:  $EVOLUTION_ROOT_PASS"
        echo "   EVOLUTION_DB_PASSWORD:       $EVOLUTION_PASS"
        echo ""
        echo "⚠️  Reinicia los contenedores para aplicar los cambios:"
        echo "   docker-compose -f docker-compose.prod.yml down"
        echo "   docker-compose -f docker-compose.prod.yml up -d"
        echo ""
        exit 0
    else
        echo "Edita .env manualmente y agrega:"
        echo "  EVOLUTION_API_URL=http://evolution-api:8080"
        echo "  EVOLUTION_API_API_KEY=<genera_con_openssl_rand_hex_16>"
        echo "  EVOLUTION_DB_ROOT_PASSWORD=<password_seguro>"
        echo "  EVOLUTION_DB_PASSWORD=<password_seguro>"
        exit 1
    fi
else
    echo "✅ Variables ya existen en .env"
fi

# ============================================
# PASO 2: Esperar a que la BD esté lista
# ============================================
echo ""
echo "[2/3] Esperando a que MariaDB esté lista..."

for i in {1..30}; do
    if docker exec ordena_db mysqladmin ping -h localhost --silent 2>/dev/null; then
        echo "✅ MariaDB listo"
        break
    fi
    echo "  Esperando... ($i/30)"
    sleep 2
done

# ============================================
# PASO 3: Aplicar migración whatsapp-instances.sql
# ============================================
echo ""
echo "[3/3] Aplicando migración whatsapp-instances.sql..."

# Verificar si la tabla whatsapp_envios ya existe
TABLE_EXISTS=$(docker exec ordena_db mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$DB_DATABASE" -se "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_DATABASE' AND table_name='whatsapp_envios';" 2>/dev/null)

# Leer credenciales del .env
MYSQL_ROOT_PASSWORD=$(grep "^MYSQL_ROOT_PASSWORD=" .env | cut -d'=' -f2)
DB_DATABASE=$(grep "^DB_DATABASE=" .env | cut -d'=' -f2)

if [ "$TABLE_EXISTS" = "1" ]; then
    echo "ℹ️  La tabla whatsapp_envios ya existe, omitiendo migración"
else
    docker exec -i ordena_db mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$DB_DATABASE" < database/whatsapp-instances.sql
    echo "✅ Migración aplicada correctamente"
fi

echo ""
echo "=== Setup completado ==="
echo ""
echo "Próximos pasos:"
echo ""
echo "1. Abre en el navegador:"
echo "   https://tudominio.com/admin/whatsapp?tenant=tu_tenant"
echo ""
echo "2. Clic en 'Vincular WhatsApp'"
echo "3. Selecciona 'Código de emparejamiento'"
echo "4. Ingresa tu número con código de país (ej: 5214611086760)"
echo "5. Copia el código de 8 dígitos"
echo "6. En WhatsApp del celular:"
echo "   Menú → Dispositivos vinculados → Vincular dispositivo"
echo "   → Vincular con número de teléfono"
echo "7. Ingresa el código de 8 dígitos"
echo "8. ⚠️  ESPERA 2-3 MINUTOS antes de enviar mensajes"
echo "   (Baileys necesita sincronizar claves de encriptación)"
echo "9. Prueba enviar mensaje de prueba"
echo ""
