# Seguridad de diagnósticos y progreso de TekoBot

Fecha: 30/10/2026, Paraguay.

## Cambios
- api/progress.php exige sesión y obtiene la identidad desde portal_student_links; ignora student_id del navegador y solo admite GET. Los errores no revelan mensajes internos de SQL.
- api/diagnostic.php exige sesión. GET abre un intento vinculado a la sesión y cuenta, con token aleatorio y vencimiento de dos horas. No entrega correct_index. POST valida las cinco respuestas y calcula aciertos, porcentaje y duración en servidor. Ignora notas, totales e identidad enviados por el cliente. Solo consume el intento después de guardar correctamente, y rechaza reenvíos.
- config/diagnostic_questions.php conserva el banco y la clave en el servidor, dentro de la carpeta config ya protegida por Apache.
- views/diagnostic_view.php envía opciones elegidas y token; muestra la nota devuelta por el servidor. Elimina la corrección local y el supuesto guardado offline. Un fallo al cargar deshabilita el envío; un fallo al guardar conserva el formulario y comunica el error. Conserva las clases y estructura visual existentes.

## Verificación
PHP lint sin errores en API y vista. tests/tutor_identity_http_test.cjs PASS en HTTP real con dos cuentas temporales: acceso anónimo rechazado, progreso aislado, clave oculta, token ajeno rechazado, respuestas incompletas rechazadas, nota manipulada ignorada, nota persistida en cuenta correcta y reenvío rechazado. Regresión de chats, historial y ejercicio desde imagen PASS. Se eliminaron las cuentas temporales.

## Límites y pendientes
No recalcula diagnósticos antiguos porque sus respuestas históricas pueden no ser confiables. Se conserva el banco actual de cinco preguntas; pre-test y post-test todavía comparten preguntas. Hay un intento diagnóstico activo por sesión: abrirlo de nuevo invalida el anterior, también en otra pestaña. La duración es tiempo transcurrido del servidor, no tiempo de atención efectiva. Faltan auditoría de las demás rutas, pruebas de carga y ampliación/revisión pedagógica del banco. El token protege este envío; no se declara protección CSRF general de todo TekoBot.
