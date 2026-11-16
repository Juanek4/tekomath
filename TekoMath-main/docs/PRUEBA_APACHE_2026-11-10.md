# Primera medición sobre Apache

Se agregó TEKO_RESTORE_APACHE=1 al verificador de restauración para iniciar Apache temporal con PHP en un puerto de loopback y con cuentas SQL limitadas a las copias. Antes de ejecutar los recorridos se verifica por HTTP la identidad de ambas bases. La salida de las pruebas ahora se conserva en http-test-results.log.

La ejecución del 10 de noviembre de 2026 restauró y verificó 27 tablas y 40 filas. Pasaron invitación docente, registro, asignación, conversación, historial y diagnóstico. Se registraron 25 cuentas adicionales en las copias.

| Solicitudes simultáneas de progreso | Errores | Percentil 95 |
| --- | --- | --- |
| 1 | 0 | 18 ms |
| 5 | 0 | 52 ms |
| 10 | 0 | 60 ms |
| 25 | 0 | 107 ms |

Es una sola tanda por nivel contra datos pequeños y con cliente y servidor en el mismo equipo. Solo mide la consulta de progreso. Faltan carga sostenida, mezcla de escrituras y lecturas, datos de tamaño representativo, chat externo y supervisión de recursos para dimensionar el piloto.

Las bases y cuentas temporales fueron eliminadas. No quedaron procesos Apache temporales en la comprobación posterior. mysql.db devolvió OK. mysql.global_priv devolvió OK con un aviso de tamaño de archivo que reapareció. Ese antecedente permanece abierto aunque las escrituras de esta ejecución completaron.

Evidencia privada en C:/xampp1/teko-private/backups/full-20261101-180808-76d842/restore-7e5281f9e4/http-test-results.log.
