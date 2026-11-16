# Reparación local de MariaDB

## Seguimiento del fallo recurrente

La revisión posterior encontró texto de arranque y apagado de MariaDB dentro de los archivos MAD de permisos. Esos mensajes explican el tamaño adicional observado. La reparación inicial de las tablas era insuficiente mientras el arranque desacoplado reutilizaba las salidas de consola.

Se hizo una nueva copia física privada con MariaDB detenido, se repararon global_priv y db y se inició el servidor con --console y redirección explícita de entrada, salida y errores. Los mensajes ahora se escriben en C:/xampp1/teko-private/mysql-stdout.log y mysql-stderr.log.

El inicio corregido queda en scripts/start_mariadb_local.ps1. Debe ejecutarse desde PowerShell con permisos suficientes y únicamente con MariaDB detenido. Rechaza otra instancia existente y verifica la ubicación del ejecutable. El procedimiento previo con Start-Process sin redirección queda sustituido por este script. El inicio desde el panel de XAMPP no fue modificado ni validado en esta intervención.

Pasaron tres ciclos de creación, permisos y eliminación de usuarios, seguidos cada uno de un apagado y arranque limpio. Todas las comprobaciones devolvieron OK sin avisos de tamaño. Luego pasó otra restauración aislada sobre Apache, incluidas las tandas de consultas de progreso. Al terminar ambas tablas siguieron OK sin avisos y quedaron cero usuarios temporales. Evidencia privada en restore-bb4a9db81a dentro del respaldo de referencia.

Una primera versión del script calculaba incorrectamente la raíz de XAMPP. La revisión automática bloqueó su ejecución antes de detener el servicio. Se corrigió y se verificaron las rutas antes de realizar los ciclos.

Las secciones siguientes conservan el diagnóstico histórico anterior a esta corrección.

Fecha de intervención: 10 de noviembre de 2026.

## Fallo observado

La creación de usuarios temporales había fallado con error 22 al escribir el registro Aria y posteriormente con error 1032 en mysql.global_priv. La revisión fuera de línea confirmó una inconsistencia en el bitmap de la página 1 y la marca de tabla dañada.

## Intervención

Con MariaDB detenido se copiaron global_priv, db y los registros Aria a un directorio privado aria-repair con fecha y hora. Su ruta exacta quedó en C:/xampp1/teko-private/last-aria-repair.txt.

Se ejecutó aria_chk --recover --backup sobre global_priv. Recuperó ocho registros frente a siete declarados. Tras arrancar, se completó REPAIR TABLE para global_priv y db y se recargaron los permisos. Se eliminaron los dos usuarios temporales identificados de las pruebas fallidas.

## Verificación

- Creación, concesión de permisos y eliminación de usuario y base desechables completadas.
- Reinicio limpio completado. Tras el reinicio apareció un aviso de tamaño en mysql.db, que no se reprodujo en la comprobación final después de las operaciones.
- Restauración aislada verificada con 27 tablas y 40 filas del respaldo de referencia. Pasaron los recorridos de administrador, invitación docente, registro estudiantil, asignación, conversación, historial y diagnóstico.
- La prueba opcional de progreso con tandas de 1, 5, 10 y 25 cuentas terminó sin errores. El ejecutor descarta la salida estándar del subproceso, por lo que no se conservaron las latencias. Se ejecutó con el servidor integrado de PHP y no certifica la capacidad de Apache ni del piloto.
- Cero cuentas temporales al terminar. Comprobación final de mysql.db y mysql.global_priv con estado OK.
- Ambas conexiones de la aplicación disponibles. Configuración local lista y mantenimiento retirado.

Evidencia de restauración privada: C:/xampp1/teko-private/backups/full-20261101-180808-76d842/restore-22fcc6c07f/verification.json.

## Límite del diagnóstico

La corrupción observada quedó reparada y las escrituras se verificaron después. La causa original del error de escritura del registro Aria sigue sin establecerse. El aviso transitorio de tamaño obliga a conservar este antecedente si reaparece el fallo.
