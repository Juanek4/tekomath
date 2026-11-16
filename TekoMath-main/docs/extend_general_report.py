from pathlib import Path
from copy import deepcopy
from datetime import datetime, timezone, timedelta
from zipfile import ZipFile
import hashlib, json, re
from lxml import etree as E

ROOT=Path(__file__).resolve().parents[1]
SOURCE=ROOT/'docs/Informe_cambios_TEKO_Math_2026-10-28_actualizado.docx'
OUT=ROOT/'docs/Informe_general_TEKO_Math_actualizado_2026-11-01.docx'
QA=ROOT/'docs/qa-informe-general-20261101'
TZ=timezone(timedelta(hours=-3));now=datetime.now(TZ)
NS={'w':'http://schemas.openxmlformats.org/wordprocessingml/2006/main'}
W='{'+NS['w']+'}'
records=[]
def add(title,date,reference,change,proof,limits):
    records.append(dict(number=33+len(records),title=title,date=date,reference=reference,change=change,proof=proof,limits=limits))

add('Compilación reproducible de Arcade','29/10/2026','03_teko_arcade/TEKOARCADE/JUEGOS/package.json',
    'Se instalaron las dependencias con el archivo de bloqueo y se restableció la generación de dist desde TypeScript, Vite y public. El inicio por cuenta y los cambios de presentación dejaron de depender de parches manuales en archivos minificados. La compilación también genera el módulo compartido de reconocimiento de texto.',
    'La instalación, TypeScript y Vite finalizaron correctamente. Se registraron primero 83 y, tras ampliar la cobertura, 86 pruebas internas aprobadas.',
    'Los avisos sobre scripts clásicos corresponden a recursos copiados deliberadamente. No se hizo una auditoría completa de dependencias ni una medición de capacidad nacional.')
add('Lectura común de imágenes','29/10/2026','03_teko_arcade/TEKOARCADE/JUEGOS/src/services/imageTextRecognition.ts',
    'Arcade y TekoBot utilizan la misma implementación OCR con Tesseract y recursos de español locales. Se validan JPG, PNG y WebP, tamaño máximo de 12 MB, 16 millones de píxeles y decodificación real. Se limita la espera a 60 segundos, la salida a 12.000 caracteres y se rechazan lecturas vacías o con confianza menor de 35. Ambos flujos permiten revisar y editar antes de confirmar.',
    'Motor real probado con imágenes impresas generadas, imagen vacía y archivo dañado; después se verificaron ambos recorridos autenticados. La fotografía no se envía a Gemini ni al antiguo procesador PHP.',
    'La confianza técnica no acredita corrección matemática. Faltan corpus de manuscritos, fotografías paraguayas y diagramas, y mediciones en teléfonos físicos.')
add('Problema nuevo desde el texto de una foto','29/10/2026','02_tekobot/TekoBot/Hackaton/services/pedagogy_service.php',
    'La acción image_problem distingue el ejercicio confirmado de una respuesta al taller anterior. Inicia un problema nuevo y limpia enunciados, rutas numéricas y estados incompatibles, conservando evidencias previas de aprendizaje. Se añadió un aviso cuando se intenta cargar una imagen durante otra operación.',
    'Prueba PHP de seis estados del taller, limpieza de contextos y aclaración de enunciado incompleto. En HTTP real se guardó y recuperó el problema confirmado, sin heredar el ejercicio anterior.',
    'El reconocimiento obtiene texto; no sustituye la confirmación del estudiante ni una interpretación geométrica fiable de la imagen.')
add('Confirmación real de sincronización de Arcade','29/10/2026','03_teko_arcade/TEKOARCADE/JUEGOS/public/account-progress.js',
    'El indicador diferencia cambios pendientes de progreso confirmado. Una respuesta del servidor para una instantánea anterior ya no muestra guardado mientras queda otra en cola. La bandera local pendiente se conserva hasta recibir el último acuse, con la revisión de cuenta correspondiente.',
    'Prueba de cola con respuestas diferidas y regresiones HTTP de propietario, revisión antigua, identidad manipulada y token CSRF.',
    'No certifica las puntuaciones del juego: sus métricas siguen calculándose en el cliente. La sincronización no garantiza coordinación instantánea entre dos partidas abiertas.')
add('Resolución visible de conflictos de Arcade','29/10/2026','docs/ARCADE_PARTIDA_Y_CONFLICTOS_2026-10-29.md',
    'Se incorporó una resolución guiada cuando el servidor tiene una revisión más reciente: conservar o descargar la copia local y cargar el progreso remoto. La recuperación comprueba la cuenta y evita sobrescribir silenciosamente otro estado. Se completó un recorrido real de juego, guardado y recarga.',
    'Prueba de navegador de Ratio Rush, persistencia en servidor y conflicto entre instancias con recuperación remota y copia local conservada.',
    'No se implementó una fusión automática de dos historiales divergentes ni el traslado de una partida en curso entre dispositivos.')
add('Misión diaria y recompensa única','29/10/2026','docs/ARCADE_MISION_DIARIA_2026-10-29.md',
    'La misión diaria cuenta tres respuestas correctas de seno en partidas completadas y habilita una recompensa de 30 XP. El progreso usa el día de Paraguay, identifica las partidas ya contadas y conserva el cobro en el progreso por cuenta. Se actualiza al cambiar el día.',
    'Prueba real de cumplimiento, cobro único y otra instancia de navegador sin caché que recupera la recompensa ya cobrada.',
    'La prueba verifica la regla del juego y la sincronización. El origen de los aciertos sigue siendo el cliente; no constituye una nota académica validada.')
add('Recorridos completos de los seis juegos','29/10/2026','docs/ARCADE_SEIS_JUEGOS_2026-10-29.md',
    'Se probaron Triangle Forge, Signal Sync, Vector Launch, Pair Matrix, Graph Lab y Ratio Rush desde preparación hasta cierre, persistencia y recarga. Las rutas de preparación y juego verifican los temas soportados. Graph Lab ya no sustituye silenciosamente cotangente por seno al recibir una sesión incompatible.',
    'Seis recorridos en Edge con una configuración por juego, errores y correcciones donde corresponde, pausa y resultado único. TypeScript, Vite y 86 pruebas internas aprobadas.',
    'No se cubrieron todas las combinaciones de tema y dificultad. Ratio Rush usa adelanto controlado del reloj en el test; no representa una medición del tiempo real del dispositivo.')
add('Continuación de partidas interrumpidas','29/10/2026','03_teko_arcade/TEKOARCADE/JUEGOS/src/games/core/usePersistentGameState.ts',
    'Los seis juegos conservan en sessionStorage su estado por cuenta, partida y campo: ronda, selección, pausa y feedback. La pantalla de inicio ofrece continuar la partida pendiente. Se puede recargar o volver al inicio y retomar el punto conservado.',
    'Los seis recorridos ampliados recuperaron una partida tras recargar en feedback y después de volver desde el inicio. El cierre no duplicó resultados.',
    'El estado intermedio pertenece a la pestaña; no se guarda en servidor ni se garantiza su recuperación después de cerrarla. El progreso final sí conserva su sincronización por cuenta.')
add('Ejercicios propios sin sustitución por el banco','29/10/2026','03_teko_arcade/TEKOARCADE/JUEGOS/src/games/core/exercisePool.ts',
    'Las opciones antiguas de texto y foto dirigen a Mi tarea. Se retiró la creación de ejercicios incompletos sin respuesta y se conserva la definición confirmada al preparar la partida. Triangle Forge y Ratio Rush aceptan el ejercicio propio; Vector Launch se recomienda cuando el enunciado permite construir su desafío. Los juegos de parejas y gráficas permanecen para práctica general.',
    'OCR real, edición a un problema 5/13 y corrección dentro de un juego real. Un enunciado sin datos suficientes no habilitó una partida ficticia.',
    'No se promete convertir cualquier problema en todos los juegos. Los tipos de ejercicio no soportados requieren aclaración o otra actividad.')
add('Varios quizzes abiertos sin reemplazo','29/10/2026','01_sistema_principal_y_registro_offline/config/quiz_grading.php',
    'La sesión mantiene hasta diez intentos identificados por quiz_id. Abrir un segundo cuestionario ya no reemplaza el primero. Al iniciar otro se limpian intentos vencidos o ya guardados, conservando la corrección de cada prueba en el servidor.',
    'Prueba HTTP que completó y guardó un cuestionario mientras otro seguía aceptando respuestas. Se mantuvieron el rechazo de contadores adulterados y del guardado duplicado.',
    'Los intentos siguen venciendo a las dos horas. No se recalcularon calificaciones históricas sin respuestas verificables ni se eligió una política definitiva entre oportunidades de misión.')
add('Retiro de la búsqueda de videos de TekoBot','29/10/2026','02_tekobot/TekoBot/Hackaton/api/youtube.php',
    'Se eliminó la intercepción de mensajes para buscar videos. El endpoint antiguo responde 410 y no realiza búsquedas. El glosario y la búsqueda de conceptos se conservaron. Este cambio evita presentar una subfunción que no estaba conectada correctamente.',
    'Revisión del flujo del chat y repetición de las regresiones funcionales, incluidos juegos y fotografías.',
    'No se retiraron las consultas educativas del tutor. El servicio de compatibilidad restante no forma parte de un flujo activo de búsqueda de videos.')
add('Cuentas demo y sesiones autenticadas','29/10/2026','01_sistema_principal_y_registro_offline/config/account_security.php',
    'Se retiró el sembrado automático de cuatro cuentas demo y una migración deshabilita solo las coincidencias exactas con las credenciales originales. No se borraron datos. Las sesiones usan cookies HttpOnly, SameSite Lax y Secure bajo HTTPS, modo estricto, rotación al entrar y validación de cuenta, rol, habilitación y versión. Vencen tras 30 minutos sin actividad o un máximo de ocho horas.',
    'Cuatro demos deshabilitadas comprobadas. Pruebas HTTP de cookies, rotación, revocación, cuenta deshabilitada, caducidad inactiva y absoluta y cierre de sesión.',
    'El polling cuenta como actividad. Falta revisar retención y limpieza de sesiones y completar los controles de acceso de todas las áreas de la plataforma.')
add('Credencial activa de consola y límite de acceso','29/10/2026','01_sistema_principal_y_registro_offline/config/admin_invitations.php',
    'La consola guarda su hash activo y una versión de credencial. El formulario valida la clave actual y la confirmación, y exige doce caracteres para nuevas rotaciones normales. El cambio invalida sesiones administrativas previas. El acceso normal y administrativo limita persistentemente cinco fallos durante la ventana activa y bloquea cinco minutos por tipo, identificador e IP.',
    'Pruebas de cambio y versión con rollback y límite entre navegadores. Se corrigió una confirmación implícita por DDL durante el primer test y se restauró inmediatamente la credencial anterior; después la prueba revirtió correctamente.',
    'No se presenta aquel cambio temporal de prueba como contraseña final. La elección posterior del propietario se registra en la ficha 57. No cubre abuso distribuido ni límites generales de consumo del tutor.')
add('Protección HTTP de archivos internos','29/10/2026','.htaccess',
    'Se deshabilitaron listados de carpetas y se bloquearon por HTTP configuraciones, bases, respaldos, logs, documentación interna, fuentes y dependencias de desarrollo. Los recursos públicos de assets y dist, incluidos OCR y archivos de idioma, siguen accesibles. Los includes PHP internos no se bloquean.',
    'La prueba inicial cubrió 14 rutas privadas y nueve públicas; con el banco de evaluación se amplió a 15 privadas, todas denegadas, y las nueve públicas accesibles.',
    'Requiere Apache con las reglas habilitadas. No sustituye una publicación con lista de archivos permitidos ni se aplica automáticamente a Nginx o al servidor de desarrollo de Vite.')
add('Diagnósticos corregidos en servidor e identidad de progreso','30/10/2026','02_tekobot/TekoBot/Hackaton/api/diagnostic.php',
    'Progreso y diagnóstico usan la identidad de la sesión y no el student_id del navegador. El servidor recibe las opciones elegidas, calcula nota y duración y guarda el resultado. El diagnóstico usa un token de intento, vence a las dos horas y rechaza reenvíos. La interfaz dejó de corregir localmente y de afirmar que guardó si la petición falló.',
    'Dos cuentas probaron aislamiento, nota manipulada ignorada, respuestas incompletas, token ajeno y reenvío rechazados. El banco inicial de cinco preguntas fue después reemplazado por el de la ficha 48.',
    'No se recalcularon diagnósticos antiguos. Solo hay un intento diagnóstico activo por sesión; abrir otro o cambiar tema reemplaza el anterior.')
add('Banco de seis preguntas por cada subtema','30/10/2026','02_tekobot/TekoBot/Hackaton/config/assessment_bank.json',
    'Se creó un banco compartido de 372 preguntas: 78 de Funciones para 13 subtemas, 162 de Trigonometría para 27, 90 de Geometría para 15 y 42 de Combinatoria para siete. Cada subtema tiene seis preguntas con cuatro opciones, clave privada y pista. El chat y el diagnóstico usan el mismo banco filtrado por unidad y tema.',
    'Cobertura de los 62 subtemas y pruebas de las 372 preguntas, opciones, claves, pistas y calificación. El banco completo responde 403 por HTTP y el cliente no recibe las claves.',
    'Parte del banco son variantes numéricas de una habilidad. Falta revisión matemática y pedagógica independiente y más variedad para no repetir preguntas entre evaluación inicial y final. No es entrenamiento de un modelo.')
add('Evaluación por tema integrada en ambas entradas','30/10/2026','02_tekobot/TekoBot/Hackaton/assets/js/diagnostic.js',
    'Se añadió Evaluarme por tema al menú y se integró el diálogo en index.php e index.html, pues la antigua vista PHP no se incluía en la entrada actual. Permite elegir cuatro unidades y 62 subtemas, presenta seis preguntas y confirma la nota solo después de guardarla. El chat pide responder con la opción de 1 a 4 y ofrece pistas y regreso al tutor.',
    'Selección de los 62 subtemas con seis preguntas, guardado real y prueba a 390 y 1365 píxeles sin desbordamiento. Se verificó persistencia de una evaluación completa del chat y compatibilidad con pruebas antiguas de tres ejercicios.',
    'La comprobación móvil es de navegador simulado. La evaluación requiere sesión y servidor; no se publica la clave para calificar sin conexión.')
add('Análisis proporcional y resultados por subtema','30/10/2026','02_tekobot/TekoBot/Hackaton/services/student_analysis.php',
    'Las pruebas nuevas registran unidad, subtema y versión. El apoyo se ajusta por proporción de aciertos sin pista, en lugar del umbral fijo usado para tres ejercicios. Un resultado de otro subtema no modifica el apoyo del actual. Al terminar se informa total correcto y aciertos autónomos; el docente ve el nombre del subtema en las pruebas del chat.',
    'Pruebas de aislamiento conceptual, umbral de seis preguntas, dudas no penalizadas y separación de pistas. Resultados guardados y recuperados por HTTP.',
    'Es evidencia limitada por actividad, no diagnóstico psicológico ni garantía de dominio. El análisis docente aún consulta las últimas 500 conversaciones; falta paginación y filtros generales.')
add('Protección común de rutas de TekoBot','30/10/2026','02_tekobot/TekoBot/Hackaton/config/api_guard.php',
    'Se centralizó la autenticación, métodos permitidos, errores JSON y CSRF de sesiones, mensajes y diagnósticos. Se validan tipos, JSON, mensajes de hasta 12.000 caracteres y solicitudes de hasta 64 KiB. El historial exige conversación propia y el listado de ejercicios omite respuestas y pistas internas. El contexto del chat se reconstruye en servidor. Se retiró el CORS abierto y se añadieron no-store y nosniff.',
    'Dos cuentas comprobaron lectura, escritura, borrado y reutilización de UUID ajenos rechazados sin alterar el historial del dueño; métodos, tokens y formatos inválidos denegados.',
    'La revisión cubre las rutas enumeradas, no una auditoría externa de toda la plataforma. Faltan controles generales de consumo y la revisión específica de login/logout y otras áreas.')
add('Historial local separado y vistas previas seguras','30/10/2026','02_tekobot/TekoBot/Hackaton/assets/js/app.js',
    'El arranque verifica la cuenta y separa las copias locales mediante un prefijo por usuario. Sin identidad verificada usa un espacio temporal de invitado. No importa las cachés antiguas sin dueño fiable. Las vistas previas usan textContent para no interpretar mensajes como HTML. Un rechazo explícito no se transforma silenciosamente en respuesta local y el borrado se refleja tras confirmación del servidor.',
    'Cambio entre dos cuentas en el mismo navegador, caché histórica ignorada, etiqueta HTML mostrada como texto y rechazo 403 visible sin activar un chat offline. Regresiones de fotografías y conversaciones aprobadas.',
    'No cifra el almacenamiento ni borra de una pestaña ya abierta lo que ya se vio. Las conversaciones del servidor se conservaron; la separación local no protege frente al acceso físico o a las herramientas del navegador.')
add('Recuperación privada de cuentas','30/10/2026','01_sistema_principal_y_registro_offline/config/password_recovery.php',
    'Se añadió el acceso Olvidaste tu contraseña y la generación de enlaces desde la consola privada, tras comprobación de identidad por coordinación. El enlace aleatorio se almacena como hash, vence en 30 minutos, reemplaza los anteriores y sirve una vez. La persona elige su clave; la transacción incrementa la versión de sesión. La URL se limpia tras capturar el token y el formulario requiere CSRF.',
    'Enlaces vencidos, sustituidos o usados rechazados; dos envíos simultáneos producen un solo cambio. Contraseña nueva aceptada, anterior rechazada, sesiones revocadas y rol docente conservado. Revisión a 390 y 1365 píxeles.',
    'No hay correo automático configurado: el enlace se comparte manualmente. No recupera la contraseña de la consola. Quedan pendientes retención y limpieza de enlaces y registros del limitador.')
add('Configuración privada compartida','01/11/2026','config/runtime.php',
    'Las conexiones y la clave opcional de Gemini pasan a una configuración común, leída desde variables del servidor o un archivo fuera del directorio público. Se rechaza una ubicación privada dentro del proyecto o DocumentRoot. La instalación local usa C:/xampp1/teko-private/teko-math.php, con ACL restringida. Se dejó un ejemplo sin credenciales utilizables y se retiró la carga de .env desde el tutor público.',
    'Archivo fuera de htdocs y no descargable, prioridad de variables comprobada y pruebas de conexión y rutas privadas aprobadas. No se imprimieron claves ni se incluyeron en este informe.',
    'Hay que trasladar esa configuración de forma segura al alojamiento definitivo. El dominio, el correo y los requisitos TLS de un proveedor SQL remoto aún no se eligieron.')
add('Usuarios SQL propios y reparación inicial de permisos','01/11/2026','scripts/setup_local_private.php',
    'Se crearon dos usuarios SQL aleatorios, cada uno limitado a su base; la aplicación dejó de usar root. Para conservar la instalación local tienen también permisos de estructura dentro de su base. Durante la provisión apareció corrupción Aria en mysql.db: se respaldó en frío, se reparó y se repusieron permisos de aplicación y del usuario de control pma conforme al SQL incluido con phpMyAdmin.',
    'Las cuentas reales conectaron y no pudieron leer la otra base. mysql.db respondió OK y pasaron cuentas, recuperación y tutor. Se corrigió una ruta de provisión antes de activar el archivo fuera de htdocs.',
    'La reparación inicial no recuperó tres registros ilegibles de permisos, por lo que se reconstruyeron los necesarios y se conservaron las copias. La advertencia restante de global_priv se investigó después, según la ficha 60.')
add('Modo producción con instalación explícita','01/11/2026','scripts/prepare_schema.php',
    'Producción exige URL pública HTTPS, usuarios SQL distintos de root y contraseñas no vacías. Oculta errores al visitante, rechaza acceso autenticado inseguro y evita crear otra base o pasar a SQLite ante fallas del tutor. Las peticiones normales no ejecutan CREATE ni ALTER; la preparación estructural queda en una herramienta CLI explícita. Se añadió check_runtime para comprobar conexiones y tablas sin mostrar claves.',
    'Prueba con cuentas temporales DML sin permiso CREATE; configuraciones inseguras y archivo privado público rechazados, sin fallback SQLite. Preparación de tablas verificada sin ejecutar los INSERT históricos de demos.',
    'No se activó producción. Quedan dominio, certificado, transporte de correo, migración del alojamiento y configuración confiable del proxy. La comprobación de configuración no certifica carga ni seguridad integral.')
add('Contraseña administrativa elegida por el propietario','01/11/2026','docs/RESPALDO_Y_RESTAURACION_2026-11-01.md',
    'Se aplicó exactamente la contraseña indicada por el propietario mediante mantenimiento local, se guardó su hash, se incrementó la versión de consola y se retiró el hash de arranque del archivo privado activo. Se conservaron respaldos antes y después. Las sesiones administrativas previas quedaron invalidadas; no se modificaron las contraseñas de estudiantes ni docentes.',
    'La clave anterior fue rechazada y la nueva aceptada. Una prueba posterior de rotación con rollback conservó la clave elegida. check_runtime dejó de informar el pendiente de contraseña inicial.',
    'La elección de ocho caracteres fue una excepción local autorizada; el formulario general mantiene el mínimo de doce para futuras rotaciones. No se incluye la contraseña, su hash ni tokens en este documento.')
add('Respaldos consistentes y manifiesto de integridad','01/11/2026','scripts/backup_databases.php',
    'Se añadieron herramientas CLI para copiar ambas bases en un mismo punto lógico con una barrera breve de lectura. Cada respaldo incluye SQL principal y tutor, configuración privada, my.ini, permisos SQL y un manifiesto con hashes y cantidades por tabla. Se guardaron versiones antes del cambio de clave, después y una copia final posterior a las regresiones.',
    'Tres respaldos fuera de htdocs, cada uno con 27 tablas. El último corte es full-20261101-180808-76d842. La prueba de archivo alterado fue rechazada antes de importar; el respaldo real permaneció intacto.',
    'Son copias locales del mismo equipo. Falta una copia cifrada externa y definir frecuencia y retención. El bloqueo y la comparación en memoria están limitados al mantenimiento local, no dimensionados para operación nacional continua.')
add('Restauración aislada comprobada','01/11/2026','scripts/verify_restoration.php',
    'El restaurador verifica los archivos, crea dos bases y usuarios temporales sin acceso a las bases reales y compara estructura y contenido canónico por SHA-256. Inicia una web temporal de loopback con puerto disponible e identificación por nonce antes de cualquier alta. Sustituye las credenciales de prueba y deshabilita llamadas a Gemini. El resultado se declara exitoso después de limpiar servidor, bases y cuentas temporales.',
    'En el respaldo final coincidieron las 27 tablas y 40 registros. El recorrido HTTP restaurado probó consola, invitación, docente, alumno, asignación, tutor, historial y diagnóstico persistido. No quedaron bases ni usuarios SQL temporales.',
    'La prueba representa los datos actuales, no 600 estudiantes. No se restauraron las tablas globales mysql sobre el servidor real ni se hizo una recuperación completa en otro equipo.')
add('Mantenimiento controlado y comprobación de MariaDB','01/11/2026','scripts/maintenance_lib.php',
    'Se añadió una bandera privada de mantenimiento que detiene nuevas peticiones PHP con 503 antes de acceder a datos. MySQL se cerró ordenadamente, se copiaron en frío db/global_priv y aria_log_control y se ejecutó aria_chk en modo de solo lectura con datadir correcto. No fue necesario reparar global_priv ni reemplazar archivos educativos. Se retiró la bandera tras verificar el arranque.',
    'Tras el reinicio, 27 tablas educativas y dos de permisos pasaron CHECK TABLE EXTENDED sin advertencias. Las huellas educativas y de permisos coincidieron con las previas. La web respondió 200 y la bandera quedó desactivada.',
    'La advertencia de tamaño anterior no se reprodujo, sin atribuirle una causa definitiva. No es una revisión del disco ni garantía indefinida. Persiste un aviso separado y no corruptivo por la abreviatura key_buffer en my.ini.')
add('Regresión posterior y estado del piloto','01/11/2026','docs/RESPALDO_Y_RESTAURACION_2026-11-01.md',
    'Después del cambio de contraseña y del reinicio se repitieron cuentas, invitaciones, asignaciones, recuperación, permisos de tareas y materiales, calificación de quizzes y misiones, Live, TekoBot y progreso por cuenta de Arcade. Se incorporaron pruebas reproducibles de restauración, rechazo de respaldo alterado y mantenimiento. El informe distingue resultados anteriores y estados sustituidos.',
    'Las suites locales indicadas aprobaron; se limpiaron cuentas y recursos temporales. Las pruebas de producción y archivos privados aprobaron y check_runtime devolvió configuration_ready=true en modo local.',
    'No se desplegó ni se probó la carga del piloto. Siguen pendientes concurrencia, revisión pedagógica humana, teléfonos físicos, accesibilidad, privacidad y preparación operativa para 600 estudiantes y 60 docentes.')

groups=[('Compilación e imágenes',33,34),('Contexto de imágenes y sincronización',35,36),('Conflictos y recompensa diaria',37,38),
('Juegos y recuperación de partidas',39,40),('Ejercicios propios y evaluaciones paralelas',41,42),('Videos y seguridad de cuentas',43,44),
('Consola y archivos internos',45,46),('Diagnóstico y banco por tema',47,48),('Evaluación visible y análisis docente',49,50),
('Protección de rutas e historial local',51,52),('Recuperación y configuración privada',53,54),('Cuentas SQL y modo de producción',55,56),
('Credencial elegida y respaldos',57,58),('Restauración y salud de MariaDB',59,60),('Regresión final y alcance del piloto',61,61)]

source_hash=hashlib.sha256(SOURCE.read_bytes()).hexdigest()
assert source_hash=='aeb3547189e13b3f5c1c444ce55aeafcd8567b5ed147cec2ffa13705c3f6ab23'
with ZipFile(SOURCE) as z:parts={name:z.read(name) for name in z.namelist()};infos=z.infolist()
document=E.fromstring(parts['word/document.xml']);body=document.find(W+'body');source_paragraphs=body.findall(W+'p')
def text(p):return ''.join(p.itertext()) if False else ''.join(p.xpath('.//w:t/text()',namespaces=NS))
def set_text(p,value):
    original_run=p.find(W+'r');rpr=deepcopy(original_run.find(W+'rPr')) if original_run is not None and original_run.find(W+'rPr') is not None else None
    for child in list(p):
        if child.tag!=W+'pPr':p.remove(child)
    run=E.SubElement(p,W+'r')
    if rpr is not None:run.append(rpr)
    node=E.SubElement(run,W+'t');node.set('{http://www.w3.org/XML/1998/namespace}space','preserve');node.text=value
    return p
normal=deepcopy(source_paragraphs[164]);heading1=deepcopy(source_paragraphs[161]);heading2=deepcopy(source_paragraphs[163])
bullet=next(deepcopy(p) for p in source_paragraphs if p.find('./w:pPr/w:pStyle',NS) is not None and p.find('./w:pPr/w:pStyle',NS).get(W+'val')=='ListBullet')
pagebreak=E.Element(W+'p');E.SubElement(E.SubElement(pagebreak,W+'r'),W+'br').set(W+'type','page')
sect=body.find(W+'sectPr');body.remove(sect)
def paragraph(value,template=normal):body.append(set_text(deepcopy(template),value))
def page(title):body.append(deepcopy(pagebreak));paragraph(title,heading1)
def h2(value):paragraph(value,heading2)

set_text(source_paragraphs[1],'Registro del 28 de octubre al 1 de noviembre de 2026')
set_text(source_paragraphs[2],'Actualización consolidada al '+now.strftime('%d/%m/%Y %H:%M:%S')+' · Hora de Paraguay. El estado vigente y los pendientes se encuentran al final.')
set_text(source_paragraphs[4],'Resultado general del período')
set_text(source_paragraphs[5],'El registro reúne la reorganización, el tutor y las cuentas del primer corte, más la compilación y corrección de Arcade, OCR común, protección de cuentas y rutas, un banco de 372 preguntas con seis por subtema, recuperación de acceso, configuración privada y respaldos con restauración comprobada. Se conserva el historial y se añaden 29 fichas posteriores, de la 33 a la 61.')
set_text(source_paragraphs[6],'La plataforma continúa en localhost. Los quizzes, las misiones y los diagnósticos cubiertos se corrigen en servidor; las métricas de Arcade siguen originándose en cliente. Se verificó restauración de 27 tablas y 40 registros actuales. No se acredita capacidad para 600 conexiones simultáneas ni preparación nacional.')
for p in source_paragraphs:
    if text(p).startswith('Hora de elaboración:'):set_text(p,text(p).replace('Hora de elaboración:','Hora de elaboración del primer corte:',1))
    if text(p)=='Estado vigente de la lista de prioridades':set_text(p,'Estado de prioridades al 28 de octubre')
    if text(p)=='Pendientes antes de un piloto público':set_text(p,'Pendientes al cierre del 28 de octubre')

page('Actualización desde el último informe general')
paragraph('Período agregado: del 29 de octubre al 1 de noviembre de 2026. Esta continuación conserva las fichas 01 a 32 como historial y documenta el trabajo posterior. El estado vigente de los apartados finales prevalece sobre los pendientes registrados en cortes anteriores.')
paragraph('Se mantiene el formato de cada ficha: número y título, fecha de trabajo, última modificación del archivo de referencia, descripción, archivo, verificación y límites. Las fechas de trabajo proceden de los registros de la conversación y los informes técnicos. Las horas de archivo son referencias verificables al elaborar esta actualización, no horas exactas de implementación.')
paragraph('Una función pudo volver a editarse el 1 de noviembre y conservar una fecha de trabajo del 29 o 30 de octubre. No se inventan horas para esos cambios. Los cortes de respaldo sí tienen marcas registradas: 01/11/2026 a las 17:56:04, 17:56:52 y 18:08:08, hora de Paraguay.')
h2('Resumen del estado actual')
paragraph('Las nuevas comprobaciones cierran varios pendientes funcionales de Arcade y de identidad del tutor. Se completaron el banco mínimo por subtema, la recuperación asistida y las herramientas de respaldo y restauración. Las pruebas locales aprobaron después del cambio administrativo y del reinicio limpio de MariaDB.')
paragraph('Se conservan pendientes explícitos: carga del piloto, revisión humana del contenido y jopara, privacidad, accesibilidad, teléfono físico, análisis docente de más de 500 conversaciones, organización institucional, correo automático, copia externa y publicación con HTTPS.')
for title,start,end in groups:
    page(title)
    for record in records:
        if not start<=record['number']<=end:continue
        ref=ROOT/record['reference'];assert ref.is_file(),ref
        h2(f"{record['number']:02d} {record['title']}")
        stamp=datetime.fromtimestamp(ref.stat().st_mtime,TZ).strftime('%d/%m/%Y %H:%M:%S')
        paragraph('Fecha de trabajo: '+record['date']+'. Última modificación del archivo de referencia: '+stamp+' · Paraguay.')
        paragraph(record['change']);paragraph('Archivo de referencia: '+record['reference'])
        paragraph('Verificación: '+record['proof']);paragraph('Límites: '+record['limits'])

page('Estado vigente al 1 de noviembre')
for title,state in [
 ('Registro docente y asignaciones','Implementado en los flujos probados. Registro público de estudiantes, docentes por invitación privada y alumnos vinculados por código. Falta institución, curso y sección.'),
 ('Identidad entre las bases','Implementada para las cuentas nuevas y las rutas comprobadas. No se asignaron automáticamente conversaciones históricas sin correspondencia fiable.'),
 ('Permisos y solicitudes','Verificados en las rutas cubiertas de tutor, tareas, materiales, Live, progreso y recuperación. No equivale a revisar todos los endpoints ni login/logout y toda defensa ante abuso.'),
 ('Calificación de actividades','Quizzes, misiones y diagnósticos cubiertos se corrigen en servidor. Métricas de Arcade, mediciones no verificadas y calificaciones antiguas sin evidencia siguen fuera de esa garantía.'),
 ('Consola y contraseñas','Credencial del propietario aplicada, versión revocada y hash de arranque retirado. Recuperación de estudiantes/docentes mediante enlaces privados y coordinación, sin correo automático.'),
 ('Datos y archivos privados','Credenciales fuera de htdocs, usuarios SQL separados y rutas internas bloqueadas en Apache. La publicación debe revalidar reglas y usar un conjunto explícito de archivos.'),
 ('Respaldos y MariaDB','Tres cortes privados y restauración final comprobada. Veintinueve tablas pasaron revisión extendida tras cierre limpio. Falta copia cifrada externa, retención y ensayo de recuperación en otro equipo.')]:
    h2(title);paragraph(state)
page('Contenido operación y publicación')
for title,state in [
 ('Arcade','Compilación reproducible y seis juegos con recorridos representativos, continuación en pestaña, conflicto guiado, misión diaria y ejercicio propio compatible. No se verificó toda combinación ni se guarda la partida intermedia en servidor.'),
 ('Imágenes','Motor común y ambos recorridos autenticados comprobados, con edición y confirmación. Falta corpus de manuscritos, diagramas y fotos reales; no se garantiza interpretar toda imagen.'),
 ('Banco y adaptación','372 preguntas para 62 subtemas y apoyo proporcional con pistas separadas. Falta revisión matemática y lingüística independiente, variedad adicional y comparación inicial/final sin repeticiones.'),
 ('Análisis docente','Resultados de chat identificados por subtema. La consulta aún se limita a las últimas 500 conversaciones; falta filtrado y paginación general.'),
 ('Móviles y accesibilidad','Recorridos y formularios probados con anchos de 390 y 1365 píxeles. El teléfono físico fue pospuesto y sigue pendiente, junto con teclado virtual, conexión intermitente y accesibilidad completa.'),
 ('Producción y servicios externos','Configuración de código y cuentas locales comprobada. Faltan alojamiento, dominio, certificado, correo y validación del SQL remoto. No se publicó la plataforma ni se contrató un proveedor.'),
 ('Capacidad del piloto','Objetivo de 600 estudiantes y 60 docentes. No hay prueba de carga ni definición verificada de concurrencia simultánea. Los 40 registros restaurados son el volumen actual, no una simulación de 600 usuarios.'),
 ('Operación y privacidad','Pendientes capacitación docente, soporte, contingencias, autorizaciones institucionales, datos de menores, retención y revisión de proveedores. No se declara cumplimiento normativo integral ni auditoría externa.')]:
    h2(title);paragraph(state)

page('Evidencias de la actualización')
for title,path in [
 ('Compilación y juegos','docs/ARCADE_COMPILACION_2026-10-29.md; docs/ARCADE_SEIS_JUEGOS_2026-10-29.md'),
 ('Continuación y conflictos','docs/ARCADE_PARTIDA_Y_CONFLICTOS_2026-10-29.md; docs/ARCADE_MISION_DIARIA_2026-10-29.md'),
 ('OCR y funciones','docs/IMAGENES_LECTOR_COMUN_2026-10-29.md; docs/CIERRE_PENDIENTES_FUNCIONALES_2026-10-29.md'),
 ('Cuentas y archivos','docs/SEGURIDAD_FASE1_2026-10-29.md; docs/SEGURIDAD_ARCHIVOS_2026-10-29.md'),
 ('Diagnósticos y banco','docs/SEGURIDAD_DIAGNOSTICOS_2026-10-30.md; docs/BANCO_EVALUACIONES_2026-10-30.md'),
 ('Rutas y recuperación','docs/SEGURIDAD_RUTAS_TEKOBOT_2026-10-30.md; docs/RECUPERACION_CUENTAS_2026-10-30.md'),
 ('Despliegue y mantenimiento','docs/CONFIGURACION_PRODUCCION_2026-11-01.md; docs/RESPALDO_Y_RESTAURACION_2026-11-01.md')]:
    h2(title);paragraph(path)
paragraph('Pruebas reproducibles de la última etapa: tests/production_config_test.cjs, tests/private_config_http_test.cjs, tests/password_recovery_http_test.cjs, tests/tutor_identity_http_test.cjs, tests/restored_site_smoke.cjs y tests/backup_integrity_test.cjs. Las suites de recursos, Live y Arcade se registran en sus informes técnicos. Aquí se consolidan resultados ya registrados; no se presentan como reejecutados durante la redacción.')
page('Próximos controles antes del piloto')
for value in [
 'Definir escenarios de concurrencia y ejecutar carga gradual para el piloto; medir errores, tiempos, bloqueos SQL y recuperación.',
 'Revisar con docentes los enunciados, respuestas, pistas y jopara, y diversificar preguntas para evaluar aprendizaje sin repetir las mismas.',
 'Completar revisión de rutas restantes, login/logout, límites de consumo, dependencias y limpieza de sesiones, enlaces y registros.',
 'Añadir filtros y paginación del análisis docente y acordar instituciones, cursos, secciones y política de calificación entre oportunidades.',
 'Probar teléfonos físicos, teclado virtual, fotos reales, conexión intermitente y accesibilidad con participantes representativos.',
 'Trasladar una copia cifrada a otro medio, definir retención y ensayar recuperación en otro equipo o alojamiento.',
 'Elegir entorno de pruebas publicado, configurar dominio, HTTPS, SQL remoto y correo, y verificar la publicación sin archivos internos.',
 'Preparar capacitación y soporte docente, autorizaciones institucionales, privacidad de menores, retención y contingencias.']:
    paragraph(value,bullet)
paragraph('Fecha objetivo conservada: 31 de enero de 2027. Este informe documenta cambios y verificaciones locales; no certifica preparación nacional, estabilidad indefinida ni carga simultánea. Durante esta actualización del Word no se modificaron funciones de la plataforma.')
body.append(sect)
parts['word/document.xml']=E.tostring(document,xml_declaration=True,encoding='UTF-8',standalone=True)
footer=E.fromstring(parts['word/footer1.xml']);footer.xpath('//w:t',namespaces=NS)[0].text='28/10/2026 a 01/11/2026 · America/Asuncion                         Página '
parts['word/footer1.xml']=E.tostring(footer,xml_declaration=True,encoding='UTF-8',standalone=True)
settings=E.fromstring(parts['word/settings.xml']);update=settings.find(W+'updateFields')
if update is None:update=E.SubElement(settings,W+'updateFields')
update.set(W+'val','true');parts['word/settings.xml']=E.tostring(settings,xml_declaration=True,encoding='UTF-8',standalone=True)
core=E.fromstring(parts['docProps/core.xml']);C={'d':'http://purl.org/dc/elements/1.1/','t':'http://purl.org/dc/terms/'}
for name,value in [('title','Informe general de cambios de TEKO Math'),('description','Registro consolidado del 28 de octubre al 1 de noviembre de 2026. Se conservan 32 fichas históricas y se agregan 29 fichas, estados y evidencias.')]:
    node=core.find('{'+C['d']+'}'+name)
    if node is None:node=E.SubElement(core,'{'+C['d']+'}'+name)
    node.text=value
core.find('{'+C['t']+'}modified').text=now.astimezone(timezone.utc).strftime('%Y-%m-%dT%H:%M:%SZ')
parts['docProps/core.xml']=E.tostring(core,xml_declaration=True,encoding='UTF-8',standalone=True)
with ZipFile(OUT,'w') as z:
    for info in infos:z.writestr(info,parts[info.filename])
assert hashlib.sha256(SOURCE.read_bytes()).hexdigest()==source_hash
with ZipFile(OUT) as z:
    final_text=''.join(E.fromstring(z.read('word/document.xml')).xpath('//w:t/text()',namespaces=NS))
    assert len(re.findall(r'(?<!\d)(?:3[3-9]|[45]\d|6[01]) ',final_text))>=29
    for secret in ['T3k0l4bs','tek0l4bs']:assert secret not in final_text,'Secret text found'
    preserved=[name for name in parts if name not in ['word/document.xml','word/footer1.xml','word/settings.xml','docProps/core.xml']]
    with ZipFile(SOURCE) as original:
        for name in preserved:assert z.read(name)==original.read(name),name
QA.joinpath('added-records.json').write_text(json.dumps(records,ensure_ascii=False,indent=2),encoding='utf-8')
QA.joinpath('structural-check.json').write_text(json.dumps({'source_sha256':source_hash,'source_unchanged':True,'historical_records':32,'added_records':29,'last_record':61,'preserved_parts':preserved,'modified_parts':['word/document.xml','word/footer1.xml','word/settings.xml','docProps/core.xml'],'output':str(OUT)},indent=2),encoding='utf-8')
print(str(OUT))
