# Auditoría inicial de preparación del piloto TEKO Math

Fecha: 28/10/2026. Fecha límite asumida: 31/01/2027 (95 días). Participantes previstos: 600. Concurrencia, instituciones, edades, presupuesto, equipo y alojamiento todavía por definir.

## Dictamen

La plataforma es un prototipo funcional con componentes y pruebas aprovechables. No hay evidencia suficiente para habilitar un piloto de 600 estudiantes con datos reales todavía. Primero deben cerrarse identidad, permisos, integridad de resultados y operación. Esta es una revisión estática de rutas críticas y evidencias de la sesión, no una auditoría exhaustiva de seguridad, una prueba de penetración, una certificación legal ni una prueba de carga. No se han reproducido ataques contra datos reales. No se cambió código funcional durante esta auditoría.

P0: bloquea participación con datos reales. P1: obligatorio para los módulos incluidos en el piloto. P2: posterior o prescindible si se excluye el módulo. Toda tarea necesita responsable, evidencia de prueba y fecha; marcar una casilla no sustituye la verificación.

## Hallazgos comprobados en código

| ID | Prioridad | Evidencia | Necesidad y criterio de cierre |
|---|---|---|---|
| H01 | P0 | registro_process.php acepta rol docente desde POST | Aprobar docentes mediante invitación/administración. Un registro público no puede otorgarse acceso docente. |
| H02 | P0 | config/database.php crea cuentas demo con contraseña 1234 y usa root | Instalación separada de la ejecución normal, cuentas demo deshabilitadas en producción y usuario SQL con permisos mínimos. |
| H03 | P0 | api/diagnostic.php toma student_id de JSON; upload_image.php no exige sesión en su entrada | Inventariar todos los endpoints; exigir identidad autenticada y autorización por recurso; probar accesos anónimos y entre cuentas. |
| H04 | P0 | usuarios en teko_math; learning_sessions referencia students en kyhyjey_db | Vincular o unificar identidades con migración. Crear un alumno nuevo y demostrar que su sesión funciona sin errores de clave foránea ni mezcla de personas. Igualar números de ID no demuestra identidad. |
| H05 | P0 | api_progreso.php acepta aciertos y total del cliente; diagnostic.php acepta correct_answers | Corregir con respuestas del servidor. Alterar el contador enviado no debe cambiar la calificación. |
| H06 | P0 | progressService.ts usa STORAGE_KEY teko-juegos-progress-v1 | Separar caché por cuenta y guardar evidencia en servidor. Cambiar de cuenta/dispositivo y probar aislamiento y recuperación. |
| H07 | P1 | docente_tutor_analysis.php consulta últimos 500 chats globales | Paginación, búsqueda y filtros por grupo/alumno/tema. Con 600 alumnos, ninguno debe desaparecer por un límite global silencioso. |
| H08 | P0 | panel y APIs docentes consultan alumnos globalmente | Definir institución, curso, sección y pertenencia docente; cada docente solo accede a su ámbito autorizado. |
| H09 | P1 | assessment_service.php contiene 3 ejercicios por cada una de 4 unidades | Banco revisado por subtema y formas paralelas. Una repetición no debe contarse como evidencia nueva de dominio. |
| H10 | P1 | student_analysis.php usa reglas y cuenta feedback correcto | Evitar duplicar evidencia y relacionar cada acierto con un intento único. Validar con respuestas correctas, incorrectas, dudas y reenvíos. |
| H11 | P1 | simulaciones usan plantillas y resolver local | Evaluar también el proveedor de IA real y preguntas reservadas revisadas por docentes. No presentar 7264 pruebas sintéticas como 7264 estudiantes o entrenamiento de pesos. |
| H12 | P1 | modificaciones en src, dist y welcome-layout.css | Compilación reproducible y único origen de estilos; artefacto publicado debe corresponder a la versión probada. No depender de parches manuales en dist. |
| H13 | P1 | móvil probado con HTML y APIs simuladas | Probar recorrido autenticado en teléfonos reales, teclado virtual, fotos, giros, pérdida de red y retorno al chat. |
| H14 | P0 | búsqueda en rutas críticas no encontró CSRF, regeneración de sesión ni limitador | Revisar y aplicar defensa CSRF, cookies de sesión seguras, rotación al entrar y límites de intentos/coste. Confirmar configuración efectiva del servidor; ausencia en estos archivos no prueba ausencia global. |
| H15 | P1 | progreso tiene errores predeterminados y métricas locales | Separar datos demostrativos de observaciones reales. Un alumno nuevo debe aparecer sin diagnóstico inventado. |

## Lista completa de trabajo por área

### Alcance y organización
- [ ] P0 Definir si 600 es población total o conexiones simultáneas, número de colegios, cursos, turnos y docentes.
- [ ] P0 Elegir un recorrido mínimo: acceso, diagnóstico, actividad, tutor, comprobación y reporte docente.
- [ ] P1 Definir qué módulos se habilitan; ocultar funciones sin validar. Live y OCR pueden excluirse inicialmente.
- [ ] P1 Asignar responsable técnico, pedagógico, de operación y de coordinación escolar; acordar presupuesto y disponibilidad.
- [ ] P1 Congelar alcance y disponer de una lista de incidencias con prioridad, responsable y evidencia de cierre.

### Acceso y seguridad
- [ ] P0 Resolver H01-H08 y H14; probar denegación por defecto y autorización en cada petición.
- [ ] P0 Eliminar credenciales demo, revisar secretos sin publicarlos y separar desarrollo/pruebas/producción.
- [ ] P0 Impedir descarga pública de SQLite, .env, copias SQL, logs, informes con datos y archivos temporales.
- [ ] P0 Validar métodos, tamaños y tipos de entrada; escapar contenido en HTML y revisar uso de innerHTML.
- [ ] P0 Política de contraseñas y recuperación de acceso, con procedimiento seguro para menores sin correo propio.
- [ ] P0 HTTPS, cookies y sesión con caducidad; cierre de sesión real y aislamiento en equipos compartidos.
- [ ] P1 Límite de acceso y consumo de IA por cuenta; controles de concurrencia e idempotencia contra doble envío.
- [ ] P1 Revisar dependencias, CORS, cabeceras y rutas de carga; validar uploads por tipo, tamaño y dimensiones.

### Datos y persistencia
- [ ] P0 Identidad única y pertenencia a grupo; migraciones versionadas y probadas sobre una copia.
- [ ] P0 Guardado de tareas, respuestas, intentos, pistas y resultados en servidor; el navegador solo como caché.
- [ ] P0 Copias automáticas cifradas o protegidas y restauración comprobada en otro entorno.
- [ ] P1 Fechas consistentes y presentación en America/Asuncion; identificar UTC en registros técnicos.
- [ ] P1 Manejo de reintentos, cortes y conflicto entre pestañas/dispositivos sin perder ni duplicar respuestas.
- [ ] P1 Definir conservación, eliminación, exportación y corrección de datos con responsables institucionales.

### Pedagogía y evaluación
- [ ] P1 Delimitar cursos y objetivos; mapa objetivo → contenido → actividad → evidencia → reporte.
- [ ] P1 Revisión matemática por docentes; controlar fórmulas, unidades, claves y soluciones equivalentes.
- [ ] P1 Ampliar pruebas por subtema con dificultad y rúbrica; equivalencia de fracciones, decimales y notación local.
- [ ] P1 Diagnóstico inicial y evaluación posterior diferentes; registrar apoyo y exposición previa a cada ítem.
- [ ] P1 Separar práctica, autoafirmación, respuesta asistida y demostración independiente.
- [ ] P1 No asignar una etiqueta global al alumno a partir de pocas preguntas; explicar incertidumbre y permitir revisión docente.
- [ ] P1 Revisar jopara con hablantes y docentes; conservar elección del idioma y validar terminología.
- [ ] P1 Evaluar problemas incompletos, equivocaciones típicas, frustración y preguntas fuera del alcance.

### IA y TekoBot
- [ ] P0 Proteger claves y acceso al proveedor; límites y alerta de gasto, timeout y contingencia honesta.
- [ ] P1 Evaluar respuestas reales con conjunto reservado, rúbrica de exactitud y pruebas de no revelar soluciones activas.
- [ ] P1 Corregir análisis duplicado/estado antiguo, aislar cada tema y relacionar evidencias con intentos únicos.
- [ ] P1 Comprobar que el modo sin conexión no simule éxito ni OCR ni guardado remoto.
- [ ] P1 Registrar versión de modelo, prompt y banco utilizados sin guardar secretos o datos innecesarios.
- [ ] P1 Permitir reportar una respuesta errónea y revisarla antes de usarla para decisiones pedagógicas.

### Experiencia, celulares y accesibilidad
- [ ] P1 Recorridos reales a 320/360/390/768 px y escritorio, zoom 200%, teclado y orientación horizontal.
- [ ] P1 Contraste, foco, nombres accesibles, orden de lectura, formularios y errores asociados a sus campos.
- [ ] P1 Juegos de arrastre con alternativa accesible; fórmulas y gráficos con explicación textual.
- [ ] P1 Corregir solapamientos, scroll anidado, menús, selector de idioma y cabecera en el iframe real.
- [ ] P1 Mensajes consistentes: guardando, guardado, sin conexión, error y reintento; no confundir local con sincronizado.
- [ ] P1 Probar red lenta, interrupciones, recarga y equipos compartidos. Reducir imágenes, fuentes y dependencias externas.

### Panel docente y grupos
- [ ] P0 Docentes aprobados y alumnos asignados por grupos; controles de acceso en consulta y modificación.
- [ ] P1 Filtros, paginación, búsqueda y exportación por curso/alumno/fecha; sin cortes silenciosos.
- [ ] P1 Mostrar quién no empezó, quién perdió conexión, quién necesita apoyo y la evidencia concreta.
- [ ] P1 Asignación y entrega de tareas con fechas y estados inequívocos; revisar tareas duplicadas/demo.
- [ ] P1 Revisión humana de análisis automático, historial de cambios y soporte para corrección de errores.

### Infraestructura y rendimiento
- [ ] P0 Dominio, alojamiento y base de producción; no exponer el XAMPP de desarrollo como solución validada.
- [ ] P0 Medir conexiones simultáneas previstas, carga realista, memoria PHP, consultas SQL e índices.
- [ ] P1 Probar carga escalonada y sostenida, incluyendo chat, carga de imágenes y polling de Live si se habilitan.
- [ ] P1 Evitar que las sesiones PHP o peticiones largas de IA bloqueen otras solicitudes; medir, no asumir.
- [ ] P0 Monitoreo de errores, salud y pérdida de guardados; alertas con responsable y procedimiento.
- [ ] P1 Presupuesto estimado con uso medido: alojamiento, almacenamiento, IA, copias y soporte.
- [ ] P1 Despliegue repetible, entorno de preproducción, rollback y congelación de cambios antes del piloto.

### Privacidad, instituciones y operación
- [ ] P0 Acordar con instituciones y asesoría competente la base y autorizaciones aplicables al tratamiento de datos de menores; no considerar este informe asesoramiento legal.
- [ ] P0 Inventario de datos, aviso comprensible, finalidad, responsables, proveedores externos y retención.
- [ ] P0 Informar qué mensajes/fotos se envían al proveedor de IA; minimizar datos personales.
- [ ] P1 Guía breve para alumnos y docentes, incorporación de cuentas y ensayo de una clase completa.
- [ ] P1 Canal de soporte, responsable durante las sesiones, protocolo de incidentes y alternativa si falla internet.
- [ ] P1 Acuerdos de horario, dispositivos, conectividad, autorizaciones institucionales y asistencia.
- [ ] P1 Encuesta de comprensión/usabilidad y registro de abandonos sin atribuirlos automáticamente a falta de capacidad.

## Calendario propuesto

| Fechas | Resultado exigido |
|---|---|
| 28 oct–8 nov | Alcance cerrado, responsables, inventario de datos/endpoints y lista de bloqueos. |
| 9–29 nov | Identidad, permisos, grupos, corrección y persistencia; restauración inicial. |
| 30 nov–20 dic | Banco revisado, tutor y reportes consistentes, compilación y experiencia móvil. |
| 21 dic–10 ene | Ensayo con 15–30 participantes autorizados y pruebas de red/carga; ajustar por disponibilidad escolar y fiestas. |
| 11–24 ene | Ampliación controlada a 60–100, resolución de incidencias y ensayo operativo. |
| 25–31 ene | Congelar cambios y decidir habilitación de los 600 según evidencias; no por calendario solamente. |

Los grupos son una propuesta, no compromisos confirmados. Si el 31 de enero es la fecha de inicio en lugar de la fecha de preparación, reservar la última semana para operación y adelantar la decisión.

## Puerta de salida: habilitar o posponer

- Ningún P0 abierto, verificado por prueba o revisión independiente apropiada.
- Dos alumnos y dos docentes de grupos distintos no pueden leer ni alterar datos ajenos.
- Un alumno nuevo completa el recorrido y recupera su avance al cambiar de dispositivo.
- Corrección en servidor y resultados por intento comprobados, sin porcentajes fabricados.
- Revisión docente de contenidos y evaluación; errores graves corregidos.
- Respaldo restaurado, rollback ensayado y personal de soporte disponible.
- Capacidad probada para la concurrencia acordada, con margen aprobado. Como objetivos iniciales a acordar: menos de 1% de errores inesperados, ninguna pérdida de respuestas confirmadas y p95 de operaciones normales menor de 2 s; medir IA por separado y definir su timeout aceptable.
- Prueba en teléfonos reales y conectividad escolar; no bloqueo del recorrido principal.
- Autorizaciones institucionales, información a participantes y tratamiento de datos definidos.

## Evidencia y límites

Se inspeccionaron autenticación/registro/sesión, configuración SQL, progreso y tareas, entrada de Live, APIs de diagnóstico y fotos, persistencia de Arcade, esquema del tutor y reporte docente. Los archivos de pruebas existentes son evidencia de trabajo previo, no garantía de ejecución actual. En esta auditoría no se realizó recorrido autenticado completo, carga, revisión lingüística humana ni validación del proveedor externo. La lista cubre las áreas necesarias conocidas; una inspección dinámica puede revelar nuevos problemas.

Referencias de criterios técnicos: https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html y https://www.w3.org/TR/WCAG22/ .
