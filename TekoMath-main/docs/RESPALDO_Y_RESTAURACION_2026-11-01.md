# Respaldo, restauración y verificación de MariaDB

Fecha: 01/11/2026, Paraguay.

## Resultado
Se aplicó exactamente la contraseña administrativa elegida por el usuario, se respaldaron las dos bases y la configuración privada, y se verificó una restauración funcional en bases aisladas. La instalación continúa en localhost y no se activó producción.

La verificación final recuperó 27 tablas y 40 registros del estado actual. Coincidieron el SHA-256 de la estructura y el SHA-256 del contenido canónico por tabla. El sitio restaurado permitió autenticarse con la contraseña nueva, crear una invitación y un docente de prueba, registrar un estudiante de prueba, asignarlo al docente, conversar con TekoBot, recuperar el historial y guardar un diagnóstico de seis preguntas con resultado correcto. Estas altas se hicieron en las copias, que luego se eliminaron.

## Contraseña administrativa
El cambio se hizo mediante mantenimiento local expresamente autorizado. Se actualizó el hash, se incrementó la versión de consola y se marcó la credencial como elegida, invalidando sesiones administrativas previas. La clave anterior se comprobó como rechazada y la elegida como aceptada. No se incluye la contraseña en este documento ni en archivos del proyecto.

La contraseña indicada tiene ocho caracteres y se aplicó como excepción de mantenimiento solicitada por el propietario. No se redujo la política general del formulario administrativo, que sigue exigiendo al menos doce caracteres para futuras rotaciones. La prueba de cambio administrativo se ejecutó dentro de una transacción revertida y confirmó que la contraseña elegida permanecía vigente.

El hash de arranque anterior se retiró del archivo privado activo. Las copias previas al cambio permanecen protegidas y contienen el estado histórico de ese momento; no deben usarse para reinstalar inadvertidamente una contraseña anterior.

## Respaldos privados
Todos están fuera de htdocs, bajo C:/xampp1/teko-private/backups, con acceso restringido por las ACL de ese directorio.

- full-20261101-175604-d9890e: estado previo al cambio de contraseña.
- full-20261101-175652-e0836c: estado posterior al cambio; contiene también la comprobación del reinicio y la copia física en frío de permisos.
- full-20261101-180808-76d842: respaldo final posterior a las regresiones. Esta es la copia recomendada de esta intervención y su restauración se verificó.

Cada respaldo completo contiene main.sql, tutor.sql, teko-math.private.php, my.ini, mysql-privileges.sql y manifest.json. El manifiesto guarda fecha de Paraguay, hashes de los archivos, cantidades de registros y huellas por tabla. Las contraseñas de usuarios permanecen como hashes dentro de SQL, pero la configuración privada contiene credenciales SQL reales: estos respaldos son confidenciales.

scripts/backup_databases.php usa una barrera de lectura breve para que ambos volcados y el manifiesto correspondan al mismo punto lógico. Libera el bloqueo aun si ocurre un error. El archivo temporal de opciones del cliente se elimina después de usarlo. Se comprobaron motores InnoDB y ausencia de vistas, rutinas, eventos y disparadores en las bases actuales. La herramienta rechaza rutinas/eventos/disparadores en lugar de omitirlos silenciosamente.

## Restauración aislada
scripts/verify_restoration.php valida los hashes antes de importar. Crea dos bases con nombres teko_restore_* y usuarios temporales limitados a esas bases; el importador no tiene acceso a las bases reales. No se restauran las tablas globales mysql sobre el servidor real.

La prueba web usa un servidor PHP temporal, enlazado solo a 127.0.0.1, con un puerto disponible y una comprobación de identidad mediante nonce y nombres de bases antes de ejecutar altas o mensajes. La configuración de prueba sustituye nombres y credenciales SQL y deshabilita Gemini para evitar llamadas externas. Las tablas se comparan antes de agregar los datos de prueba del recorrido HTTP.

Las bases, las cuentas SQL temporales y el servidor de prueba se eliminan al terminar, también en caso de error. El resultado verification.json solo se declara exitoso después de completar la limpieza. Se conservan el resultado y el log de la prueba dentro del directorio privado. Se comprobó que no quedaban bases ni cuentas temporales de restauración/probe.

La copia final se verificó en restore-4ed9e084eb. Su verification.json registra 27 tablas, 40 registros, HTTP smoke passed y limpieza de las dos bases y cuentas temporales. La prueba negativa backup_integrity_test.cjs alteró un archivo ficticio en un directorio nuevo y confirmó que el restaurador lo rechaza antes de importar; el respaldo real no se modificó.

## Investigación de MariaDB
Se confirmó primero que la instancia existente ya estaba activa; el intento de arrancar otra instancia no se usó como evidencia de corrupción de InnoDB. Los archivos db.MAD y global_priv.MAD no tenían enlaces duros adicionales. En las comprobaciones en línea de esta intervención, mysql.db y mysql.global_priv ya respondieron OK sin la advertencia de tamaño anterior.

Para comprobarlo tras un cierre real, se bloqueó temporalmente el acceso PHP mediante una bandera privada. MySQL se cerró con mysqladmin shutdown y se esperó la salida de su proceso. Se copiaron en frío db y global_priv (frm/MAI/MAD) y aria_log_control dentro del respaldo privado posterior al cambio. Se ejecutó aria_chk --read-only --check con el datadir correcto, sin reparar ni reemplazar ninguna de esas tablas. Ambas pasaron.

Apache está iniciado como proceso independiente, sin servicio Apache2.4 instalado; no se forzó su terminación. El acceso PHP se protegió durante la ventana de base apagada, de modo que nuevas peticiones no accedieran a SQL ni activaran otro almacenamiento. La bandera devuelve HTTP 503, Retry-After: 60 y no-store, antes de acceder a datos. CLI puede seguir haciendo mantenimiento y check_runtime informa si la bandera quedó activa.

Después del arranque limpio, las 27 tablas educativas y las dos tablas de permisos pasaron CHECK TABLE EXTENDED: 29 respuestas OK, sin advertencias. Coincidieron las huellas del contenido educativo y de todas las filas de db/global_priv con las guardadas antes del cierre. Se retiró la bandera y la web volvió a estar disponible. Las pruebas posteriores de creación/eliminación de usuarios SQL temporales también terminaron con ambas tablas de permisos en OK.

No fue necesario reparar global_priv ni reemplazar archivos educativos. La advertencia previa no se reprodujo en estas comprobaciones, pero no se atribuye con certeza su origen a hardware, antivirus o una causa específica. El procedimiento no es una garantía de estabilidad indefinida ni una revisión del disco. El arranque conserva una advertencia separada sobre la abreviatura key_buffer de my.ini; no es un error de integridad y no se alteró la memoria del servidor en esta intervención.

Referencia técnica del modo de comprobación y de sus opciones: [documentación oficial de MariaDB sobre aria_chk](https://mariadb.com/docs/server/clients-and-utilities/aria-clients-and-utilities/aria_chk).

## Regresiones posteriores
PASS en recuperación de contraseñas, invitaciones/registro/asignaciones, tareas/materiales y calificación en servidor, Live, identidad y persistencia de TekoBot, y progreso por cuenta de Arcade. PASS en configuración de producción con usuarios DML, aceptación de la credencial administrativa elegida, rechazo de HTTP inseguro y respuesta controlada de mantenimiento. PASS en bloqueo de archivos privados y aislamiento de las cuentas SQL reales.

check_runtime.php devuelve configuration_ready=true y pending vacío en el modo local actual. Esto solo expresa el resultado de sus controles de configuración; no declara publicada o completamente auditada la plataforma. Los cambios nuevos de código son las herramientas de respaldo/restauración, su biblioteca CLI, las pruebas de restauración e integridad y la bandera privada de mantenimiento; se actualizó la prueba de producción para la credencial ya cambiada.

## Uso posterior
Para crear otro respaldo local: ejecutar `C:/xampp1/php/php.exe scripts/backup_databases.php` desde la raíz del proyecto. Para verificarlo: `C:/xampp1/php/php.exe scripts/verify_restoration.php RUTA_DEL_RESPALDO`, con TEKO_ADMIN_TEST_PASSWORD definido temporalmente en el proceso de prueba. No escribir la contraseña en un archivo del proyecto y retirar esa variable después. Los comandos requieren permisos de mantenimiento locales, distintos de los usuarios de ejecución de la web.

Estas herramientas están limitadas al entorno local y presuponen que ambas bases están en el mismo servidor. El manifiesto usa una comparación en memoria adecuada al tamaño actual; la barrera de lectura no debe trasladarse sin evaluación a una instalación nacional con actividad continua. Para producción hay que planificar copias del proveedor, retención y recuperación a su escala.

Los respaldos conservados están en el mismo equipo. Falta trasladar una copia cifrada a otro medio o ubicación y definir frecuencia y retención. No se hizo una restauración de las cuentas globales mysql ni una recuperación completa sobre un equipo nuevo. Tampoco se ha hecho aún la prueba de carga de los 600 estudiantes y 60 docentes; esa es la siguiente fase.
