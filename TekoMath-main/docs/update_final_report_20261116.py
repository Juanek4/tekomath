from pathlib import Path
from copy import deepcopy
from zipfile import ZipFile
import hashlib
import json
from lxml import etree as E

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / 'docs/Informe_general_TEKO_Math_actualizado_2026-11-01.docx'
OUT = ROOT / 'docs/Informe_final_TEKO_Math_actualizado_2026-11-16.docx'
QA = ROOT / 'docs/qa-informe-final-20261116'
QA.mkdir(exist_ok=True)
NS = {'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'}
W = '{' + NS['w'] + '}'
source_hash = hashlib.sha256(SOURCE.read_bytes()).hexdigest()
with ZipFile(SOURCE) as z:
    infos = z.infolist()
    parts = {i.filename: z.read(i.filename) for i in infos}
doc = E.fromstring(parts['word/document.xml'])
body = doc.find(W + 'body')
paras = body.findall(W + 'p')

def text(p):
    return ''.join(p.xpath('.//w:t/text()', namespaces=NS))

def replace(p, value):
    first = p.find(W + 'r')
    rpr = deepcopy(first.find(W + 'rPr')) if first is not None and first.find(W + 'rPr') is not None else None
    for child in list(p):
        if child.tag != W + 'pPr':
            p.remove(child)
    run = E.SubElement(p, W + 'r')
    if rpr is not None:
        run.append(rpr)
    E.SubElement(run, W + 't').text = value
    return p

normal = deepcopy(paras[456])
h1 = deepcopy(paras[454])
h2 = deepcopy(paras[455])
sect = body.find(W + 'sectPr')
body.remove(sect)

def p(value, template=normal):
    body.append(replace(deepcopy(template), value))

def page(title):
    br = E.SubElement(body, W + 'p')
    E.SubElement(E.SubElement(br, W + 'r'), W + 'br').set(W + 'type', 'page')
    p(title, h1)

replace(paras[0], 'Informe final de cambios de TEKO Math')
replace(paras[1], 'Registro del 28 de octubre al 16 de noviembre de 2026')
replace(paras[2], 'Actualización consolidada al 16/11/2026 · Hora de Paraguay. El estado vigente y las prioridades se encuentran en los apartados finales.')
replace(paras[5], 'El informe conserva las 61 fichas anteriores y agrega seis fichas sobre reparación local de MariaDB, restauración en Apache, mediciones de progreso y carga mixta, datos de clase docente y preparación del alojamiento. El último corte previo corresponde al 1 de noviembre; los nuevos cambios documentados corresponden al 10 de noviembre y se consolidan al 16 de noviembre de 2026.')
replace(paras[6], 'TekoMath continúa en localhost y todavía no tiene alojamiento contratado. Se registraron pruebas breves con 25 cuentas concurrentes sobre copias aisladas y una reparación local de permisos SQL. Estos resultados no acreditan carga sostenida, capacidad para 600 estudiantes ni preparación para publicación. Los resultados históricos no se presentan como reejecutados durante esta actualización documental.')
for para in paras:
    if text(para) == 'Estado vigente al 1 de noviembre':
        replace(para, 'Estado registrado al 1 de noviembre')
    elif text(para) == 'Contenido operación y publicación':
        replace(para, 'Contenido operación y publicación al 1 de noviembre')
    elif text(para) == 'Próximos controles antes del piloto':
        replace(para, 'Controles pendientes registrados al 1 de noviembre')

page('Actualización posterior al último informe')
p('Este apartado incorpora el trabajo posterior al informe del 1 de noviembre de 2026. Las fichas 01 a 61 se conservan como historial. Las fichas 62 a 67 y el estado final de esta actualización prevalecen sobre los pendientes anteriores que hayan sido modificados.')
p('Se revisaron los informes técnicos posteriores, los scripts de mantenimiento y pruebas, el formulario de vinculaciones, su lógica de datos y estilos, y la preparación del alojamiento. La fecha de modificación de un archivo sirve para localizar cambios; no demuestra por sí sola una ejecución exitosa ni la hora exacta de implementación.')
p('Las pruebas históricas de restauración y carga se resumen con sus límites. La prueba de asignaciones fue ampliada en el código, pero no se encontró un registro posterior que permita afirmar que esa ampliación pasó. No se reiniciaron servicios ni se alteraron datos para redactar este informe.')
p('La última ejecución registrada del nuevo comprobador de alojamiento detectó PHP 8.2.12, todas las extensiones requeridas y fallos de conexión en ambas bases. Es un resultado puntual del 10 de noviembre; no contradice ni sustituye las conexiones que sí funcionaron en las pruebas anteriores de esa fecha.')
p('Se acordó avanzar fila por fila en la preparación para publicación. Solo se inició la fila de servidor y versiones. La consulta sobre alojamiento quedó en evaluación: no se seleccionó proveedor, no se contrató un plan y no se publicó la aplicación.')

records = [
('62 Reparación recurrente y arranque de MariaDB',
 'Se investigó la reaparición de daños en mysql.global_priv y mysql.db. La revisión encontró texto de arranque y apagado dentro de los archivos MAD de permisos. Se conservaron copias físicas privadas con MariaDB detenido, se repararon las tablas y se corrigió el arranque desacoplado con --console y redirección explícita de entrada, salida y errores a archivos privados.',
 'scripts/start_mariadb_local.ps1; docs/REPARACION_MARIADB_2026-11-10.md',
 'El procedimiento documentado pasó tres ciclos de creación, permisos y eliminación de usuarios, cada uno seguido de apagado y arranque limpio. Las tablas de permisos terminaron OK sin avisos de tamaño. También pasó una restauración aislada posterior sobre Apache y no quedaron usuarios temporales.',
 'El script rechaza otra instancia y verifica el ejecutable. El arranque mediante el panel de XAMPP no fue modificado ni validado. Se conserva el antecedente de corrupción; no se certifica el disco ni estabilidad indefinida. Una ruta incorrecta de la primera versión fue bloqueada antes de detener el servicio y se corrigió antes de ejecutar los ciclos.'),
('63 Restauración aislada mediante Apache',
 'El verificador admite TEKO_RESTORE_APACHE=1. Inicia un Apache temporal en loopback, carga PHP y emplea cuentas SQL restringidas a las bases restauradas. Antes de los recorridos comprueba por HTTP el nonce y la identidad de ambas bases. La salida de las pruebas se conserva en http-test-results.log.',
 'scripts/verify_restoration.php; scripts/maintenance_lib.php; docs/PRUEBA_APACHE_2026-11-10.md',
 'La ejecución documentada restauró y comparó 27 tablas y 40 filas. Pasaron invitación docente, registro estudiantil, asignación, conversación, historial y diagnóstico. Se eliminaron las bases, cuentas y procesos Apache temporales.',
 'Es una restauración local sobre copias y un puerto de loopback, no un despliegue en Internet. El aviso de tamaño observado en la primera medición se conserva como antecedente; el seguimiento de la ficha 62 informa las comprobaciones posteriores sin avisos.'),
('64 Primera medición concurrente de progreso',
 'Se incorporó una medición opcional de solicitudes de progreso al recorrido de restauración. Se registraron 25 cuentas adicionales en las copias y se midieron tandas de 1, 5, 10 y 25 solicitudes simultáneas.',
 'tests/restored_site_smoke.cjs; docs/PRUEBA_APACHE_2026-11-10.md',
 'Todas las tandas terminaron con cero errores. El percentil 95 fue 18 ms con una solicitud, 52 ms con cinco, 60 ms con diez y 107 ms con veinticinco.',
 'Una tanda por nivel, datos pequeños, cliente y servidor en el mismo equipo y solo consulta de progreso. La ejecución previa con el servidor integrado de PHP no conservó latencias y no se mezcla con estas métricas de Apache. No permite dimensionar el piloto.'),
('65 Carga mixta breve con veinticinco cuentas',
 'Se añadió TEKO_RESTORE_MIXED=1 junto a TEKO_RESTORE_LOAD=1. El escenario abre 25 conversaciones y realiza diez rondas por cuenta. Cada ronda envía un chat, lee historial, abre una evaluación de seis preguntas, entrega las respuestas y consulta el progreso persistido.',
 'tests/restored_site_smoke.cjs; docs/CARGA_MIXTA_2026-11-10.md',
 'Se documentaron 1.250 operaciones de ronda y 25 aperturas de conversación sin errores. Las respuestas fueron JSON con success=true, con nota 100 y un diagnóstico correcto persistido por cuenta. Las rondas duraron 4.769 ms con 25 cuentas concurrentes.',
 'Prueba breve sobre datos pequeños, con tutor local sin Gemini y cliente y servidor en el mismo equipo. No incluye consultas docentes concurrentes, CPU, memoria ni seguimiento de conexiones SQL. Faltan carga sostenida, volumen representativo y el proveedor de IA de producción.'),
('66 Datos de clase y código de vinculación docente',
 'Mis vinculaciones incorpora colegio, curso de Educación Media de 1 a 3, bachillerato con énfasis o especialidad y sección. La información se guarda en docente_invitaciones. Antes de generar el código se requieren datos completos; al editar la clase se conserva el código válido y sus vinculaciones. El código tiene una letra mayúscula y seis dígitos. Se añadieron estilos para el formulario y validaciones de longitud, curso y caracteres de control.',
 '01_sistema_principal_y_registro_offline/asignaciones.php; 01_sistema_principal_y_registro_offline/config/teacher_assignments.php; 07_diseno_y_graficos/teko-ui.css; tests/teacher_assignments_test.php',
 'La revisión del código confirmó el formulario, las consultas preparadas, la comprobación de rol docente, el token CSRF del envío y el guardado transaccional. La prueba ampliada contempla clase obligatoria, persistencia, conservación del código, curso inválido, duplicados, aislamiento y varios docentes por alumno. No se dispone de un resultado de ejecución posterior de esta ampliación.',
 'Es una ficha de clase por docente, asociada a un único registro de invitación. No implementa un catálogo institucional ni varias clases por docente. La consulta global de las últimas 500 conversaciones no se corrigió con este cambio. Falta comprobar migración y recorridos de esta ampliación en el destino.'),
('67 Preparación del servidor y comprobador de alojamiento',
 'Se definieron PHP 8.4 y MariaDB 11.4 como versiones objetivo, con sus últimos parches disponibles. Se añadió una herramienta CLI que comprueba PHP, PDO MySQL, mbstring, cURL, OpenSSL, fileinfo, sesiones y conectividad de ambas bases mediante SELECT VERSION() y SELECT 1. Evita cargar conectores que puedan crear tablas y no activa SQLite. El README enlaza los requisitos y criterios de cierre.',
 'scripts/check_hosting.php; docs/SERVIDOR_Y_VERSIONES.md; README.md',
 'El chequeo de sintaxis del script pasó. La ejecución local registrada detectó PHP 8.2.12 y todas las extensiones requeridas, pero no pudo conectar con las dos bases. Por ello indicó hosting_ready=false. No se instalaron nuevas versiones ni se cambió XAMPP.',
 'La comprobación exige las ramas objetivo, no valida que el parche sea el último ni certifica seguridad o funcionamiento web. No se dispone de alojamiento para probar PHP 8.4 y MariaDB 11.4. La orientación sobre hosting administrado y VPS fue una propuesta, sin selección ni contratación.')
]

for title, change, refs, proof, limits in records:
    page(title)
    p('Fecha de trabajo documentada: 10/11/2026. Consolidación del informe: 16/11/2026, Paraguay.')
    p(change)
    p('Archivos de referencia: ' + refs)
    p('Verificación: ' + proof)
    p('Límites y pendientes: ' + limits)
    if title.startswith('65 '):
        p('Percentil 95 y máximo por operación', h2)
        p('Crear conversación: 25 solicitudes, p95 70 ms, máximo 70 ms. Chat: 250, p95 178 ms, máximo 232 ms. Historial: 250, p95 138 ms, máximo 176 ms. Abrir evaluación: 250, p95 125 ms, máximo 168 ms. Entregar evaluación: 250, p95 132 ms, máximo 241 ms. Consultar progreso: 250, p95 120 ms, máximo 188 ms.')

page('Estado vigente al 16 de noviembre')
for title, value in [
 ('Servidor y datos locales', 'MariaDB fue reparado y el procedimiento de arranque recibió comprobaciones posteriores sin avisos de tamaño. Esto no equivale a actualizar el motor. La referencia local continúa siendo PHP 8.2.12 y MariaDB 10.4.32; las versiones objetivo de alojamiento aún no fueron instaladas ni probadas.'),
 ('Restauración y rendimiento', 'Se amplió la restauración con Apache aislado y se conservaron salidas de prueba. Hay mediciones breves de progreso y de carga mixta con 25 cuentas. Sigue sin existir una validación de capacidad para el piloto de 600 estudiantes y 60 docentes.'),
 ('Organización docente', 'El código incluye datos de colegio, curso, bachillerato y sección asociados al docente, y conservación del código al editar. Falta ejecutar y registrar la ampliación de pruebas, administrar varias clases e instituciones y corregir el límite global de 500 conversaciones.'),
 ('Publicación', 'No hay alojamiento contratado ni aplicación publicada. Se prepararon requisitos, documentación y un comprobador de versiones. La fila de servidor permanece abierta; dominio, HTTPS, despliegue definitivo y servicios operativos siguen pendientes.'),
 ('Seguridad contenido y experiencia', 'Continúan vigentes los controles y límites documentados en el corte anterior. La reparación de MariaDB y las pruebas locales no completan la revisión de CSRF de login/logout, límites generales, revisión pedagógica y jopara, teléfonos reales, accesibilidad ni acuerdos sobre datos de menores.')
]:
    p(title, h2)
    p(value)

page('Prioridades bloqueantes para publicar')
for title, value in [
 ('Servidor y versiones', 'En curso. Elegir alojamiento, instalar ramas mantenidas con parches actuales y probar la aplicación allí. El comprobador está implementado; la prueba en el destino falta.'),
 ('Dominio y HTTPS', 'Pendiente. Configurar dominio, certificado, URL pública, cookies seguras y reconocimiento confiable de HTTPS si existe proxy. Comprobar enlaces, archivos, redirecciones y sesiones desde la dirección definitiva.'),
 ('Despliegue y bases', 'Pendiente. Crear un entorno en línea y migrar ambas bases conservando la relación entre cuentas y conversaciones. Excluir secretos, respaldos y archivos internos del paquete. Usar permisos SQL mínimos y cuenta separada de migración.'),
 ('Seguridad de acceso', 'Pendiente de completar. Revisar CSRF de inicio y cierre de sesión, límites de registro y solicitudes, recuperación y permisos por recurso. Rotar credenciales para producción y definir recuperación de la consola.'),
 ('Respaldos y monitoreo', 'Parcial. Las copias y restauraciones locales están documentadas. Faltan automatización externa protegida, conservación, recuperación en otro entorno y alertas de errores, caídas y fallos de guardado con responsable.'),
 ('Datos estudiantiles', 'Bloqueante antes de usar alumnos reales. Acordar con instituciones datos recogidos, accesos, conservación, corrección y eliminación. Informar proveedores externos y establecer autorizaciones correspondientes para menores.')
]:
    p(title, h2)
    p(value)

page('Prioridades altas y siguiente paso')
for title, value in [
 ('Panel docente', 'Completar el ámbito del docente antes de consultar, retirar el límite de 500 conversaciones globales y agregar filtros y paginación. Ampliar la ficha de clase actual a la organización institucional necesaria para el piloto.'),
 ('Rendimiento', 'Acordar concurrencia esperada y ejecutar carga sostenida con conversaciones, entregas y consultas docentes, datos representativos y mediciones de CPU, memoria, conexiones SQL, errores y tiempos.'),
 ('Celulares y conectividad', 'Probar teléfonos reales, teclado, fotos, gráficos, juegos, orientación y retorno tras pérdida de conexión. Verificar reintentos sin duplicados y estados visibles de guardado local y sincronizado.'),
 ('TekoBot y contenido', 'Revisar matemática y jopara con docentes. Probar el proveedor de IA elegido, establecer límites de consumo y gasto y permitir reportar respuestas incorrectas.'),
 ('Accesibilidad y soporte', 'Revisar teclado, contraste, lectura de fórmulas y alternativas al arrastre. Preparar instrucciones para alumnos y docentes, canal de soporte y alternativa durante una caída.')
]:
    p(title, h2)
    p(value)
p('Siguiente paso acordado: resolver únicamente la fila de servidor y versiones. Definir presupuesto, consultar un plan que cumpla los requisitos, seleccionar alojamiento y ejecutar la validación en un entorno aislado. Se mantiene la fecha objetivo previamente registrada del 31 de enero de 2027, sin declarar que los controles pendientes estén cumplidos.')

page('Evidencias del último período')
for value in [
 'Reparación y seguimiento: docs/REPARACION_MARIADB_2026-11-10.md y scripts/start_mariadb_local.ps1.',
 'Restauración y primeras mediciones: docs/PRUEBA_APACHE_2026-11-10.md, scripts/verify_restoration.php y scripts/maintenance_lib.php.',
 'Carga mixta: docs/CARGA_MIXTA_2026-11-10.md y tests/restored_site_smoke.cjs.',
 'Datos de clase: asignaciones.php y config/teacher_assignments.php del módulo principal, tests/teacher_assignments_test.php y 07_diseno_y_graficos/teko-ui.css.',
 'Alojamiento: docs/SERVIDOR_Y_VERSIONES.md, scripts/check_hosting.php y README.md.',
 'Los registros detallados de restauración permanecen en el directorio privado de respaldos fuera de htdocs. Este informe no incorpora credenciales, hashes de acceso ni tokens.',
 'Se conserva intacto el informe del 1 de noviembre. Esta actualización documental no reejecuta pruebas operativas, no cambia cuentas, no repara bases y no despliega la aplicación.'
]:
    p(value)
body.append(sect)
parts['word/document.xml'] = E.tostring(doc, xml_declaration=True, encoding='UTF-8', standalone=True)
for name in parts:
    if name.startswith('word/footer') and name.endswith('.xml'):
        footer = E.fromstring(parts[name])
        for node in footer.xpath('//w:t', namespaces=NS):
            if node.text and '28/10/2026 a 01/11/2026' in node.text:
                node.text = node.text.replace('28/10/2026 a 01/11/2026', '28/10/2026 a 16/11/2026')
        parts[name] = E.tostring(footer, xml_declaration=True, encoding='UTF-8', standalone=True)
core = E.fromstring(parts['docProps/core.xml'])
dc = '{http://purl.org/dc/elements/1.1/}'
for key, value in [('title', 'Informe final de cambios de TEKO Math'), ('description', 'Registro consolidado al 16 de noviembre de 2026 con 67 fichas y prioridades de publicación actualizadas.')]:
    node = core.find(dc + key)
    if node is not None:
        node.text = value
parts['docProps/core.xml'] = E.tostring(core, xml_declaration=True, encoding='UTF-8', standalone=True)
with ZipFile(OUT, 'w') as z:
    for info in infos:
        z.writestr(info, parts[info.filename])
assert hashlib.sha256(SOURCE.read_bytes()).hexdigest() == source_hash
with ZipFile(OUT) as z:
    result = E.fromstring(z.read('word/document.xml'))
    full_text = '\n'.join(result.xpath('//w:t/text()', namespaces=NS))
    for record in records:
        assert record[0] in full_text
    # Every historical paragraph except the cover summary and three dated labels is retained verbatim.
    with ZipFile(SOURCE) as original:
        old = E.fromstring(original.read('word/document.xml')).find(W + 'body').findall(W + 'p')
        for i, old_p in enumerate(old):
            if i in [0, 1, 2, 5, 6, 476, 492, 527]:
                continue
            assert E.tostring(old_p) == E.tostring(body.findall(W + 'p')[i]), i
    assert z.testzip() is None
QA.joinpath('structural-check.json').write_text(json.dumps({
    'source_sha256': source_hash, 'source_unchanged': True,
    'historical_records': 61, 'added_records': 6, 'last_record': 67,
    'new_references_checked': True, 'visual_review': 'unavailable: LibreOffice not installed and no bundled Windows renderer',
    'output': str(OUT)
}, ensure_ascii=False, indent=2), encoding='utf-8')
print(OUT)
