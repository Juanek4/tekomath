# Corrección de calificaciones en servidor

Quizzes: el servidor crea un intento ligado a la sesión, conserva preguntas y claves en PHP, recibe una opción por pregunta y no permite volver a calificarla. El guardado exige completar el intento, ignora aciertos/total enviados por el navegador y rechaza el segundo guardado. Los intentos vencen a las dos horas. Un inicio nuevo reemplaza al anterior en esa sesión; se debe evitar usar dos quizzes a la vez.

Misiones: el navegador envía opciones elegidas por etapa/paso. El servidor toma la definición guardada de la tarea, valida opciones y exige respuestas para todos los ítems corregibles. El porcentaje corresponde a aciertos en la primera respuesta de cada ítem. No se usan bonos, porcentajes ni conceptos de dominio enviados por el cliente. Las métricas de ayuda anteriores no están verificadas y no intervienen en esta calificación. La pantalla actualiza el porcentaje con el confirmado por el servidor y muestra el fallo de guardado.

Pruebas HTTP aprobadas: quizzes sin claves iniciales, respuestas corregidas, contadores adulterados ignorados y guardado duplicado rechazado; misiones propias guardadas con evidencia de opciones; permisos entre grupos conservados. Datos temporales eliminados.

Límites: esto no certifica ausencia de consulta de soluciones; las misiones aún entregan claves al cliente para feedback local. Las respuestas de misiones se envían al final y no constituyen un registro inviolable de la primera respuesta. Pendiente registrar cada intento de misión en servidor y retirar sus claves del cliente. No se recalcularon notas históricas sin evidencia. Diagnósticos antiguos de TekoBot y métricas de Arcade requieren revisión separada. Falta prueba visual integral.

## Actualización de intentos de misiones

Cada misión inicia un intento persistido en mission_attempts con identidad, tarea, copia de la definición, respuestas y vencimiento de dos horas. Las opciones se corrigen una por una con bloqueo transaccional. Se mantiene un identificador de petición para reintentos tras pérdida de respuesta. Las etapas deben resolverse en orden; se registran equivocaciones y la primera respuesta determina la nota. El cierre solo acepta un intento propio, vigente, completado y no finalizado. La nota se obtiene del historial del servidor, no del JSON de respuestas enviado al final.

Las vistas de alumno de tareas ya no entregan es_correcta ni feedback específico de opciones antes de elegir. El servidor devuelve únicamente la devolución de la opción elegida. El propietario docente conserva acceso a la definición para preparar sus clases.

Pruebas HTTP aprobadas con dos grupos: ausencia de claves en el inicio, rechazo de cierre incompleto, registro por ítem y reenvío idempotente, cierre utilizando evidencia del servidor y conservación de permisos. Datos temporales eliminados. Pendiente revisión visual de todas las plantillas y recuperación de una actividad tras recargar; el intento persiste pero la interfaz aún inicia uno nuevo al reabrir la misión. Los datos de pistas no se usan como evidencia verificada.

## Recuperación de misiones al reabrir

El inicio busca un intento vigente del mismo alumno y tarea antes de crear uno. La selección se serializa bloqueando la fila del alumno, de modo que dos solicitudes simultáneas no crean dos intentos activos nuevos. El servidor conserva la copia original de la misión y devuelve respuestas registradas y el primer ítem no resuelto. La interfaz recupera etapa, paso, cantidad de intentos, errores y ejercicios resueltos. Si todos los ítems ya están resueltos, abre el cierre para guardar la calificación. El vencimiento original de dos horas no se prolonga al reabrir.

Validación HTTP contra Apache/MySQL: mismo ID al reabrir antes de responder y después de cada respuesta, historial recuperado y siguiente ítem distinto al resuelto; corrección, guardado y aislamiento existentes siguen pasando. Datos temporales eliminados. Sintaxis PHP y JavaScript correctas.

Límites: falta prueba visual autenticada de todas las plantillas. El tiempo de actividad, pistas y autocorrecciones de la interfaz no se restauran como mediciones verificadas. No se probó carga concurrente sostenida ni expiración mediante reloj simulado. Un intento finalizado o vencido permite uno nuevo, por lo que la política de repetición/calificación definitiva requiere decisión pedagógica.

## Historial de oportunidades de misión

Se añadió mission_results: una fila por intento finalizado, con ID de intento único, alumno, docente, tarea, título, aciertos, total, porcentaje y fecha UTC. La inserción se realiza en la misma transacción que el cierre del intento y la actualización del resumen existente. No hay endpoint de edición de este historial. El resumen progreso_misiones sigue representando el último resultado; no se cambió retroactivamente su significado.

historial_misiones.php muestra 30 resultados por página, fechas de Paraguay y comparaciones primera/última/mejor. El alumno solo ve sus resultados; el docente solo ve resultados de sus propias tareas para alumnos actualmente asignados. Los vínculos de acceso están en ambos escritorios. No se elige una nota definitiva ni se importan resultados históricos sin evidencia. Los resultados se conservan aunque se elimine la tarea, pues se guarda su título; la política de retención y eliminación institucional sigue por definir.

Pruebas HTTP PASS: cierre duplicado rechazado, primera calificación conservada tras una segunda realización, dos oportunidades visibles, acceso de alumno y docente propietario, ausencia de datos del otro grupo. Limpieza de resultados, intentos y cuentas de prueba. PHP validado. Falta revisión visual, pruebas con varias páginas y optimización de comparaciones para gran volumen; la pantalla actualmente consulta el historial de cada misión mostrada.

## Rendimiento del historial de misiones

Se retiró la consulta individual de comparación por cada tarjeta. La página obtiene los resultados autorizados y después calcula primera, última, mejor nota y cantidad de intentos en una sola consulta agrupada para las combinaciones alumno/tarea/docente visibles. Se deduplican combinaciones repetidas antes de consultar. Los permisos se aplican antes de seleccionar las combinaciones y la consulta agrupada usa parámetros preparados.

Antes: hasta 32 consultas de lectura de datos por página de 30 resultados (conteo, listado y 30 comparaciones). Ahora: hasta 3 consultas de lectura de datos (conteo, listado y comparación conjunta); una página vacía no ejecuta la comparación. No se incluyen en ese conteo las tareas de inicialización de tablas y conexión. No cambió el diseño ni la regla de calificación.

Validación: sintaxis PHP y prueba HTTP de cuentas, dos grupos, dos oportunidades conservadas y acceso al historial aprobadas con MySQL local. Datos temporales eliminados. No es una medición de latencia ni una prueba de 600 conexiones concurrentes; siguen pendientes carga, paginación con gran volumen y análisis de planes SQL en producción.
