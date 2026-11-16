# Protección de archivos privados en Apache

Fecha: 29/10/2026. Archivo de reglas: .htaccess en la raíz del proyecto. Requiere Apache 2.4, mod_rewrite y permisos AllowOverride adecuados. No modifica las autorizaciones SQL ni borra archivos.

Se deshabilitaron listados de directorio. Se deniega por HTTP el acceso a config, database, services, prompts, pruebas, scripts, temporales, documentación interna, dependencias Node, fuente TypeScript, directorios de implementación de Jopamath y modelos. También se restringen archivos de entorno, metadatos de repositorio, SQLite y sus auxiliares, SQL y copias, logs, mapas de código, Python/TypeScript, configuración y claves/certificados privados. Los informes Word permanecen disponibles por herramientas de archivos locales, pero no para descarga anónima desde Apache.

Los includes PHP siguen funcionando: la restricción afecta peticiones HTTP, no lectura interna del servidor. Los recursos públicos permanecen en assets y dist; currículo y traducciones públicos del tutor usan sus JavaScript generados, no los JSON privados. Los PDFs educativos no se bloquean globalmente.

Verificación: tests/private_files_http_test.cjs PASS para 14 rutas privadas reales, nueve recursos públicos y rechazo del listado de carpeta. Las reglas deben comprobarse de nuevo en el alojamiento definitivo; no se aplican por sí solas a Nginx, Vite, servicios serverless o servidores con AllowOverride deshabilitado.

Pendientes: revisar qué archivos se empaquetan para publicación (no basta esconderlos por HTTP), mover secretos y SQLite fuera del directorio público, política de copias y restauración, configuración de servidor y cabeceras, y auditoría de endpoints restantes. No se declara que estas reglas cubran nombres de copias arbitrarias ni otras rutas futuras; la publicación debe usar una lista explícita de archivos permitidos.

Regresión posterior a las reglas finales: PASS en los seis juegos (incluye recuperación de partida y recompensa diaria), ambos flujos de fotos con OCR y ejercicio propio, calificación de cuestionarios en servidor, paginación docente, aislamiento de tareas/materiales/progreso, invitaciones y asignaciones, rechazo de cambios de rol y validación CSRF en las rutas cubiertas. Las cuentas temporales se eliminaron. Esta verificación corresponde a los escenarios automatizados de estos scripts, no a una auditoría exhaustiva de todos los endpoints.

