# Pruebas completas de los seis juegos

Fecha: 29/10/2026. Entorno: Edge headless sobre Apache/MySQL locales, cuenta temporal creada mediante registro real y eliminada al terminar. Script reproducible: tests/arcade_all_games_browser_test.cjs (Node con --experimental-strip-types).

Resultados PASS:

| Juego | Tema de prueba | Recorrido |
|---|---|---|
| Triangle Forge | Seno | Preparación, inicio, pausa, error, corrección, tres rondas, cierre, guardado y recarga |
| Signal Sync | Coseno | Preparación, inicio, pausa, pareja errónea y corrección, parejas configuradas, cierre, guardado y recarga |
| Vector Launch | Tangente | Preparación, inicio, pausa, error registrado, cinco rondas, cierre, guardado y recarga |
| Pair Matrix | Cosecante | Preparación, inicio, pausa, pareja errónea y corrección, tres parejas, cierre, guardado y recarga |
| Graph Lab | Seno | Preparación, inicio, pausa, error registrado, cuatro rondas, cierre, guardado y recarga |
| Ratio Rush | Secante | Preparación, inicio, respuesta correcta, cierre por tiempo con reloj de prueba, guardado y recarga |

Cada partida produjo un único registro y al recargar no se añadió otro ni se reescribió la revisión de progreso. Los resultados comprobados contenían respuestas correctas. La prueba usa las definiciones fuente como referencia para seleccionar respuestas, sin modificar el estado del juego ni la puntuación desde el navegador.

Corrección funcional: GameSetupPage y GamePage ahora verifican supportedTopics. Una URL o una sesión incompatible no puede iniciar el juego bajo un tema que no implementa. Se reprodujo la inconsistencia Graph Lab/cotangente: antes utilizaba seno como sustituto. Ahora la preparación redirige al tema cotangente para elegir un juego compatible. No se implementaron funciones recíprocas nuevas ni se cambió el diseño.

Regresiones: 86 pruebas internas aprobadas; TypeScript y compilación Vite aprobadas; prueba de cola de sincronización aprobada; prueba real de conflicto entre dispositivos aprobada con carga del servidor y conservación del respaldo local. Datos temporales limpiados.

Durante la preparación se corrigieron errores de la automatización: selector ambiguo cos/fórmula, cantidades de rondas incorrectas, tema no soportado y espera del cambio de ruta React. No se presentan esos errores del test como fallos adicionales del producto. Se conservaron las reglas existentes: Vector Launch y Graph Lab avanzan después de una respuesta calificada, mientras los juegos de emparejamiento permiten corregir.

Límites: una configuración y tema por juego, no todas las combinaciones de temas/dificultades ni ejercicios propios. Reloj adelantado en Ratio Rush, sin esperar un minuto real. No se probó carga de 600 personas, teléfonos físicos, accesibilidad completa, cámara ni toda la precisión matemática del banco. El progreso sincronizado sigue basado en resultados del cliente y no es una nota certificada por servidor. Pendientes específicos: cobro diario integrado entre dispositivos, reinicio/abandono en todas las fases y preservación del estado intermedio de partidas al recargar.
