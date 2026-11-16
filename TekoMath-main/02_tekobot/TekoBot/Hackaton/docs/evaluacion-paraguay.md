# Evaluación de TekoBot para público paraguayo

## Resultados reproducibles

- `php tests/simulation_paraguay.php`: 6264 casos sintéticos, 261 conceptos, 2088 por registro (español neutro, español paraguayo y jopara).
- `php tests/simulation_1000.php`: 1000 regresiones complementarias.
- `php tests/pedagogy_test.php`: 62 subtemas; enseñanza, ejemplos, interrupciones, regreso a la lección y ausencia de dominio inventado.

La suite paraguaya verifica concepto, intención, existencia de respuesta, fórmula, conservación del progreso, repregunta y regreso a la lección. El informe JSON conserva los casos completos. No requiere claves ni llama al proveedor de IA. Las variantes regionales son sintéticas: no constituyen una muestra representativa de hablantes.

## Cambios

Se normalizan partículas coloquiales y algunas expresiones de incomprensión sin modificar el mensaje original guardado. Las acciones vacías de la lección no deben convertirse accidentalmente en consultas de conceptos. Se corrigió la prueba de ejemplos para comparar el texto localizado: antes exigía el original español dentro de una respuesta traducida a jopara.

## Antes de un despliegue nacional

1. Revisión matemática y pedagógica independiente de respuestas y ejemplos por docentes; registrar errores por tema y gravedad.
2. Revisión del jopara por hablantes y docentes de distintas comunidades. La prueba automatizada no juzga naturalidad ni calidad de la traducción.
3. Piloto consentido con estudiantes de diferentes cursos, lenguas y contextos; evaluar comprensión y accesibilidad, además de satisfacción.
4. Evaluar el proveedor de IA real con un conjunto reservado, respuestas esperadas revisadas y versiones registradas. No usar solo estas plantillas como evidencia de generalización.
5. Medir respuestas incorrectas, aclaraciones útiles, manejo de problemas incompletos, protección de soluciones y recuperación tras errores; investigar cada fallo crítico antes de publicar.
6. Verificar privacidad de menores, acceso a sesiones, carga concurrente, conectividad limitada y recuperación de servicios con responsables del proyecto.

Pasar estas regresiones no certifica preparación nacional ni entrena los pesos del modelo externo. Los ejemplos regionales del prompt deben conservar los datos del alumno y no inventar precios actuales.
