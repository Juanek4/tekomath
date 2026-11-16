# Cierre de pendientes funcionales del 29 de octubre de 2026

## Cambios

- Se retiró la búsqueda de videos de TekoBot: el chat ya no intercepta esas palabras como una función de búsqueda y el endpoint antiguo responde 410. El glosario y sus búsquedas de conceptos permanecen.
- Los seis juegos usan usePersistentGameState para conservar en sessionStorage el estado por cuenta, sesión de partida y campo. Incluye sesión, ronda, selecciones, pausa y datos locales. El inicio ofrece Continuar mi partida pendiente cuando la última partida sigue activa. No permite trasladar partidas en curso a otro dispositivo ni recuperarlas después de cerrar la pestaña; el progreso final sí sigue sincronizándose con servidor.
- Quizzes mantienen hasta diez intentos abiertos independientes por sesión, identificados por quiz_id; se limpian vencidos y guardados al iniciar otro. Una segunda pestaña ya no reemplaza la primera.
- Las opciones antiguas de texto/foto de preparación de Arcade dirigen al flujo Mi tarea. Se eliminó la construcción de ejercicios temporales sin respuesta. Las sesiones de ejercicios preparados conservan su definición completa.
- Triangle Forge conserva primero el ejercicio propio confirmado. Vector Launch y Ratio Rush incluyen la definición propia al preparar sus ejercicios. Mi tarea recomienda Triangle Forge y Ratio Rush, y Vector Launch solo cuando puede construir el desafío con ese enunciado. Los juegos de emparejamiento y gráficas siguen para práctica general.
- El tutor avisa si se intenta subir una foto durante otra operación, en lugar de descartarla silenciosamente.

## Verificaciones

- Seis juegos: inicio, pausa donde aplica, corrección, recarga en estado de feedback, continuación y cierre; resultado único y servidor sin reescritura al recargar. Retorno al juego desde Inicio incluido en la prueba ampliada.
- Recompensa diaria: tres aciertos de seno habilitan cobro; 30 XP añadidos una vez; otra instancia de navegador sin caché muestra recompensa ya cobrada.
- Fotos: OCR real en Arcade, revisión, edición y juego con un problema distinto del banco (5/13) corregido correctamente. Enunciado incompleto no habilita partida. TekoBot reconoce, permite editar y persiste el enunciado confirmado en un taller real.
- Quizzes: segundo intento abierto mientras se completa y guarda el primero; el segundo continúa aceptando respuestas. Persisten rechazo de contadores falsificados y guardado duplicado.
- Informes: más de 30 resultados de prueba comprueban primera y segunda página y aislamiento docente. Los registros adicionales se insertaron como fixtures sintéticos para probar paginación, no como aprendizaje real.
- 86 pruebas internas de Arcade aprobadas; 62 subtemas de pedagogía y prueba de foto como problema aprobados; compilación TypeScript y Vite aprobada. Cuentas, intentos y resultados temporales eliminados.

## Límites

Los tests de interfaz esperan el cambio de ruta y la finalización de creación del chat; se corrigieron esperas de la automatización que inicialmente dieron falsos fallos. No se declara verificación exhaustiva de cada tema, dificultad, plantilla o manuscrito. El estado intermedio de juegos está en la pestaña, no en servidor. No se garantiza coordinación en tiempo real de dos partidas abiertas ni recuperación con almacenamiento del navegador bloqueado. Las métricas del juego siguen originándose en cliente. Seguridad, carga, privacidad, respaldos y teléfono físico son trabajos separados pendientes. No se eligió automáticamente una calificación definitiva entre oportunidades.
