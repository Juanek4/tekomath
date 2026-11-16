<?php
require_once __DIR__.'/../config/api_guard.php';
tutorApiGuard(['GET', 'POST']);
// api/sessions.php - Gestor de Múltiples Conversaciones y Sesiones de Chat
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/student_identity.php';
require_once __DIR__ . '/../services/pedagogy_service.php';

$studentId = $authenticatedStudentId;
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = Database::getConnection();

    // GET: Listar todas las conversaciones del estudiante
    if ($method === 'GET') {
        $stmt = $pdo->prepare("
            SELECT s.id, s.session_uuid, s.current_topic, s.language_used, s.total_interactions, s.started_at,
                   (SELECT message FROM chat_history WHERE session_id = s.id AND role = 'user' ORDER BY id ASC LIMIT 1) as first_message
            FROM learning_sessions s
            WHERE s.student_id = ?
            ORDER BY s.started_at DESC
        ");
        $stmt->execute([$studentId]);
        $sessions = $stmt->fetchAll();

        $formatted = array_map(function($s) use ($studentId) {
            $topicNames = [
                'trigonometria' => 'Trigonometría',
                'numeros_reales' => 'Números Reales',
                'conjuntos' => 'Conjuntos',
                'funciones' => 'Funciones',
                'funciones_lineales' => 'Func. Lineales',
                'ecuaciones' => 'Ecuaciones',
                'geometria' => 'Geometría',
                'estadistica' => 'Estadística'
            ];

            $savedState=Database::getTutorState((int)$s['id'],$studentId);
            $isWorkshop=isset($savedState['workshop']);
            $topicLabel = $isWorkshop?'Resolver un problema':($topicNames[$s['current_topic']] ?? 'Matemática');
            $preview = !empty($s['first_message']) ? mb_substr($s['first_message'], 0, 35) . '...' : "Conversación de {$topicLabel}";

            return [
                'id' => (int)$s['id'],
                'session_uuid' => $s['session_uuid'],
                'topic' => $s['current_topic'],
                'topic_label' => $isWorkshop?$topicLabel:(CurriculumService::unit($s['current_topic'])['title'] ?? $topicLabel),
                'language' => $s['language_used'],
                'interactions' => (int)$s['total_interactions'],
                'preview' => $preview,
                'started_at' => $s['started_at'],
                'date_formatted' => date('d/m H:i', strtotime($s['started_at']))
            ];
        }, $sessions);

        echo json_encode([
            'success' => true,
            'sessions' => $formatted
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // POST: Crear una nueva conversación
    if ($method === 'POST') {
        $data = tutorApiJson();
        $action = $data['action'] ?? 'create';
        $studentId = $authenticatedStudentId;
        if(!in_array($action,['create','delete'],true))tutorApiError(422,'Acción inválida.');

        if ($action === 'create') {
            $uuid = $data['session_uuid'] ?? ('sess_' . bin2hex(random_bytes(8)) . '_' . time());
            $topic = $data['topic'] ?? 'trigonometria';
            $lang = $data['language'] ?? 'jopara';
            $subtopic = $data['subtopic'] ?? '';
            foreach([$uuid,$topic,$lang,$subtopic] as $value)if(!is_string($value))tutorApiError(422,'Datos de conversación inválidos.');
            if(!in_array($lang,['es','es_py','jopara'],true))tutorApiError(422,'Idioma inválido.');
            if (!CurriculumService::lesson($topic, $subtopic)) throw new InvalidArgumentException('Seleccioná una unidad y un subtema.');

            $newId = Database::getOrCreateSession($uuid, $studentId, $lang, $topic);
            $session = Database::getSession($newId, $studentId);
            $state = Database::getTutorState($newId, $studentId, $subtopic);
            if (!isset($state['last_response'])) {
                if(($data['purpose']??'')==='problem_workshop')$state['workshop']=['stage'=>'topic'];
                $welcome = PedagogyService::response($state);
                unset($welcome['pedagogical_state']);
                $state['last_response'] = $welcome;
                $pdo->beginTransaction();
                $state = Database::saveTutorState($newId, $studentId, $state, $state['revision']);
                Database::saveMessage($newId, $studentId, 'assistant', $welcome['tutor_message_jopara'], null, null, null, 0, false, $state['topic'], $state['subtopic']);
                $pdo->commit();
            }

            echo json_encode([
                'success' => true,
                'session' => [
                    'id' => $newId,
                    'session_uuid' => $uuid,
                    'topic' => $session['current_topic'],
                    'tutor_state' => $state,
                    'language' => $session['language_used'],
                    'preview' => 'Nueva Conversación'
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'delete') {
            $sessionId = $data['session_id']??null;
            if(!is_int($sessionId)||$sessionId<1)tutorApiError(422,'Conversación inválida.');
            Database::getSession($sessionId, $studentId);
            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM tutor_session_states WHERE session_id = ?')->execute([$sessionId]);
            $pdo->prepare('DELETE FROM chat_history WHERE session_id = ? AND student_id = ?')->execute([$sessionId,$studentId]);
            $pdo->prepare('DELETE FROM learning_sessions WHERE id = ? AND student_id = ?')->execute([$sessionId,$studentId]);
            $pdo->commit();
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    tutorApiFailure($e);
}
