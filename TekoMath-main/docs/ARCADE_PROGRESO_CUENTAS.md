# Progreso de Arcade por cuenta

Se reemplazó el uso compartido de teko-juegos-progress-v1 por una caché v2 ligada al ID autenticado. El servidor toma la identidad de la sesión; no acepta elegir a otra persona por parámetros. La clave antigua no se importa ni elimina porque su propietario no es demostrable.

api_arcade_progress.php guarda una instantánea por cuenta con revisión, protección CSRF y un límite de tamaño. Arcade consulta la sesión y carga el progreso antes de renderizar. Al guardar, conserva una copia local y sincroniza mediante una cola. Una revisión antigua o un cambio de cuenta rechaza la escritura con 409. Al volver la conexión se reintentan escrituras; la recuperación de pendientes al reabrir exige que la revisión base coincida. Los conflictos quedan indicados y no se fusionan automáticamente. Se añadió un indicador visual de sincronización con bordes y sombra propios del sitio.

Se retiraron errores conceptuales predeterminados del estado inicial. Reiniciar el progreso notifica al servidor. Las puntuaciones siguen originándose en el juego del navegador: esta sincronización no prueba que se hayan obtenido legítimamente y no debe convertirse en nota oficial sin validación de eventos en servidor.

Validación: tests/arcade_accounts_http_test.cjs PASS contra Apache/MySQL con dos alumnos: progreso inicial vacío, persistencia, separación, rechazo de ID falsificado, revisión antigua y falta de CSRF. Limpieza de cuentas y datos mediante eliminación en cascada. PHP y JavaScript publicados pasan validación sintáctica.

Límites: no se ejecutó una partida completa en navegador, cambio de cuenta entre dos pestañas, red interrumpida real ni carga. No hay interfaz para resolver conflictos; se conserva el local y se bloquea la sincronización. No se garantizan avances nuevos sin una primera conexión autenticada. No hay política de retención ni historial de instantáneas. Se actualizaron fuente y salida publicada directamente porque no hay node_modules instalados; sigue pendiente una compilación reproducible y comprobación TypeScript completa. Estos límites impiden dar por cerrada toda la preparación de Arcade para producción.
