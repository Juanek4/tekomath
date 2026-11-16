from pathlib import Path
from datetime import datetime, timezone, timedelta
from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
ROOT=Path(r'C:/xampp1/htdocs/TekoMath-main')
OUT=ROOT/'docs/Informe_cambios_TEKO_Math_2026-10-28.docx'
TZ=timezone(timedelta(hours=-3))
doc=Document();sec=doc.sections[0];sec.page_width=Inches(8.5);sec.page_height=Inches(11);sec.top_margin=sec.bottom_margin=Inches(.7);sec.left_margin=sec.right_margin=Inches(.8)
normal=doc.styles['Normal'];normal.font.name='Calibri';normal.font.size=Pt(11);normal.paragraph_format.space_after=Pt(7)
for name in ['Heading 1','Heading 2']:
 doc.styles[name].font.name='Calibri';doc.styles[name].font.color.rgb=RGBColor.from_string('174D3B')
doc.styles['Title'].font.color.rgb=RGBColor(0,0,0)
sec.header.paragraphs[0].text='TEKO MATH   |   Registro de desarrollo'
footer=sec.footer.paragraphs[0];footer.text='28 de octubre de 2026  ·  America/Asuncion                                      Página '
f=OxmlElement('w:fldSimple');f.set(qn('w:instr'),'PAGE');footer._p.append(f)
def p(t):doc.add_paragraph(t)
def h(t):doc.add_heading(t,1)
def timefor(rel):
 path=ROOT/rel
 return datetime.fromtimestamp(path.stat().st_mtime,TZ).strftime('%d/%m/%Y %H:%M:%S') if path.exists() else 'No disponible'
def item(n,title,rel,body,status):
 doc.add_heading(f'{n:02d} {title}',2)
 p('Fecha y hora de referencia: '+timefor(rel)+' · última modificación del archivo.')
 p(body);p('Archivo de referencia: '+rel);p('Verificación y estado: '+status)
def page(title):doc.add_page_break();h(title)
doc.add_heading('Informe de cambios de TEKO Math',0)
p('Jornada del 28 de octubre de 2026')
p('Preparado para la coordinación del proyecto. Piloto previsto de 600 estudiantes y 60 docentes. Fecha objetivo de preparación: 31 de enero de 2027.')
h('Resultado de la jornada')
p('Se reorganizó el proyecto, se ampliaron las pruebas del tutor, se añadieron evaluaciones y análisis docente, se ajustaron interfaces móviles y se implementaron cuentas docentes por invitación, asignaciones y controles de acceso. Las pruebas HTTP locales confirmaron recorridos de cuentas, aislamiento entre docentes y alumnos, persistencia del tutor y permisos de tareas, materiales y salas.')
p('La plataforma continúa en localhost. Estos resultados no acreditan preparación nacional ni capacidad para 600 conexiones simultáneas. La corrección de todas las calificaciones en el servidor sigue pendiente.')
h('Cómo interpretar fechas y evidencias')
p('Zona horaria: America/Asuncion, UTC−03:00. Las horas de las fichas proceden de la última modificación del archivo señalado, consultada al generar este documento. Un archivo pudo cambiar varias veces: su hora final no reconstruye las horas de cada edición ni de cada prueba. Donde no existe un registro histórico, no se inventa una hora exacta. El orden de las fichas sigue la conversación y no ordena las marcas finales del disco.')
p('Fuentes: conversación de trabajo, archivos actuales y resultados de herramientas registrados durante la sesión. No se dispone de un historial de commits que permita reconstruir cada diferencia. Algunas pruebas iniciales utilizaron respuestas simuladas; las pruebas HTTP finales usaron Apache y MySQL locales. No se incluyeron contraseñas ni tokens privados en este informe.')
p('Hora de elaboración: '+datetime.now(TZ).strftime('%d/%m/%Y %H:%M:%S')+' (Paraguay).')
page('Estructura y evolución del tutor')
item(1,'Eliminación de la carpeta duplicada','README.md','Se movieron módulos, pruebas, documentación y archivos de entrada desde la carpeta interna TekoMath-main a la raíz del proyecto. Se documentó el acceso http://localhost/TekoMath-main/. No se renombró la raíz a TEKO Math.','index.php pasó validación PHP. La primera comprobación HTTP quedó limitada por el entorno; se realizaron pruebas locales posteriores.')
item(2,'Reconocimiento de preguntas y errores de escritura','02_tekobot/TekoBot/Hackaton/services/question_resolver.php','Se añadieron abreviaturas, tolerancia conservadora a una edición en palabras largas e identificación de dudas sobre errores. Se corrigieron confusiones entre la intención del alumno y términos del título, como gráfica, aplicaciones o diferencia.','La suite de 1000 consultas sobre 261 conceptos terminó con 1000 aprobaciones. No evalúa el modelo externo.')
item(3,'Simulaciones para registros paraguayos','02_tekobot/TekoBot/Hackaton/tests/simulation_paraguay.php','Se generaron 6264 casos sintéticos, 2088 por registro de idioma: español, español paraguayo y jopara. Se incorporaron expresiones coloquiales y revisión de contexto, fórmula, progreso y retorno a la lección.','6264 comprobaciones aprobadas. Son variaciones de plantillas; no equivalen a estudiantes reales ni entrenamiento de pesos.')
page('Pedagogía y evidencia de aprendizaje')
item(4,'Adecuación lingüística del tutor','02_tekobot/TekoBot/Hackaton/prompts/system_tutor.txt','Se ampliaron instrucciones para respetar la elección de idioma, evitar estereotipos y utilizar ejemplos paraguayos sin inventar precios ni alterar datos del ejercicio.','Instrucciones guardadas. La naturalidad del jopara y la utilidad pedagógica requieren revisión humana.')
item(5,'Análisis por conversación y concepto','02_tekobot/TekoBot/Hackaton/services/student_analysis.php','Se registran interacciones, pedidos de ayuda, ejemplos y señales de aciertos verificados. El apoyo puede simplificarse según las dudas y los resultados de la prueba breve. Un sí del estudiante no se toma como dominio.','Pasaron pruebas de adaptación, autorreporte, separación por concepto y serialización. El análisis es principalmente determinista, no una evaluación libre del razonamiento.')
item(6,'Pruebas breves de comprensión','02_tekobot/TekoBot/Hackaton/services/assessment_service.php','Se añadieron tres ejercicios en cada una de cuatro unidades: funciones, trigonometría, geometría y combinatoria. Se separan aciertos con ayuda e independientes; las dudas no se califican como errores.','Pruebas de cuatro unidades aprobadas. Banco inicial de 12 ítems, repetible e insuficiente para validar todos los subtemas.')
item(7,'Panel de análisis y corrección de prueba pedagógica','01_sistema_principal_y_registro_offline/docente_tutor_analysis.php','Se añadió al panel docente un informe con señales, apoyo y recomendaciones. Se ajustó el test de ejemplos para comparar la versión localizada en lugar de exigir texto español dentro del jopara.','Prueba pedagógica aprobada sobre 62 subtemas. Sigue pendiente paginar el análisis: existe un límite global de 500 conversaciones.')
page('Interfaz y presentación de los módulos')
item(8,'Base compartida de interfaz','07_diseno_y_graficos/teko-ui.css','Se incorporaron tamaños táctiles, foco visible, ajustes de formularios, reducción de movimiento y estilos compartidos en las pantallas principales. Posteriormente se añadieron estilos para vinculaciones, administración y Teko Desafíos.','Validación de archivos PHP; no constituye revisión visual completa de todas las pantallas.')
item(9,'Chat móvil de TekoBot','02_tekobot/TekoBot/Hackaton/assets/css/mobile.css','Se separaron conversación y gráficos en vistas móviles, compactaron controles y ajustaron altura y campo de escritura. Se corrigieron reglas antiguas que redistribuían el formulario.','Prueba de diseño a 320, 390, 768 y 1365 px aprobada con recursos locales y APIs simuladas. Falta teclado virtual en teléfono real.')
item(10,'Idioma y cabecera del tutor','05_jopamath/jopamath_i18n.js','Se diseñó un selector segmentado, trasladado al menú lateral, y se eliminó el selector duplicado. En móviles se ocultó el nombre de la cabecera y se ocultó Inicio en la bienvenida. Se corrigió la ruta del script lingüístico.','Sintaxis JavaScript comprobada y prueba de diseño repetida. La revisión multilingüe humana sigue pendiente.')
item(11,'TekoArcade y Teko Desafíos','03_teko_arcade/TEKOARCADE/JUEGOS/src/pages/HomePage.css','Se intentó corregir el título superpuesto de Arcade mediante cambios en fuente y archivos publicados. El usuario informó que el resultado no se veía. También se renombró visualmente TekoBeta a Teko Desafíos y se añadió una tarjeta con accesos a misiones y Live.','La última corrección de Arcade no tuvo confirmación visual. Hay parches en dist que deben integrarse en una compilación reproducible. Teko Desafíos pasó validación PHP, no revisión visual completa.')
page('Cuentas y asignación de estudiantes')
item(12,'Inicialización de la base principal','01_sistema_principal_y_registro_offline/config/database.php','Se confirmó que faltaba teko_math mientras existía kyhyjey_db. Se añadió creación ante el error específico de base inexistente y se verificó la tabla de usuarios en MySQL local.','Conexión comprobada; se observaron cuatro cuentas demo. Su eliminación o deshabilitación en producción sigue pendiente.')
item(13,'Registro público restringido','01_sistema_principal_y_registro_offline/registro_process.php','El registro público crea solo estudiantes, independientemente del rol recibido. Se retiró la opción docente, se añadió token del formulario y se ocultaron detalles internos de errores. La sesión rota su identificador al iniciar acceso.','Pruebas de política y PHP aprobadas. El intento HTTP de registrarse como docente terminó como estudiante.')
item(14,'Vinculaciones entre docentes y alumnos','01_sistema_principal_y_registro_offline/config/teacher_assignments.php','Se crearon tablas de vínculos e invitaciones de asignación. Un docente puede tener varios alumnos y un alumno varios docentes. Se añadieron pantallas para compartir código, vincularse y retirar una relación.','Pruebas MySQL y HTTP aprobadas; sin reparto automático ni creación de las 660 cuentas del piloto.')
item(15,'Códigos cortos de vinculación','01_sistema_principal_y_registro_offline/asignaciones.php','Los códigos pasaron a una letra y seis cifras, aceptando minúsculas. Se conserva la unicidad y se reemplaza el formato largo al consultar el código docente. Las vinculaciones existentes permanecen.','Pruebas de asignación aprobadas. Caducidad, rotación y límites contra intentos masivos siguen pendientes.')
page('Consola e identidad del tutor')
item(16,'Consola administrativa','01_sistema_principal_y_registro_offline/admin.php','Se añadió un botón al login principal y una consola con autenticación, sesión administrativa de 30 minutos, token de formulario y limitación básica por sesión. El usuario inicial se definió según la solicitud; la contraseña se verifica mediante hash.','Acceso administrativo probado por HTTP. La limitación por sesión no sustituye un control persistente de intentos. Gestión y cambio de credenciales pendientes.')
item(17,'Creación docente por invitación','01_sistema_principal_y_registro_offline/config/admin_invitations.php','La consola genera un enlace vinculado al correo, válido siete días y de un solo uso. El token se guarda mediante hash. El docente elige su contraseña en docente_registro.php y usa el acceso habitual. No se envían correos automáticamente.','Pruebas MySQL y HTTP: alta, correo correcto, contraseña hasheada y rechazo de reutilización. Revocación administrativa y prueba específica de vencimiento pendientes.')
item(18,'Flujo completo de cuentas y listados','tests/pilot_accounts_http_test.cjs','Se verificaron dos invitaciones, dos docentes, dos alumnos, vinculación, aislamiento de listados, denegación anónima y de alumno, rol manipulado y token inválido.','PASS con Apache y MySQL reales. Se eliminaron cuentas temporales. No es una prueba visual ni de carga.')
item(19,'Vinculación de identidades de TekoBot','02_tekobot/TekoBot/Hackaton/config/student_identity.php','Se reprodujo un error de clave foránea al crear chats con cuentas nuevas. Se añadió portal_student_links, que vincula la cuenta principal con una identidad independiente del tutor, y se ajustó el informe docente.','Dos alumnos nuevos crearon y recuperaron mensajes. Se rechazó acceso cruzado aun manipulando student_id. Los chats antiguos no se reasignaron por coincidencia numérica.')
page('Permisos y protección de solicitudes')
item(20,'Permisos de tareas y materiales','01_sistema_principal_y_registro_offline/config/resource_access.php','Se filtraron listados, detalle y progreso según propiedad docente y vinculación del alumno. Se retiró el borrado alternativo sin propietario; la eliminación de resultados asociados ocurre después de autorizar y en transacción. Se retiró el sembrado fijo de demos en el endpoint de tareas.','Prueba HTTP de dos grupos aprobada: acceso propio, bloqueo ajeno, preservación de resultados ante borrado rechazado y eliminación autorizada.')
item(21,'Permisos de Teko Live','06_teko_live/access.php','Solo el propietario maneja la sala. Entrar exige vinculación docente y consultar o responder exige participación. Se bloqueó el banco de respuestas a estudiantes y el uso de paquetes privados ajenos.','Prueba HTTP aprobada para creación, entrada, estado, respuesta y cierre, con rechazo entre grupos. Concurrencia sobre alumnos_json requiere pruebas adicionales.')
item(22,'Tokens de peticiones de modificación','01_sistema_principal_y_registro_offline/config/api_security.php','Se añadió un token por sesión para mutaciones de tareas, materiales, salas y progreso de quizzes, y un adaptador fetch en las pantallas principales. Live exige POST para acciones de modificación.','Se repitieron las pruebas HTTP de tareas, materiales y Live. Se comprobó rechazo sin token y por GET en Live. No cubre automáticamente todos los endpoints del sistema.')
item(23,'Auditoría y documentación del piloto','docs/AUDITORIA_PILOTO_2027.md','Se documentaron bloqueos, alcance de 600 alumnos y 60 docentes, prioridades, calendario hasta enero y criterios de habilitación. Se crearon informes específicos de cuentas, asignaciones y permisos.','Documentación disponible. El calendario es una propuesta y no una certificación de capacidad o seguridad.')
page('Validaciones y trabajo pendiente')
h('Resultados de pruebas registrados')
for t in [
'1000 simulaciones conceptuales locales aprobadas sobre 261 conceptos.',
'6264 simulaciones paraguayas aprobadas en tres registros lingüísticos.',
'62 subtemas en la prueba pedagógica de lecciones y ejemplos.',
'Cuatro unidades en las pruebas breves de evaluación y sus pistas.',
'Adaptación del análisis y persistencia de su estado aprobadas.',
'Pruebas de diseño en cuatro anchos aprobadas con APIs simuladas.',
'Flujos HTTP reales aprobados para cuentas, tutor, tareas, materiales y Live.',
'Comprobaciones PHP y JavaScript realizadas según los archivos modificados.'
]:doc.add_paragraph(t,style='List Bullet')
h('Pendientes que no deben darse por resueltos')
for t in [
'Corregir en servidor los quizzes y misiones que todavía reciben contadores del navegador.',
'Revisar todas las rutas restantes de API, límites de uso y exposición de datos; no existe una auditoría integral cerrada.',
'Revisar visualmente pantallas autenticadas en móviles reales y confirmar el ajuste pendiente de Arcade.',
'Resolver guardado de Arcade por cuenta y sincronización; eliminar métricas demo no sustentadas.',
'Ampliar evaluación por subtema y revisar matemáticas y jopara con docentes.',
'Migrar chats históricos solo con evidencia de identidad; paginar informes docentes.',
'Preparar alojamiento, HTTPS, backups restaurables, monitoreo, soporte y privacidad institucional.',
'Probar concurrencia y carga acordadas para los 600 alumnos y 60 docentes.',
'Cambiar la configuración inicial de administración por gestión segura de credenciales antes de publicar.'
]:doc.add_paragraph(t,style='List Bullet')
p('Este documento cierra el registro de la jornada hasta su elaboración. No se continuó el desarrollo funcional mientras se preparaba el informe.')
doc.core_properties.title='Informe de cambios de TEKO Math del 28 de octubre de 2026';doc.core_properties.subject='Registro de desarrollo y verificación para el piloto';doc.core_properties.author='TEKO Math'
doc.save(OUT);print(OUT)
