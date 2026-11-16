# Recuperación de cuentas de TEKO Math

Fecha: 30/10/2026, Paraguay.

## Funcionamiento en localhost
No se encontró transporte de correo configurado. La recuperación implementada es asistida por coordinación, dentro de la consola administrativa existente. No se enviaron correos ni se cambiaron contraseñas de cuentas reales durante las pruebas.

1. La persona encuentra «¿Olvidaste tu contraseña?» en el acceso principal. La página pública explica cómo pedir un enlace a coordinación. No consulta ni revela si un correo está registrado.
2. El administrador entra a su consola habitual y usa «Recuperar cuenta de estudiante o docente». Debe verificar la identidad por un canal conocido e indicar el correo registrado. La confirmación del formulario deja explícita esta responsabilidad; no constituye una verificación automática de identidad.
3. El administrador genera un enlace privado y lo comparte manualmente. No puede consultar la contraseña vigente y no necesita elegir la nueva. La consola solo muestra el enlace al generarlo; la base guarda su hash SHA-256.
4. El enlace vence a los 30 minutos. Generar otro invalida los anteriores de la misma cuenta. La página captura el token en la sesión y redirige a una URL limpia; el token no aparece en el formulario.
5. La persona elige su contraseña, la confirma y guarda. El servidor actualiza el hash y aumenta session_version en una transacción, invalidando las sesiones previas. No inicia sesión automáticamente. Se mantiene la misma cuenta, rol, progreso y asignaciones.

## Archivos
- config/password_recovery.php: tabla password_reset_links y servicio de emisión, validación y consumo.
- recuperar.php: instrucciones públicas y formulario protegido de cambio de contraseña.
- admin.php: generación privada, protegida por autenticación administrativa vigente y CSRF, sin alterar la contraseña de la consola.
- index.php: acceso desde el formulario de inicio de sesión.
- 07_diseno_y_graficos/teko-ui.css: ajuste de casilla de confirmación para conservar el estilo y el ancho móvil.

## Controles
Tokens aleatorios de 256 bits; hash en base de datos; caducidad de 30 minutos; uso único; reemplazo de enlaces anteriores; vínculo al hash actual de contraseña; rechazo de cuentas deshabilitadas. Las lecturas con bloqueo serializan dos consumos o una sustitución concurrente. Las contraseñas se validan con la misma política de cuenta: al menos ocho caracteres y máximo de 72 bytes, y no se permite reutilizar la actual.

El formulario requiere CSRF de sesión y limita los fallos a cinco por enlace e IP durante cinco minutos, reutilizando el mecanismo persistente de AccountSecurity. No se limita a cinco recuperaciones globales por colegio/IP. La página usa no-store, no-referrer y una política CSP sin scripts, sin marcos y con formularios del mismo origen. Los errores inesperados se registran internamente.

La consola conserva su autenticación, caducidad y versión existentes; alumnos, docentes y visitantes sin sesión administrativa no pueden emitir enlaces. La casilla de identidad no sustituye esos controles.

## Pruebas
password_recovery_http_test.cjs PASS sobre cuentas temporales en MySQL local:
- Emisión desde consola y rechazo de emisión anónima, CSRF incorrecto y falta de confirmación de identidad.
- Enlaces sustituidos, vencidos o usados rechazados.
- Token ausente del formulario y almacenado como hash.
- Contraseña corta rechazada; contraseña nueva guardada; antigua rechazada y nueva aceptada en el login.
- Sesión anterior invalidada y rol docente conservado.
- Cuenta deshabilitada no recuperable por este flujo.
- Dos solicitudes simultáneas, en sesiones de navegador distintas, consiguen exactamente un cambio.
- Navegación desde login y formulario sin desbordamiento horizontal a 390 y 1365 píxeles. Captura móvil revisada visualmente en docs/qa-recovery.

pilot_accounts_http_test.cjs PASS después de los cambios: invitaciones, altas docentes, rechazo de reuso, registros de alumnos, asignaciones y aislamiento docente. PHP lint sin errores en los archivos PHP modificados. Se eliminaron las cuentas temporales y los enlaces asociados; las ejecuciones no alteraron credenciales reales ni la contraseña administrativa.

## Límites y próximos pasos
Esta recuperación cubre usuarios de la tabla usuarios (estudiantes y docentes), no la pérdida de la contraseña de la consola administrativa. El envío automático por correo queda pendiente de elegir y configurar un proveedor. Al publicar, los enlaces requieren HTTPS y un dominio accesible; un enlace localhost solo funciona en el equipo del servidor. Debe evitarse registrar tokens completos en logs de acceso del alojamiento: limpiar la URL del formulario no elimina la petición inicial de esos logs. Quedan pendientes la depuración periódica de enlaces vencidos y registros del limitador, la configuración de producción y pruebas de carga. No se modificaron historiales, notas ni asignaciones de cuentas reales.
