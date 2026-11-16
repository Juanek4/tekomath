<?php
require_once __DIR__.'/../config/api_guard.php';
tutorApiGuard(['GET']);
// api/history.php - Endpoint para obtener el historial completo de una sesión
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/student_identity.php';

foreach(['session_id','session_uuid','limit'] as $key)if(isset($_GET[$key])&&!is_string($_GET[$key]))tutorApiError(422,'Parámetro inválido.');
if(isset($_GET['session_id'])&&(!ctype_digit($_GET['session_id'])||(int)$_GET['session_id']<1))tutorApiError(422,'Conversación inválida.');
if(isset($_GET['session_uuid'])&&!preg_match('/^[a-zA-Z0-9_-]{1,36}$/D',$_GET['session_uuid']))tutorApiError(422,'Conversación inválida.');
$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$sessionUuid = $_GET['session_uuid'] ?? null;
$studentId = $authenticatedStudentId;
$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 50;

try {
    if (!$sessionId && $sessionUuid) {
        $stmt = Database::getConnection()->prepare('SELECT id FROM learning_sessions WHERE session_uuid = ? AND student_id = ?');
        $stmt->execute([$sessionUuid, $studentId]);
        $sessionId = (int)$stmt->fetchColumn();
        if(!$sessionId)tutorApiError(404,'Conversación no disponible.');
    }

    if (!$sessionId) {
        echo json_encode([
            'success' => true,
            'history' => [],
            'session_id' => null
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $session = Database::getSession($sessionId, $studentId);
    $state = Database::getTutorState($sessionId, $studentId);
    $history = Database::getFullHistory($sessionId, $limit);

    echo json_encode([
        'success' => true,
        'session_id' => $sessionId,
        'tutor_state' => $state,
        'topic' => $session['current_topic'],
        'language' => $session['language_used'],
        'history' => $history
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) { tutorApiFailure($e); }
