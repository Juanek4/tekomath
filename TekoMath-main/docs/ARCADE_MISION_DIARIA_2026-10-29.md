# Misión diaria de Arcade

29/10/2026. Se retiraron el contador 1/3 y la barra 30% constantes en AppShell. dailyQuest.ts calcula la fecha en America/Asuncion y suma respuestas correctas registradas en partidas de seno del mismo día; omite otras funciones, días previos, fechas inválidas y sesiones repetidas. El progreso visible está limitado a tres aciertos.

La interfaz escucha cambios de progreso y refresca cada minuto para actualizar el día. El botón de recompensa usa las clases del sitio. Vuelve a comprobar el progreso al pulsar y utiliza daily-sin-YYYY-MM-DD como identificador para reclamar 30 XP una sola vez mediante claimMissionReward. Esta garantía corresponde al estado de progreso de la cuenta; aún no existe verificación central de eventos del juego contra manipulación.

Pruebas: 86 aprobadas, incluyendo cuenta vacía, días diferentes, función distinta, sesión duplicada, meta y fecha de Paraguay. Las pruebas anteriores de progressService cubren no duplicar recompensas. Pendiente recorrido visual del botón y prueba integrada de cobro/sincronización entre dispositivos. Las partidas necesitan terminar para incorporar sus aciertos al historial; un ejercicio abierto todavía no cuenta.
