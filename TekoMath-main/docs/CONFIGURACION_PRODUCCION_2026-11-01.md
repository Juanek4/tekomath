# Configuración local y de producción de TEKO Math

Fecha: 01/11/2026, Paraguay.

> Actualización posterior del 01/11/2026: la contraseña elegida ya fue aplicada, el hash de arranque se retiró y las comprobaciones de permisos posteriores al reinicio respondieron OK sin advertencias. El respaldo final y su restauración están documentados en RESPALDO_Y_RESTAURACION_2026-11-01.md. Los pendientes mencionados abajo describen el momento anterior de preparación.


## Resultado
Localhost continúa en modo local. La aplicación usa dos cuentas SQL propias, con contraseñas aleatorias, separadas por base. Las credenciales están en C:/xampp1/teko-private/teko-math.php, fuera de htdocs y del proyecto. No se publica la plataforma ni se cambia la contraseña de administración por una elegida automáticamente.

config/runtime.php centraliza la configuración. Lee primero la variable de servidor TEKO_CONFIG_FILE, o el archivo privado local predeterminado; las variables TEKO_* del proceso tienen prioridad sobre ese archivo. Rechaza archivos privados ubicados dentro del proyecto o del DocumentRoot. El archivo privado se restringió por ACL a la cuenta local del usuario, SYSTEM y administradores. No se imprimieron las contraseñas en los resultados de las herramientas.

## Cambios realizados
- La conexión principal y TekoBot ahora leen sus respectivos MAIN_DB_* y TUTOR_DB_* (host, puerto, nombre, usuario y contraseña). Cada cuenta real de ejecución solo tiene acceso a su propia base. No se modificó la contraseña global de root.
- El adaptador env.php del tutor deja de cargar .env desde el directorio público. La clave opcional de Gemini y el hash de instalación de consola pertenecen a la configuración privada o a variables del servidor. El .env.example antiguo explica la migración al ejemplo común. No había un .env real del tutor que migrar en esta instalación.
- El hash inicial de consola dejó de estar incrustado en admin_invitations.php. Se conserva en la configuración privada para instalación local deliberada; la credencial activa sigue en la base. Si falta una credencial, no se instala una contraseña conocida desde el código público.
- Producción exige una URL pública HTTPS, usuarios SQL distintos de root y contraseñas no vacías. Oculta errores PHP al visitante. Las páginas y APIs que usan sesiones rechazan HTTP inseguro, y las cookies conservan HttpOnly, SameSite y Secure bajo HTTPS.
- No se confía automáticamente en X-Forwarded-Proto. Si el alojamiento termina TLS en un proxy, hay que configurarlo de forma confiable para que PHP identifique HTTPS; no se debe quitar el control.
- En producción, una falla de MySQL del tutor no crea otra base ni activa SQLite. El modo local conserva el comportamiento de compatibilidad existente.
- Las sentencias estructurales de la aplicación pasan por teko_schema_exec. En producción normal no se ejecuta CREATE/ALTER durante las peticiones. Solo la ejecución CLI con TEKO_SCHEMA_SETUP=1 permite preparar estructura. En local sigue disponible la compatibilidad de creación de tablas.
- scripts/prepare_schema.php prepara explícitamente las tablas, sin ejecutar CREATE DATABASE, USE ni los INSERT de estudiantes demo del volcado histórico del tutor. Las bases deben existir previamente en producción y debe usarse una credencial temporal de migración.
- scripts/check_runtime.php comprueba conexiones, cuentas no root, acceso a las tablas requeridas y contraseña inicial de consola. No imprime contraseñas ni claves. Es una comprobación de configuración, no una certificación de publicación.

## Privilegios locales y de producción
Las cuentas locales nuevas tienen SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX y REFERENCES únicamente sobre su respectiva base; se mantienen CREATE/ALTER para la instalación local existente. No tienen permisos globales, GRANT OPTION ni lectura de la otra base. No se afirma que sean cuentas DML exclusivamente.

Para producción se probaron cuentas temporales con solo SELECT, INSERT, UPDATE y DELETE sobre cada base. Esas son las autorizaciones previstas para la ejecución pública. La migración debe usar otra cuenta con permisos estructurales dentro de las bases asignadas; esa cuenta no debe quedar en la configuración del servidor web.

## Preparación del alojamiento
1. Elegir alojamiento compatible con PHP/PDO MySQL y HTTPS, y una URL definitiva para la raíz del proyecto. En un proveedor SQL remoto también deben configurarse sus exigencias de TLS, certificados y conectividad; esta tarea no seleccionó ni certificó un proveedor.
2. Crear las dos bases y cuentas SQL con permisos por base. Respaldar e importar ambas bases manteniendo los identificadores, incluido portal_student_links; no importar solamente una base porque se perdería la correspondencia entre cuentas y conversaciones.
3. Copiar config/runtime.example.php a un archivo fuera del directorio público del alojamiento. Completar ENV=production, PUBLIC_URL=https://dominio/ruta/, MAIN_DB_*, TUTOR_DB_* y la clave opcional de Gemini. Las contraseñas del ejemplo están vacías a propósito: producción las rechaza.
4. Definir TEKO_CONFIG_FILE en el entorno real de Apache/PHP/FPM. El archivo no debe colocarse en htdocs ni depender de un .env público. Restringir sus permisos al usuario que ejecuta PHP y administradores.
5. Antes de preparar la estructura, usar temporalmente credenciales de migración y ejecutar por consola `php scripts/prepare_schema.php`. Restaurar después las credenciales DML de ejecución. El script no instala cuentas docentes ni una cuenta administrativa de producción; se conserva/importa la credencial administrativa elegida y ya cambiada en el entorno de origen.
6. Ejecutar `php scripts/check_runtime.php` con la configuración de destino. Verificar HTTPS real, cookies y redirecciones. Revisar errores de despliegue sin habilitar display_errors al público.
7. Excluir de la publicación tests, documentación interna, dependencias de desarrollo, scripts de provisión, respaldos y archivos privados. Las reglas .htaccess no sustituyen una publicación con una lista explícita de archivos. Revalidarlas en el alojamiento, especialmente si no usa Apache.

La contraseña administrativa inicial continúa vigente en localhost porque su reemplazo debe elegirlo el usuario. Se cambia desde la consola, con el formulario ya existente. En producción normal, credentials() rechaza una credencial marcada como inicial. Dominio, certificado, transporte de correo y configuración del proveedor remoto quedan pendientes de seleccionar esos servicios.

## Pruebas
- private_config_http_test.cjs PASS: las cuentas reales no son root, no leen la otra base y no se descargan configuración ni scripts. /teko-private/teko-math.php responde 404 por estar fuera de htdocs.
- production_config_test.cjs PASS: usuarios temporales DML sin CREATE; conexiones y servicios de preparación en modo normal sin DDL; bloqueo de root, contraseña vacía, URL HTTP y archivo privado público; errores ocultos; falla de MySQL sin SQLite; contraseña inicial de consola rechazada y acceso autenticado por HTTP rechazado. No activa producción en el servidor habitual.
- pilot_accounts_http_test.cjs, password_recovery_http_test.cjs y tutor_identity_http_test.cjs PASS con las nuevas cuentas reales: invitaciones, registros, asignaciones, aislamiento, recuperación, revocación de sesiones, diagnósticos y chats.
- resource_permissions_http_test.cjs, live_permissions_http_test.cjs y arcade_accounts_http_test.cjs PASS: permisos de tareas/materiales, calificación en servidor, Live y persistencia aislada de Arcade.
- private_files_http_test.cjs PASS: 15 rutas internas denegadas y nueve recursos públicos accesibles.
- prepare_schema.php ejecutado correctamente sobre las bases existentes, sin cuentas demo nuevas. PHP lint sin errores en configuración y archivos revisados.
- Las cuentas y usuarios SQL temporales de pruebas se eliminaron. No se cambiaron contraseñas de usuarios reales ni sus notas, asignaciones o conversaciones.

## Incidencia de MariaDB encontrada durante la preparación
MySQL y Apache estaban apagados y se iniciaron para verificar el trabajo. Al crear los permisos de aplicación, MariaDB respondió con error Aria 176: checksum inválido en mysql.db. Se detuvo MySQL y se conservaron los archivos db y global_priv (frm/MAI/MAD) en C:/xampp1/teko-private/backups/privileges-20261101-161438 antes de reparar.

La recuperación offline indicó tres registros declarados y cero recuperados: los permisos por base anteriores eran ilegibles. La copia de mysql.db contenía texto de logs. Se terminó la reparación con REPAIR TABLE, y CHECK TABLE mysql.db pasó a OK. Se generaron los permisos de las dos cuentas nuevas y se repusieron los permisos de pma sobre phpmyadmin siguiendo el SQL incluido con la instalación de phpMyAdmin. Las cuentas globales root y pma se conservaron; no se restauraron permisos desconocidos ni se otorgó acceso global a las cuentas de aplicación.

Se creó además una copia lógica del estado actual de permisos: C:/xampp1/teko-private/backups/privileges-after-20261101.sql. Esto no equivale a una prueba de restauración de las bases educativas. La copia física previa se conserva para auditoría.

CHECK TABLE mysql.global_priv devuelve OK, pero mantiene una advertencia de tamaño de archivo. No se reparó ni reemplazó esa tabla global con cuentas y claves que sí funcionan. Esta advertencia debe investigarse en una revisión de la instalación XAMPP antes del piloto; no se declara completamente saneado el motor local. Las pruebas de la aplicación y de aislamiento SQL pasaron después de la reparación. Ningún archivo de las bases educativas fue reparado o sustituido.

Durante la provisión se corrigió también una ruta del archivo privado antes de activar las credenciales en la aplicación: quedó finalmente fuera de htdocs y se eliminó el directorio público vacío creado por esa ruta. Se comprobó posteriormente la ausencia del archivo por HTTP y su ACL privada.
