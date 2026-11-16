<?php
require_once __DIR__.'/../config/api_guard.php';
tutorApiGuard(['POST']);
// api/chat.php - Endpoint con memoria persistente, gestión de sesiones y multi-tema MEC
require_once __DIR__ . '/../services/ai_service.php';
require_once __DIR__ . '/../services/pedagogy_service.php';
require_once __DIR__ . '/../services/progress_service.php';
require_once __DIR__ . '/../services/student_analysis.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/student_identity.php';

$data = tutorApiJson();
if(!is_string($data['message']??null)||mb_strlen($data['message'])>12000)tutorApiError(422,'Escribí un mensaje de hasta 12.000 caracteres.');
if(!is_string($data['session_uuid']??null)||!preg_match('/^[a-zA-Z0-9_-]{1,36}$/D',$data['session_uuid']))tutorApiError(422,'Conversación inválida.');
if(isset($data['action'])&&(!is_string($data['action'])||strlen($data['action'])>80))tutorApiError(422,'Acción inválida.');
if(isset($data['revision'])&&(!is_int($data['revision'])||$data['revision']<1))tutorApiError(422,'Revisión inválida.');

if(isset($data['language'])&&!is_string($data['language']))tutorApiError(422,'Idioma inválido.');
$userMessage = trim($data['message'] ?? '');
$studentId = $authenticatedStudentId;
$sessionUuid = $data['session_uuid'] ?? 'default-session';
$language = TutorLanguage::normalize($data['language'] ?? 'jopara');
$context = []; // Context and pedagogical state are reconstructed on the server.
$startMs = microtime(true);

if($userMessage==='')tutorApiError(422,'El mensaje no puede estar vacío.');

try {
    // Sessions are created only after choosing a unit/subtopic.
    $lookup = Database::getConnection()->prepare('SELECT id FROM learning_sessions WHERE session_uuid = ? AND student_id = ?');
    $lookup->execute([$sessionUuid, $studentId]);
    $sessionId = (int)$lookup->fetchColumn();
    if (!$sessionId) tutorApiError(404,'Conversación no disponible.');

    $session = Database::getSession($sessionId, $studentId);
    $tutorState = Database::getTutorState($sessionId, $studentId);
    $context['current_topic'] = $session['current_topic'];
    $context['tutor_state'] = $tutorState;
    $context['language'] = $language;
    $chatHistory = Database::getLastNMessages($sessionId, 12);

    if (CurriculumService::lesson($tutorState['topic'], $tutorState['subtopic'])) {
        if ($userMessage === '__init__') {
            echo json_encode(['success'=>true, 'session_id'=>$sessionId, 'tutor_state'=>$tutorState,
                'history'=>Database::getFullHistory($sessionId),
                'response'=>$tutorState['last_response'] ?? PedagogyService::response($tutorState)], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (isset($data['revision']) && (int)$data['revision'] !== $tutorState['revision']) {
            http_response_code(409);
            echo json_encode(['success'=>false, 'error'=>'La conversación cambió. Recuperamos el último punto.', 'tutor_state'=>$tutorState], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $tutorState['language'] = $language;
        $next = PedagogyService::transition($tutorState, $userMessage, $data['action'] ?? '');
        $next = StudentAnalysis::adapt(StudentAnalysis::update($next, $userMessage, $data['action'] ?? ''));
        $next = AIService::interpretWorkshop($next);
        unset($next['last_response']);
        $response = AIService::renderLesson($userMessage, $next, $chatHistory, PedagogyService::response($next));
        // No self-referencing state in the persisted response.
        unset($response['pedagogical_state']);
        $next['last_response'] = $response;
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $next = Database::saveTutorState($sessionId, $studentId, $next, $tutorState['revision']);
            $pdo->prepare('UPDATE learning_sessions SET language_used = ? WHERE id = ? AND student_id = ?')->execute([$language,$sessionId,$studentId]);
            Database::saveMessage($sessionId, $studentId, 'user', $userMessage, null, null, null, 0, false, $next['topic'], $next['subtopic']);
            Database::saveMessage($sessionId, $studentId, 'assistant', $response['tutor_message_jopara'], $response['formula_display']['latex'], json_encode($response['visual_action']), null, 0, false, $next['topic'], $next['subtopic']);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
        // Teaching, examples, questions and self-reports are not scored attempts.
        echo json_encode(['success'=>true, 'session_id'=>$sessionId, 'response'=>$response, 'tutor_state'=>$next], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Si es un mensaje especial de inicialización
    if ($userMessage === '__init__') {
        $history = Database::getFullHistory($sessionId, 50);
        echo json_encode([
            'success' => true,
            'session_id' => $sessionId,
            'tutor_state' => $tutorState,
            'history' => $history
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Guardar el mensaje del estudiante en la memoria (chat_history)
    Database::saveMessage(
        $sessionId,
        $studentId,
        'user',
        $userMessage,
        null, null, null, 0, false,
        $context['current_topic'] ?? 'trigonometria',
        ''
    );

    // 3. Cargar el historial reciente real de la BD para la memoria contextual de la IA
    $context['history'] = $chatHistory;


    // 4. Procesar interacción con el motor de IA / Fallback Jopara
    $aiResponse = AIService::processInteraction($userMessage, $context);

    // 5. Guardar la respuesta del tutor en la memoria (chat_history)
    $pedagogical = $aiResponse['pedagogical_state'] ?? [];
    $subtopic = $pedagogical['subtopic'] ?? 'sen_cos';
    $errorType = $pedagogical['detected_error_type'] ?? null;
    $hintLevel = (int)($pedagogical['hint_level'] ?? 0);
    $isCorrect = (bool)(($pedagogical['student_state'] ?? '') === 'correct' || ($pedagogical['is_step_complete'] ?? false));
    $exerciseId = $context['current_exercise']['id'] ?? null;
    $responseTimeMs = (int)((microtime(true) - $startMs) * 1000);

    Database::saveMessage(
        $sessionId,
        $studentId,
        'assistant',
        $aiResponse['tutor_message_jopara'] ?? '',
        $aiResponse['formula_display']['latex'] ?? null,
        json_encode($aiResponse['visual_action'] ?? null),
        $errorType,
        $hintLevel,
        $isCorrect,
        $context['current_topic'] ?? 'trigonometria',
        $subtopic,
        $responseTimeMs
    );

    // 6. Registrar en el log de progreso y métricas
    ProgressService::logInteraction(
        $studentId,
        $sessionId,
        $exerciseId,
        $subtopic,
        $userMessage,
        $aiResponse['tutor_message_jopara'] ?? '',
        $errorType,
        $hintLevel,
        $isCorrect,
        $responseTimeMs
    );

    // 7. Obtener resumen de progreso actualizado
    $progressOverview = ProgressService::getStudentOverview($studentId);

    echo json_encode([
        'success' => true,
        'session_id' => $sessionId,
        'response' => $aiResponse,
        'tutor_state' => $tutorState,
        'updated_progress' => $progressOverview
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) { tutorApiFailure($e); }
