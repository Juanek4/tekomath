<?php
/* ============================================
   TEKO MATH — API de Estudiantes (api_estudiantes.php)
   Listado de alumnos registrados para docentes
   ============================================ */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

requireDocente();

$db = getDB();
$assignedScope = TeacherAssignments::scope((int)getUser()['id']);

try {
    $stmt = $db->query("
        SELECT u.id, u.nombre, u.email, u.created_at
        FROM usuarios u
        WHERE rol = 'estudiante' AND {$assignedScope}
        ORDER BY nombre ASC
    ");
    $estudiantes = $stmt->fetchAll();
    echo json_encode(['success' => true, 'estudiantes' => $estudiantes]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al obtener lista de estudiantes']);
}
