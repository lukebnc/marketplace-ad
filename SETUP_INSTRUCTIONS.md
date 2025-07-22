# 🚀 Market-X - Instrucciones de Instalación

## 📋 PASOS PARA ACTUALIZAR TU MARKETPLACE

### ⚡ 1. ACTUALIZAR BASE DE DATOS

Antes de usar el marketplace mejorado, debes actualizar tu base de datos MySQL:

**Opción A: Usando el script web (Recomendado)**
1. Abre tu navegador
2. Ve a: `http://localhost/tu-directorio/setup_database.php`
3. El script actualizará automáticamente todas las tablas
4. Elimina el archivo `setup_database.php` después

**Opción B: Usando phpMyAdmin**
1. Abre phpMyAdmin
2. Selecciona tu base de datos `ecommerce_db`
3. Ve a la pestaña "SQL"
4. Copia y ejecuta el contenido de `database_updates.sql`

### 🎨 2. CARACTERÍSTICAS NUEVAS

✅ **Diseño moderno** - Tema oscuro con acentos naranjas
✅ **Sistema de vendors mejorado** - Panel completo para vendedores
✅ **Seguridad avanzada** - Cifrado end-to-end y autenticación robusta
✅ **Interfaz moderna** - Gradientes, efectos hover, animaciones
✅ **Responsive design** - Adaptado a todos los dispositivos
✅ **Sistema de notificaciones** - Alertas modernas y elegantes

### 👤 3. USUARIOS POR DEFECTO

Después de ejecutar `setup_database.php`, tendrás:

**Administrador:**
- Usuario: `admin`
- Contraseña: `admin123`
- Permisos: Administrador y Vendedor aprobado

### 🔧 4. CONFIGURACIÓN INICIAL

1. **Configurar pagos Monero:**
   - Ve a configuración del sistema
   - Añade tu dirección XMR
   - Configura tasas de comisión

2. **Configurar certificados SSL:**
   - Para máxima seguridad en Tor
   - Activa HTTPS siempre que sea posible

3. **Configurar límites:**
   - Tamaño máximo de archivos
   - Límites de productos por vendor
   - Configuración de seguridad

### 🛠️ 5. ESTRUCTURA DE ARCHIVOS

```
/tu-marketplace/
├── 📁 assets/
│   └── styles.css (ACTUALIZADO - Tema moderno)
├── 📁 includes/
│   ├── db.php (Conexión BD)
│   ├── functions.php (Funciones principales) 
│   └── security.php (Sistema de seguridad)
├── 📁 admin/ (Panel administrativo)
├── 📁 uploads/ (Archivos subidos)
├── index.php (MEJORADO - Página principal)
├── login.php (Sistema de login avanzado)
├── register.php (NUEVO - Registro con validación)
└── setup_database.php (Script de actualización BD)
```

### 🔒 6. CARACTERÍSTICAS DE SEGURIDAD

- **Cifrado AES-256-GCM** para datos sensibles
- **Tokens CSRF** para prevenir ataques
- **Sistema anti-fuerza bruta** con bloqueo de cuentas
- **Validación de contraseñas** robusta
- **Logs de auditoría** de todas las acciones
- **Sesiones seguras** con detección de hijacking
- **Remember Me** con tokens seguros

### 💰 7. SISTEMA DE VENDEDORES

- **Registro de vendedores** con verificación de pago
- **Panel de control** completo para vendedores
- **Sistema de comisiones** configurable
- **Ratings y reviews** para productos y vendedores
- **Mensajería cifrada** entre usuarios y vendedores
- **Gestión de inventario** y estadísticas de ventas

### 🎯 8. PRÓXIMOS PASOS

1. Ejecuta el script de actualización de BD
2. Testa el login con usuario admin
3. Configura los ajustes del marketplace
4. Añade productos de prueba
5. Testa todas las funcionalidades

---

## ⚠️ IMPORTANTE

- Haz **backup** de tu base de datos antes de ejecutar el script
- Cambia la contraseña del admin después del primer login
- Configura SSL/TLS para conexiones seguras
- Revisa los logs de seguridad regularmente

---

## 📞 SOPORTE

Si necesitas ayuda con la instalación o configuración:
- Revisa los logs en `/var/log/` 
- Verifica la configuración de PHP y MySQL
- Asegúrate de tener los permisos correctos en archivos

**¡Disfruta tu marketplace moderno y seguro!** 🚀