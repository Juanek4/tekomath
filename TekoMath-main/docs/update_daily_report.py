from pathlib import Path
from datetime import datetime,timezone,timedelta
from docx import Document
from docx.shared import Pt
from docx.oxml import OxmlElement
ROOT=Path(r'C:/xampp1/htdocs/TekoMath-main');original=ROOT/'docs/Informe_cambios_TEKO_Math_2026-10-28.docx';out=ROOT/'docs/Informe_cambios_TEKO_Math_2026-10-28_actualizado.docx'
doc=Document(original);tz=timezone(timedelta(hours=-3));now=datetime.now(tz).strftime('%d/%m/%Y %H:%M:%S')
for para in doc.paragraphs:
 if para.text=='Pendientes que no deben darse por resueltos':para.text='Pendientes registrados en el primer corte'
 if para.text.startswith('Este documento cierra el registro de la jornada'):para.text='El primer corte del informe termina aquí. La actualización siguiente registra los trabajos posteriores y reemplaza los estados pendientes que hayan cambiado.'
first=doc.paragraphs[1];node=OxmlElement('w:p');first._p.addnext(node)
from docx.text.paragraph import Paragraph
Paragraph(node,first._parent).add_run('Actualización consolidada al '+now+' · Hora de Paraguay. Consultar el estado vigente al final del documento.').bold=True
def p(x):doc.add_paragraph(x)
def page(x):doc.add_page_break();doc.add_heading(x,1)
def item(n,title,rel,change,proof,limits):
 doc.add_heading(str(n)+' '+title,2)
 path=ROOT/rel;mtime=datetime.fromtimestamp(path.stat().st_mtime,tz).strftime('%d/%m/%Y %H:%M:%S') if path.exists() else 'No disponible'
 p('Última modificación del archivo de referencia: '+mtime+' · Paraguay.')
 p(change);p('Archivo de referencia: '+rel);p('Verificación: '+proof);p('Límites: '+limits)
page('Actualización posterior al primer informe')
p('Corte de actualización: '+now+' (America/Asuncion, UTC−03:00). Se conservan las fichas iniciales como registro histórico. Los estados vigentes de este anexo prevalecen sobre los pendientes mencionados en el primer corte. Las horas de archivo son últimas modificaciones verificables, no horas exactas de implementación o ejecución de pruebas.')
item(24,'Corrección de quizzes en servidor','01_sistema_principal_y_registro_offline/config/quiz_grading.php','El servidor crea el intento, guarda la clave de corrección, recibe una opción por pregunta y calcula el resultado. La definición pública inicial no incluye respuestas ni explicación. Se ignoran contadores del navegador, se exige terminar la prueba y se rechaza un segundo guardado del mismo intento. El banco pasó de JavaScript a un archivo PHP.','Pruebas HTTP aprobadas con contadores adulterados, ausencia de claves iniciales y rechazo del guardado duplicado.','El intento vive en la sesión y vence a las dos horas. Iniciar otro quiz reemplaza el anterior en la misma sesión. No se recalcularon notas históricas ni se completó la revisión de diagnósticos antiguos del tutor.')
item(25,'Primera corrección de misiones en servidor','01_sistema_principal_y_registro_offline/config/mission_grading.php','Se sustituyó el porcentaje enviado por el cliente por una corrección basada en la definición de la tarea. La rúbrica usa la primera respuesta de cada ítem. Esta primera versión recibía las opciones al finalizar; luego fue sustituida por el registro durante la actividad descrito a continuación.','Pruebas HTTP de guardado autorizado y conservación de permisos aprobadas.','Es un hito intermedio. No debe interpretarse como el estado final del sistema de misiones.')
page('Registro y recuperación de misiones')
item(26,'Respuestas de misión persistidas por ítem','01_sistema_principal_y_registro_offline/config/mission_attempts.php','Se creó mission_attempts con identidad, tarea, copia de la definición, respuestas, vencimiento y finalización. El servidor corrige cada opción, exige orden de ejercicios y usa identificadores de petición para reenvíos idempotentes. La vista del alumno recibe la definición sin marcas de corrección ni feedback de opciones antes de elegir. El cierre calcula la nota con el historial guardado, no con un JSON final del navegador.','Pruebas HTTP aprobadas para ausencia de claves en el inicio, cierre incompleto rechazado, respuestas registradas y reenvíos sin duplicación. Validación PHP y JavaScript correcta.','Los reintentos y pistas no constituyen evidencia de dominio independiente. Falta revisión visual completa de todas las plantillas. Las métricas de pistas y tiempo no están verificadas por este registro.')
item(27,'Recuperación del intento vigente','04_diagnostico_y_rutas_de_aprendizaje/misiones.js','Al reabrir se recupera el intento activo del mismo alumno y tarea, sus respuestas y el próximo ítem pendiente. Se restaura etapa, paso y contadores derivados del historial. Si todos los ejercicios están resueltos se abre el cierre. La selección del intento se serializa para evitar duplicados de inicio.','Pruebas HTTP comprobaron el mismo ID antes y después de responder, historial persistido y avance al siguiente ítem.','No se extiende el vencimiento de dos horas. El tiempo de actividad, pistas y autocorrecciones no se recuperan como mediciones verificadas. Falta probar concurrencia sostenida y teléfonos físicos, estos últimos pospuestos por acuerdo.')
page('Calificaciones y experiencia de gestión')
item(28,'Historial de oportunidades de misión','01_sistema_principal_y_registro_offline/historial_misiones.php','Se creó mission_results con una fila por intento finalizado y restricción de unicidad. La inserción se hace en la transacción de cierre. Alumno y docente autorizado pueden consultar resultados paginados, con primera, última y mejor nota y fecha de Paraguay. Los accesos están en ambos escritorios.','Pruebas reales confirmaron dos oportunidades conservadas, rechazo de cierre duplicado y aislamiento entre dos grupos.','No se eligió nota definitiva. El resumen anterior sigue mostrando el último resultado. No se importaron notas antiguas sin evidencia y falta acordar retención de resultados.')
item(29,'Integración visual de pantallas nuevas','07_diseno_y_graficos/teko-ui.css','Asignaciones, consola, registro docente e historial adoptaron los tokens existentes de tipografía y color, bordes oscuros, esquinas redondeadas y sombras sólidas. Se estilizaron código docente, botones, tarjetas y avisos; se ajustaron textos largos y controles móviles.','Validación PHP y prueba con Edge a 390 y 1365 px aprobadas. Se revisaron capturas móviles; cuentas y recursos temporales eliminados.','La revisión usó sesiones reales en asignaciones, historial y consola; el registro por invitación fue revisado visualmente como formulario. La prueba física en teléfono se pospuso, no se canceló para el piloto.')
item(30,'Consultas agrupadas del historial','01_sistema_principal_y_registro_offline/historial_misiones.php','Se retiraron las consultas individuales por tarjeta y se agruparon las comparaciones de alumno, misión y docente. Una página llena pasa de hasta 32 a hasta 3 consultas de lectura de datos, sin contar conexión ni inicialización de tablas. No cambió la presentación ni la rúbrica.','Validación PHP y pruebas HTTP de historial y separación entre grupos aprobadas.','La reducción de consultas no es una medición de latencia ni acredita 600 conexiones simultáneas. Faltan pruebas de carga y planes SQL en el alojamiento definitivo.')
page('Arcade y preparación para publicación')
item(31,'Progreso de Arcade separado por cuenta','01_sistema_principal_y_registro_offline/api_arcade_progress.php','Se creó una instantánea de progreso por usuario autenticado con revisión y token CSRF. Arcade espera la identificación antes de renderizar, utiliza caché local v2 por usuario y sincroniza mediante una cola. Revisiones antiguas y cambios de cuenta rechazan la escritura. Un indicador distingue guardado y pendiente. El progreso compartido antiguo no se importó ni eliminó. Se retiraron errores conceptuales predeterminados del estado inicial.','Prueba HTTP con dos alumnos aprobada: persistencia, aislamiento, ID manipulado rechazado, revisión antigua rechazada y token requerido. Sintaxis PHP y JavaScript publicados comprobada.','Pendiente partida completa, cortes reales de red y cambio de cuenta entre pestañas. Los conflictos se conservan pero no tienen resolución guiada. Los puntos siguen originándose en el navegador; no son notas verificadas. Se actualizó fuente y dist sin compilación TypeScript completa.')
item(32,'Decisiones sobre publicación y teléfonos','docs/ARCADE_PROGRESO_CUENTAS.md','Se acordó seguir en localhost y cerrar errores críticos antes de publicar para participantes. Puede prepararse después un entorno restringido para pruebas. La comprobación física de teléfonos fue pospuesta. Se explicaron requisitos de PHP, MySQL o MariaDB, HTTPS, migración, respaldos y carga.','Decisiones y recomendaciones registradas en la conversación; no hubo despliegue ni contratación.','No se eligió proveedor, dominio ni plan SQL gratuito. No se validó capacidad de alojamiento. El plazo objetivo continúa siendo el 31 de enero de 2027 para el piloto de 600 alumnos y 60 docentes.')
page('Estado vigente de la lista de prioridades')
rows=[
('Registro público docente','Resuelto en el flujo probado','Solo estudiantes en registro público; docentes por invitación administrativa.'),
('Identidad entre las dos bases','Parcial','Cuentas nuevas vinculadas a TekoBot; chats históricos pendientes.'),
('ID del alumno y permisos de endpoints','Parcial','Flujos revisados protegidos; falta inventario y cierre del resto de endpoints.'),
('Calificación desde el navegador','Parcial','Quizzes y misiones corrigen en servidor; diagnósticos antiguos y métricas de Arcade pendientes.'),
('Progreso compartido de Arcade','Implementado con verificación parcial','Cuenta y servidor separados; falta partida completa, conflictos y compilación.'),
('Cuentas demo y configuración inicial','Pendiente','Deshabilitar demo y gestionar credenciales antes de publicación.'),
('Alumnos visibles globalmente','Parcial','Vínculos y filtros implementados; institución, curso y sección pendientes.'),
('Análisis docente limitado a 500 chats','Pendiente','La paginación de misiones no resuelve el límite de TekoBot.'),
('Banco inicial de 12 ejercicios','Pendiente','Ampliar por subtema y revisar con docentes.'),
('Compilación reproducible','Pendiente','Eliminar dependencia de parches manuales en dist.'),
]
for title,state,detail in rows:
 doc.add_heading(title,2);p(state+'. '+detail)
page('Evidencias y próximos controles')
for title,path in [
('Calificaciones y recuperación','docs/CALIFICACIONES_SERVIDOR.md'),
('Progreso de Arcade por cuenta','docs/ARCADE_PROGRESO_CUENTAS.md'),
('Pruebas de recursos y notas','tests/resource_permissions_http_test.cjs'),
('Prueba visual de gestión','tests/management_visual_test.cjs'),
('Pruebas de Arcade por cuenta','tests/arcade_accounts_http_test.cjs'),
('Pruebas de Teko Live','tests/live_permissions_http_test.cjs'),
('Auditoría inicial del piloto','docs/AUDITORIA_PILOTO_2027.md')]:
 p(title+' — '+path)
doc.add_heading('Pendientes antes de un piloto público',1)
for text in [
'Completar y verificar la compilación de Arcade y su flujo real de juego, guardado, cambio de usuario y recuperación.',
'Cerrar endpoints pendientes, cuentas demo, protección de archivos, control de consumo y gestión de credenciales.',
'Definir copia de seguridad y ensayar restauración; preparar entorno de pruebas publicado con HTTPS.',
'Ampliar contenido evaluable y validar matemáticas, jopara, accesibilidad y utilidad de los informes con docentes.',
'Probar carga acordada y soporte operativo para 600 estudiantes y 60 docentes; definir concurrencia real.',
'Revisar privacidad, autorizaciones institucionales, retención y datos enviados a proveedores externos.',
'Retomar la comprobación física en celulares antes del piloto.']:
 doc.add_paragraph(text,style='List Bullet')
p('No se realizaron cambios funcionales durante la elaboración de esta actualización. El informe describe el estado registrado; no convierte verificaciones parciales en garantías de seguridad, rendimiento o preparación nacional.')
doc.core_properties.comments='Actualizado al '+now+' America/Asuncion. Original preservado.'
doc.save(out);print(out)
