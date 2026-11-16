# Verificación del flujo de cuentas y asignaciones

Fecha: 28/10/2026. Ejecutado contra Apache y MySQL locales mediante peticiones HTTP reales, manteniendo cookies separadas para cada cuenta. No es una prueba visual de navegador ni de carga.

Resultado: PASS.

- Acceso a la consola de administración.
- Generación de dos invitaciones privadas y creación de dos docentes.
- Rechazo al reutilizar una invitación.
- Inicio de sesión docente con destino al panel correcto.
- Registro de dos alumnos, incluso enviando un rol docente manipulado: ambos permanecen estudiantes.
- Generación automática de códigos y vinculación de cada alumno con su docente.
- Consulta real de api_estudiantes.php: cada docente ve únicamente a su alumno.
- Denegación a un alumno que intenta consultar el listado docente.
- Denegación de acceso anónimo a vinculaciones.
- Rechazo de una vinculación con token CSRF falsificado.
- Limpieza de las cuatro cuentas temporales e invitaciones al finalizar.

Script: tests/pilot_accounts_http_test.cjs. La contraseña administrativa se recibe por variable de entorno y no está escrita en el script.

Pendiente: aspecto visual y accesibilidad, recorrido completo de tareas e informes, vinculación de identidades con TekoBot, permisos del resto de endpoints, expiración/revocación de invitaciones y carga de 600 alumnos/60 docentes. Este resultado no demuestra que esos puntos estén resueltos.
