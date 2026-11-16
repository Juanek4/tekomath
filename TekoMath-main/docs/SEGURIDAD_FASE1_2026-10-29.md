# Primera fase de seguridad de cuentas y sesiones

Fecha: 29/10/2026. Alcance: cuentas, sesiones y administración. No representa el cierre de toda la seguridad.

## Demos

Se retiró el sembrado automático de cuatro cuentas demo desde getDB. Una migración deshabilita solo las cuentas que coinciden en correo, nombre original y contraseña original de demostración. No borra usuarios, tareas, conversaciones ni resultados. En MySQL local se confirmaron cuatro cuentas deshabilitadas. Las sesiones anteriores a este cambio deben volver a iniciar sesión.

## Sesiones

Cookies HttpOnly, SameSite=Lax, Secure cuando PHP detecta HTTPS directamente, modo estricto y solo cookies. Rotación del identificador al iniciar sesión y limpieza del estado previo para no heredar privilegios de otra cuenta. Vencimiento tras 30 minutos sin peticiones autenticadas y máximo de ocho horas. Se comprueba existencia, rol, habilitación y versión de sesión de la cuenta en peticiones autenticadas. El polling cuenta como actividad; no es una medición de presencia humana. El cierre normal elimina la sesión.

## Contraseñas y acceso

Registro público exige mínimo ocho caracteres; se limita a 72 bytes por bcrypt. La consola usa un hash activo en console_credentials y permite cambiar contraseña verificando la actual, confirmación y mínimo de 12 caracteres. Cambiarla incrementa la versión e invalida los accesos administrativos anteriores. La clave inicial continúa válida hasta que el propietario la cambie; aparece aviso y no se eligió otra por él. El hash inicial de bootstrap sigue en código para nuevas instalaciones: debe reemplazarse por configuración de despliegue antes de publicar. No se habilitó recuperación de contraseña de alumnos/docentes en esta fase.

La limitación de intentos de login normal y administrativo persiste en MySQL por clase de acceso, identificador y dirección IP: cinco fallos dentro de la ventana activa bloquean durante cinco minutos. Otro navegador no reinicia el límite. No sustituye defensas contra abuso distribuido, límites por IP global ni monitoreo. No se confía en cabeceras de proxy; HTTPS y IP requieren revisión al desplegar tras un proxy.

## Incidente de prueba y corrección

Al probar cambio administrativo dentro de una transacción, la inicialización ejecutó CREATE TABLE y MySQL confirmó implícitamente la transacción. La contraseña temporal llegó a persistirse. Se restauró inmediatamente la contraseña original, se revocó la versión temporal y se modificó la inicialización para no ejecutar DDL repetido en la misma petición. La prueba repetida revirtió correctamente el cambio. Las pruebas HTTP posteriores confirmaron el acceso original y las invitaciones. No se dejó una clave desconocida.

## Evidencias

- tests/session_security_http_test.cjs: PASS para atributos de cookie, rotación, cuenta deshabilitada, caducidad inactiva/absoluta, logout y límite persistente entre navegadores. Usa cuenta temporal y limpia sus datos.
- tests/admin_password_test.php: PASS cambio de contraseña y versión, con rollback y preservación de la clave anterior.
- Pruebas HTTP de cuentas/invitaciones/asignaciones, tutor, tareas/calificaciones/historial y Live: PASS tras los cambios. Datos temporales eliminados.
- Validación PHP de los archivos modificados: PASS.

Pendiente: probar visualmente el formulario de cambio en móvil, revisar todos los endpoints y protección CSRF del login/logout, recuperación de contraseña, limpieza/retención de auth_failures y sesiones PHP, configuración segura de producción, protección web de archivos privados, backup/restauración y carga. No se declara la plataforma lista para publicación.
