# Banco de evaluaciones de TekoBot

Fecha: 30/10/2026, Paraguay.

## Cobertura

372 preguntas propias, seis por cada uno de los 62 subtemas presentes en curriculum.json. Se incluyen los temas de ampliación que ya ofrece el tutor. Cada pregunta tiene cuatro opciones, una clave en servidor y una pista. Algunas actividades son variantes numéricas de la misma habilidad; no representan 372 habilidades distintas.

| Unidad | Subtemas | Preguntas |
|---|---:|---:|
| Funciones | 13 | 78 |
| Funciones trigonométricas | 27 | 162 |
| Línea recta | 15 | 90 |
| Análisis combinatorio | 7 | 42 |

## Cambios funcionales

- assessment_bank.json es el banco compartido, privado por las reglas de Apache. assessment_bank.php lo carga y filtra por unidad/subtema. El generador reproducible es scripts/build_assessment_bank.py; no usa servicios externos ni entrena un modelo.
- En el chat, «Comprobar comprensión con ejercicios» usa las seis preguntas del subtema seleccionado. Se responde con el número de opción, de 1 a 4, para permitir evaluar también conceptos. Preguntar o escribir texto inválido no cuenta como error. Las pistas siguen separándose de los aciertos autónomos. Al terminar se informa total de aciertos y aciertos sin pista.
- Las pruebas nuevas registran subtema y versión del banco. Las pruebas antiguas de tres ejercicios que estaban en curso conservan su corrección anterior hasta terminar o salir.
- El menú de ambos puntos de entrada (index.php e index.html) incorpora «Evaluarme por tema». Se integró la evaluación, cuya antigua vista PHP no estaba incluida en el punto de entrada actual. El módulo diagnostic.js crea el diálogo, conserva estilos comunes y ofrece selección de las cuatro unidades y los 62 subtemas.
- El diagnóstico entrega seis preguntas del subtema, sin claves ni pistas. El intento conserva en sesión los identificadores esperados, unidad y subtema. El servidor corrige y guarda la identidad de la sesión; la nota del navegador se ignora. La vista muestra éxito solo tras guardar.
- Los resultados diagnósticos nuevos incluyen unidad y subtema en details_json; no se cambian diagnósticos históricos. Los resultados de pruebas del chat muestran el nombre del subtema al docente. El umbral de apoyo se calcula por proporción (menos de dos tercios de aciertos sin pista), y una prueba de otro subtema no altera el apoyo del actual.

## Verificación realizada

- assessment_bank_test.php: PASS en los 62 subtemas y 372 preguntas; cobertura, claves válidas, cuatro opciones distintas, pistas, respuestas correctas/incorrectas, dudas sin penalización, resultados por subtema y compatibilidad con pruebas antiguas.
- assessment_test.php y student_analysis_test.php: PASS; calificación, pistas, salida y retorno a la lección, apoyo proporcional y aislamiento por subtema.
- pedagogy_test.php: PASS en los 62 subtemas; flujo de enseñanza y consultas.
- tutor_identity_http_test.cjs: PASS con dos cuentas reales temporales; selección por subtema, nota 0 y 100, seis respuestas del chat guardadas y recuperadas, claves ocultas, token, reenvío, aislamiento entre cuentas y regresión de conversaciones e imagen.
- assessment_bank_browser_test.cjs: PASS en Edge sin interfaz; selección de los 62 subtemas con seis preguntas cada uno. Guardado real y ausencia de desbordamiento horizontal del diálogo a 1365 px con index.php y 390 px con index.html. Sin errores JavaScript de página. No equivale a prueba en teléfono físico.
- private_files_http_test.cjs: PASS; el banco completo responde 403 por HTTP, junto con otras 14 rutas privadas. Nueve recursos públicos siguen accesibles.
- Las cuentas temporales usadas en HTTP y navegador se eliminaron. PHP lint y node --check sin errores en los archivos revisados.

## Alcance pendiente

La cobertura mínima está implementada. Un banco fijo de seis preguntas por tema todavía requiere revisión pedagógica docente y más variedad para comparar pruebas iniciales y finales sin repetir preguntas. El diagnóstico continúa permitiendo un intento activo por sesión; cambiar tema o abrirlo nuevamente reemplaza el intento anterior. El banco requiere servidor autenticado para calificarse; no se publica la clave para evaluación offline. No se modificaron permisos ni funciones de Arcade. La comprobación automática de calificación verifica el motor contra el banco; no sustituye la revisión matemática independiente de cada enunciado.

## Detalle por subtema

### Funciones

- Introducción a las funciones: 6 preguntas.
- Concepto de función: 6 preguntas.
- Clasificación de las funciones: 6 preguntas.
- Características de una función: 6 preguntas.
- Tipos de función: 6 preguntas.
- Función lineal: 6 preguntas.
- Función constante: 6 preguntas.
- Función exponencial: 6 preguntas.
- Función logarítmica: 6 preguntas.
- Funciones trigonométricas: 6 preguntas.
- Variación de las funciones trigonométricas: 6 preguntas.
- Función módulo: 6 preguntas.
- Función parte entera: 6 preguntas.

### Funciones trigonométricas

- Ángulo y sistema de medición: 6 preguntas.
- Grados y radianes: 6 preguntas.
- Triángulo rectángulo y sus elementos: 6 preguntas.
- Seno: 6 preguntas.
- Coseno: 6 preguntas.
- Tangente: 6 preguntas.
- Cosecante, secante y cotangente: 6 preguntas.
- Aplicación en problemas: 6 preguntas.
- Funciones trigonométricas en coordenadas cartesianas: 6 preguntas.
- Signos en los cuatro cuadrantes: 6 preguntas.
- Circunferencia trigonométrica y líneas de seno, coseno y tangente: 6 preguntas.
- Relaciones entre funciones de un mismo ángulo: 6 preguntas.
- Relaciones entre ángulos y ángulo de referencia: 6 preguntas.
- Valores exactos de ángulos notables: 6 preguntas.
- Resolución de triángulos oblicuángulos: 6 preguntas.
- Función seno y su gráfica: 6 preguntas.
- Función coseno y su gráfica: 6 preguntas.
- Función tangente, dominio y asíntotas: 6 preguntas.
- Amplitud: 6 preguntas.
- Período y frecuencia: 6 preguntas.
- Transformaciones de las gráficas: 6 preguntas.
- Seno inverso · arcsen (sen⁻¹): 6 preguntas.
- Coseno inverso · arccos (cos⁻¹): 6 preguntas.
- Tangente inversa · arctan (tg⁻¹): 6 preguntas.
- Función cotangente: 6 preguntas.
- Función secante: 6 preguntas.
- Función cosecante: 6 preguntas.

### Línea recta

- Geometría, Álgebra y Geometría Analítica: 6 preguntas.
- Lugar geométrico: 6 preguntas.
- Distancia entre dos puntos: 6 preguntas.
- Punto medio de un segmento: 6 preguntas.
- Pendiente de una recta: 6 preguntas.
- Pendiente conociendo dos puntos: 6 preguntas.
- Rectas paralelas: 6 preguntas.
- Rectas perpendiculares: 6 preguntas.
- Ángulo entre rectas: 6 preguntas.
- Ecuación de la recta: 6 preguntas.
- Formas de la ecuación de la recta: 6 preguntas.
- Recta por un punto y pendiente conocida: 6 preguntas.
- Recta que pasa por dos puntos: 6 preguntas.
- Distancia de un punto a una recta: 6 preguntas.
- Aplicaciones de la función lineal: 6 preguntas.

### Análisis combinatorio

- Factorial de un número: 6 preguntas.
- Concepto de análisis combinatorio: 6 preguntas.
- Principio fundamental de conteo: 6 preguntas.
- Variación o arreglo: 6 preguntas.
- Permutación: 6 preguntas.
- Combinación: 6 preguntas.
- Números combinatorios: 6 preguntas.
