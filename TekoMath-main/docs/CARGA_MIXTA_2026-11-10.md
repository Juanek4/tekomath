# Prueba mixta local sobre Apache

Se amplió tests/restored_site_smoke.cjs con TEKO_RESTORE_MIXED=1 junto a TEKO_RESTORE_LOAD=1. El escenario crea 25 conversaciones y ejecuta diez rondas por cuenta. Cada ronda envía una consulta al tutor, recupera historial, abre una evaluación de seis preguntas, entrega las respuestas y comprueba el progreso guardado.

Se realizaron 1.250 operaciones de las rondas y 25 aperturas de conversación sin errores. Las respuestas se validaron como JSON con success=true; se verificó nota 100 y persistencia de un diagnóstico correcto por cuenta.

| Operación | Solicitudes | p95 | Máximo |
| --- | --- | --- | --- |
| Crear conversación | 25 | 70 ms | 70 ms |
| Chat | 250 | 178 ms | 232 ms |
| Historial | 250 | 138 ms | 176 ms |
| Abrir evaluación | 250 | 125 ms | 168 ms |
| Entregar evaluación | 250 | 132 ms | 241 ms |
| Consultar progreso | 250 | 120 ms | 188 ms |

Las rondas duraron 4.769 ms con 25 cuentas concurrentes. Es una prueba mixta breve y reproducible. No equivale a una sesión de clase sostenida ni mide capacidad para 600 estudiantes. Usa el motor local de tutoría, sin Gemini, datos pequeños y cliente/servidor en el mismo equipo. No incluye consultas docentes simultáneas ni seguimiento de CPU y memoria. Esos puntos continúan pendientes.

Se restauraron 27 tablas y 40 filas como base de las pruebas. Las bases y cuentas temporales se eliminaron. Ambas tablas de permisos devolvieron OK sin avisos al finalizar.

Evidencia privada en C:/xampp1/teko-private/backups/full-20261101-180808-76d842/restore-7064a44029/http-test-results.log.
