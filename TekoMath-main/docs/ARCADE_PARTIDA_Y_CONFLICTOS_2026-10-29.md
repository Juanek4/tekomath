# Verificación funcional de Arcade del 29 de octubre de 2026

Cambio: interfaz para conflictos de sincronización en public/account-progress.js, regenerada a dist mediante npm run build. Ante una revisión incompatible, se permite descargar la instantánea local o cargar la del servidor. La carga remota verifica la misma identidad y guarda primero una copia local con fecha en la clave de esa cuenta. No fusiona ni sobrescribe la versión remota. Los botones usan bordes, sombras y colores del sitio.

Prueba real: tests/arcade_game_browser_test.cjs. Cuenta temporal registrada por HTTP, Edge autenticado, preparación de Ratio Rush en Secante, inicio, respuesta correcta a secante de coseno 4/5 y finalización por tiempo usando reloj de prueba adelantado 61 segundos. Se comprobó un registro de partida y respuesta correcta en la instantánea del servidor, sin duplicación ni nueva revisión al recargar.

Conflicto: se simuló otra escritura autorizada como segundo dispositivo; la pestaña antigua recibió conflicto. Se pulsó Cargar versión del servidor y se comprobó la recuperación del valor remoto y la copia de seguridad del local. PASS. La cuenta temporal y su progreso SQL se eliminaron; el contexto de navegador era temporal.

La primera comprobación del botón de recuperación leyó el almacenamiento antes de completar la recarga. Se corrigió la sincronización de la prueba esperando la navegación real; pasó al repetir. No se atribuye ese fallo de prueba a pérdida de progreso en la aplicación.

Límites: solo una partida de Ratio Rush, no los seis juegos. No se verificó visualmente el panel móvil ni la descarga efectiva del archivo de copia local. Copias locales sin gestión de retención o restauración manual integrada. Pendiente verificar misiones diarias y contadores del panel: en una cuenta nueva se muestra 1/3 aunque no hubo actividad, por lo que ese indicador necesita revisión. Puntuaciones aún no verificadas por eventos matemáticos del servidor. No es una prueba de carga ni de conexión física interrumpida.
