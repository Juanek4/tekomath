<?php
// Read-only hosting preflight. Never load application connectors: local connectors can create tables.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit(1); }
$checks = [];
$pending = [];
$record = static function (string $name, bool $ok, string $detail) use (&$checks, &$pending): void {
    $checks[] = ['check' => $name, 'ok' => $ok, 'detail' => $detail];
    if (!$ok) $pending[] = $name . ': ' . $detail;
};
$record('php', PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION === 4,
    PHP_VERSION . '; objetivo PHP 8.4 con el último parche disponible.');
foreach (['pdo_mysql', 'mbstring', 'curl', 'openssl', 'fileinfo', 'session'] as $extension) {
    $record('extension:' . $extension, extension_loaded($extension), $extension);
}
try {
    require_once __DIR__ . '/../config/runtime.php';
    foreach (['main', 'tutor'] as $module) {
        try {
            $settings = teko_database($module);
            $db = new PDO(
                'mysql:host=' . $settings['host'] . ';port=' . $settings['port'] . ';dbname=' . $settings['name'] . ';charset=utf8mb4',
                $settings['user'], $settings['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
            );
            $version = (string)$db->query('SELECT VERSION()')->fetchColumn();
            // Some servers retain the MySQL compatibility prefix in their version string.
            $normalized = preg_replace('/^5\.5\.5-/', '', $version);
            $ok = stripos($version, 'MariaDB') !== false && preg_match('/^11\.4\./', $normalized) === 1;
            $record($module . ':mariadb', $ok, $version . '; objetivo MariaDB 11.4 con el último parche disponible.');
            $record($module . ':query', (int)$db->query('SELECT 1')->fetchColumn() === 1, 'Consulta de solo lectura.');
        } catch (Throwable $e) {
            // Connection exceptions can reveal hostnames and account names; keep output generic.
            $record($module . ':connection', false, 'No se pudo comprobar la base. Revisar configuración privada y conectividad SQL.');
        }
    }
} catch (Throwable $e) {
    $record('configuration', false, 'No se pudo cargar una configuración válida. Revisar TEKO_CONFIG_FILE y TEKO_* .');
}
echo json_encode([
    'hosting_ready' => !$pending,
    'scope' => 'Versiones objetivo, extensiones y conectividad SQL. No certifica seguridad, parches actuales ni funcionamiento web.',
    'checks' => $checks,
    'pending' => $pending,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) . PHP_EOL;
exit($pending ? 2 : 0);
