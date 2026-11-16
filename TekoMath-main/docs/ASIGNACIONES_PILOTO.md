# Asignaciones del piloto

Población prevista: 600 estudiantes y 60 docentes. No se fuerza reparto de diez alumnos ni se crean usuarios ficticios. La relación es de muchos a muchos.

1. El docente entra al escritorio y abre «Gestionar mis estudiantes».
2. Comparte el código con sus alumnos, por un canal de la institución.
3. El alumno abre «Mis docentes · Ingresar código» y se vincula.
4. El docente puede retirar la relación sin eliminar la cuenta ni sus resultados.

No se asignan automáticamente alumnos anteriores. Un docente sin asignaciones ve una lista vacía. El código debe tratarse como invitación privada. Falta rotación/caducidad del código y asignación central por institución para operación a escala; no publicar códigos en espacios abiertos.

Filtrado aplicado: listado del escritorio docente, API de estudiantes, progreso de quizzes, informes de misiones del escritorio y API, y selección de alumnos del análisis del tutor. Esto no completa la auditoría de permisos de todos los endpoints de tareas, materiales y Live; esa revisión sigue pendiente antes de usar datos reales.

Verificación: tests/teacher_assignments_test.php contra MySQL, con transacción y rollback. Cubre vinculación, duplicados, códigos inválidos, rol, separación entre docentes y varios docentes por alumno. PHP validado. Pendiente recorrido completo en navegador y carga con 660 cuentas.
