# Seguridad de rutas y conversaciones de TekoBot

Fecha: 30/10/2026, Paraguay.

## Alcance implementado
Se revisaron las rutas activas de sesiones, mensajes, historial, ejercicios, progreso y diagnósticos. No se declara terminada toda la seguridad de la plataforma.

| Ruta | Métodos | Protección |
|---|---|---|
| sessions.php | GET, POST | Sesión, identidad de servidor, CSRF en escritura, tipos y acciones validados, propiedad antes de borrar |
| chat.php | POST | Sesión, CSRF, conversación propia, JSON válido, mensajes de hasta 12.000 caracteres, revisión de estado y contexto reconstruido en servidor |
| history.php | GET | Sesión, conversación propia por ID o UUID, parámetros validados, máximo de 100 mensajes |
| exercises.php | GET | Sesión, filtros validados, selección explícita de columnas públicas, máximo de 200 ejercicios |
| progress.php | GET | Sesión e identidad de servidor; errores internos no expuestos |
| diagnostic.php | GET, POST | Sesión, CSRF en POST, token propio del intento, banco y corrección de servidor |
| security.php | GET | Sesión; entrega token de petición e identidad de cuenta para separar cachés |

Las rutas activas rechazan métodos ajenos a su contrato con 405. Las respuestas llevan Cache-Control: no-store y X-Content-Type-Options: nosniff. Se retiraron las cabeceras de acceso CORS abierto. api_guard.php centraliza los controles y las respuestas JSON: 401 por falta de sesión, 403 por CSRF inválido, 400 por JSON inválido, 413 por solicitud mayor a 64 KiB, 415 por formato incorrecto y 422 por datos inválidos. La corrección diagnóstica conserva también su token de intento.

Los errores inesperados se registran internamente y devuelven un mensaje genérico, sin incluir excepciones SQL o rutas. Una conversación ajena o inexistente devuelve 404; un conflicto de revisión devuelve 409. El control de acceso ya presente se conservó y se probó para lectura, creación con UUID existente, escritura y eliminación. La eliminación verifica propiedad antes de borrar y usa una transacción para estado, mensajes y sesión.

El listado de ejercicios ya no usa SELECT *: omite expected_value y pistas internas. No se cambió el banco de 372 preguntas ni se publicaron sus claves. Las peticiones del navegador incorporan X-Teko-CSRF mediante assets/js/api-security.js, limitado a las tres rutas de escritura de este tutor en el mismo origen. Se comparte el token de sesión con las otras APIs de la plataforma para no invalidarlas.

## Historial local e interfaz
Antes, las copias de conversaciones usaban claves globales kyhyjey_* sin dueño verificable. Ahora el arranque consulta la cuenta de la sesión y usa el prefijo teko-tutor:user:ID:. Sin identidad verificada, se usa un espacio temporal de invitado en sessionStorage. La preferencia general de idioma puede seguir compartiéndose; las conversaciones no.

No se importan las copias globales antiguas porque no se puede atribuir su dueño con seguridad. No se borraron ni migraron automáticamente esos archivos de navegador. Las conversaciones del servidor siguen recuperándose normalmente. Si no se puede verificar la cuenta al abrir la página, no se abre su caché persistente anterior: es una limitación deliberada para evitar atribuirlo al siguiente usuario.

Las vistas previas se rellenan con textContent, para impedir que mensajes o títulos se interpreten como HTML. La eliminación de un chat guardado solo se refleja visualmente después de confirmación del servidor; un fallo conserva el elemento y comunica el problema. Un rechazo explícito de identidad, permisos o validación no se convierte silenciosamente en respuesta local. La caída de red conserva el comportamiento de trabajo local dentro del espacio correspondiente.

## Verificación
- tutor_identity_http_test.cjs: PASS con dos cuentas temporales. Cubre las siete rutas activas, métodos incorrectos, acceso anónimo, token ausente o ajeno, JSON inválido, formatos y tipos inválidos, límites, claves de ejercicios ocultas, suplantación de student_id, intento de reutilizar UUID ajeno, escritura y eliminación ajenas rechazadas, historial y estado del dueño intactos, eliminación propia, notas diagnósticas y prueba de seis preguntas del chat persistida.
- tutor_cache_browser_test.cjs: PASS en Edge sin interfaz. Cambiar entre dos cuentas dentro del mismo navegador mantiene separadas las listas locales; la caché global antigua no aparece. Una vista previa con etiqueta img se muestra como texto. Un rechazo 403 de creación se comunica sin activar un chat offline.
- assessment_bank_browser_test.cjs: PASS después de activar CSRF. Selección de 62 subtemas y guardado real de seis respuestas, a 1365 px e index.php y a 390 px e index.html.
- photo_flows_browser_test.cjs: PASS en ambos lectores de imágenes y en el ejercicio propio de Arcade, incluida confirmación y persistencia del taller en TekoBot.
- assessment_bank_test.php y student_analysis_test.php: PASS. Claves, pistas y análisis por subtema conservados.
- private_files_http_test.cjs: PASS, 15 rutas privadas denegadas y nueve recursos públicos accesibles.
- PHP lint y node --check sin errores en los archivos revisados. Las cuentas temporales se eliminaron.

## Límites y pendientes
La separación de cachés evita la mezcla en la interfaz normal; no cifra el almacenamiento ni lo protege de una persona con acceso a las herramientas del navegador o al equipo. No elimina de una pestaña ya abierta lo que el usuario anterior ya vio; los siguientes envíos quedan sujetos a los tokens y la sesión vigente. El aislamiento en servidor es independiente de la caché del navegador.

Las pruebas HTTP se realizaron con MySQL local; no se certifica aquí el fallback SQLite completo. La lista de conversaciones sigue sin paginación y carga estados individuales; queda pendiente optimización y prueba de carga. Las rutas retiradas youtube.php y upload_image.php permanecen sin procesamiento funcional (410; la carga antigua además exige sesión).

Siguen pendientes la recuperación de contraseñas, credenciales y configuración definitivas de producción, revisión de las demás áreas de la plataforma, límites de frecuencia/costos del tutor y proveedores, pruebas de carga para el piloto, copias/restauración y despliegue HTTPS. Estos cambios no equivalen a una auditoría externa ni a declarar la plataforma lista para publicar.
