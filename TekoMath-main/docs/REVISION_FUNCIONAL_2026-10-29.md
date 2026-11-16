# Revisión funcional del 29 de octubre de 2026

Alcance: repetición de pruebas de las modificaciones recientes y revisión de funciones de estudio. No equivale a verificar exhaustivamente cada pantalla o cada plantilla.

Fallos corregidos:

1. Texto confirmado de una foto podía tratarse como una respuesta del taller anterior, o quedar en selección de tema. Se añadió image_problem como acción explícita, que inicia un problema nuevo y limpia rutas de respuesta antiguas. Se conservan evidencias previas del alumno, pero no el enunciado ni los intentos del problema anterior. Prueba PHP cubre seis estados de taller, aclaración de enunciado incompleto y limpieza de estados incompatibles.
2. El indicador de Arcade podía mostrar Progreso guardado mientras había otra instantánea en cola. Ahora distingue cambios pendientes y solo confirma al recibir el último acuse. Prueba de cola con respuestas diferidas verifica el mensaje y la bandera pendiente.

Regresiones repetidas: prueba pedagógica de 62 subtemas; evaluación de cuatro unidades; análisis del alumno; 1000 consultas locales. Pruebas HTTP reales de quizzes y misiones, cuentas, aislamiento de conversaciones, Live y progreso de Arcade aprobadas. Cuentas temporales limpiadas. Compilación TypeScript/Vite regenerada.

Pendientes funcionales concretos: búsqueda de videos del tutor no conectada; resolución manual de conflictos entre dispositivos de Arcade sin interfaz; persistencia y navegación de quizzes en dos pestañas; validación visual completa del lector y del envío de la foto; juegos completos en navegador, corpus de manuscritos y diagramas. Estas necesidades no se declaran corregidas por las pruebas de servicios. El navegador físico sigue pospuesto según lo acordado.
