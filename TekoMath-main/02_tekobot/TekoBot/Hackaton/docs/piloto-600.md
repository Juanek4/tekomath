# Piloto con 600 estudiantes

La prueba breve se inicia desde «Comprobar comprensión con ejercicios» en el tutor. Hay tres ítems por cada una de las cuatro unidades actuales (12 ítems en total). La corrección es determinista; no se delega a un modelo externo. Las pistas se registran por separado. Las preguntas y respuestas que no tengan formato corregible no se cuentan como errores. Los resultados se guardan con la conversación y aparecen en el panel docente. Menos de dos respuestas independientes correctas activa apoyo más sencillo al retomar el tutor.

Alcance: es una comprobación inicial de la unidad, no una evaluación exhaustiva de los 62 subtemas. Los ítems se repiten; no interpretar las repeticiones como evidencia independiente ni como una calificación oficial. Se requiere ampliar el banco por subtema y revisar dificultad y respuestas equivalentes con docentes antes del piloto.

Prioridades para el piloto:

1. Validar el flujo real con cuentas de alumno y docente, incluidos cierre de sesión, cambio de dispositivo y recuperación de conversaciones.
2. Definir si 600 es el total de participantes o la concurrencia máxima. Medir la carga con cuentas de prueba y el servidor de destino; este trabajo no demuestra capacidad para 600 conexiones simultáneas.
3. Revisar cobertura, accesibilidad, jopara y comprensión de los enunciados con docentes y un grupo pequeño antes de ampliar la participación.
4. Preparar alojamiento, copias de seguridad, monitoreo de errores y recuperación de la base. XAMPP local no constituye un despliegue nacional validado.
5. Revisar autorización de acceso a todos los endpoints, privacidad de menores y consentimiento con responsables del piloto.
6. Medir finalización, pedidos de ayuda, errores por ítem, latencia y aprendizaje mediante una prueba diferente posterior.

Validación ejecutada: tests/assessment_test.php y tests/pedagogy_test.php. Falta verificación de extremo a extremo en navegador y prueba de carga en infraestructura de destino.
