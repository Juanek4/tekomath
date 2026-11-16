# Lector común de imágenes

Fecha: 29 de octubre de 2026.

Arcade y TekoBot usan la misma implementación OCR en imageTextRecognition.ts. La compilación crea un módulo compartido en dist/shared/image-recognition.js que importa TekoBot. Arcade incorpora la misma implementación en su aplicación. Los recursos Tesseract y el idioma español son locales. La fotografía no se sube a Gemini ni al endpoint PHP. Para leer una foto por primera vez se necesita poder descargar los recursos de la plataforma; el rendimiento depende del dispositivo.

Validación común: JPG/PNG/WebP, máximo 12 MB y 16 millones de píxeles, decodificación real en el navegador, límite de espera de 60 segundos, rechazo de texto vacío o confianza menor de 35. La confianza es una señal técnica, no una garantía matemática. El motor se reutiliza y las lecturas se serializan; un fallo libera el motor. La salida se limita a 12000 caracteres.

Flujo común: seleccionar imagen, leer, revisar/corregir el texto y confirmar antes de continuar. TekoBot presenta un editor y envía el texto confirmado como ejercicio propio; Arcade deja el texto editable y exige la acción de analizar texto. Se eliminaron las detecciones ficticias del servidor y del cliente del tutor. El endpoint antiguo devuelve 401 sin sesión y 410 con sesión, sin aceptar procesamiento remoto. El servicio PHP de compatibilidad no genera ejercicios ficticios.

Verificación: TypeScript y compilaciones de motor compartido y Arcade aprobadas. Pruebas unitarias incluyen motor simulado, tamaño, formato y baja confianza. Prueba real con Edge: ejercicio impreso generado en memoria reconocido con coseno, cateto 3 e hipotenusa 5; imagen vacía y archivo dañado rechazados. Apache estaba apagado: se utilizó un servidor temporal de archivos locales y se cerró al finalizar. Esto prueba el motor real, no el recorrido autenticado completo ni envío del ejercicio al tutor.

Pendientes: corpus revisado de fotos paraguayas, manuscritos, fracciones, raíces, sombras, giros y figuras. El OCR reconoce texto, no interpreta diagramas geométricos de forma fiable. No se promete resolver cualquier ejercicio. Falta comprobación visual completa de ambos flujos, conexión del problema confirmado con todos los modos del tutor y rendimiento en dispositivos reales. El umbral de confianza debe calibrarse con ese corpus.
