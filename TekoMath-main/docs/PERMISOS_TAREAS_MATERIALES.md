# Permisos de tareas y materiales

Verificado el 28/10/2026 con Apache y MySQL locales. Los docentes consultan sus recursos; los estudiantes consultan recursos de sus docentes vinculados. Un alumno sin vinculación no obtiene recursos de otros grupos. El detalle de misión y el guardado de progreso exigen acceso a la tarea; el alumno se toma de la sesión. Los reportes de misiones se limitan al propietario de la tarea y a sus estudiantes asignados.

Se eliminó el borrado alternativo sin propietario en tareas y materiales. El borrado de progreso asociado a tareas se hace después de autorizar al propietario, dentro de una transacción. Se retiró la creación automática de misiones demo para el docente fijo 2 en el endpoint.

Prueba: tests/resource_permissions_http_test.cjs. Resultado PASS: creación de tareas/materiales, listados por grupo, detalle ajeno denegado, progreso ajeno denegado, progreso propio guardado, borrado ajeno denegado con conservación del progreso y borrado propio permitido. Se reutiliza el flujo real de invitaciones y cuentas, con limpieza al finalizar.

Límites: no demuestra corrección de notas (los contadores enviados por el navegador siguen pendientes de sustitución por corrección del servidor), protección CSRF de estos endpoints, permisos de Live ni revisión visual. Esos puntos requieren trabajo separado.
