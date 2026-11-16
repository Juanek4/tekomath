<?php
require_once __DIR__.'/../config/api_guard.php';
tutorApiGuard(['GET']);
// api/exercises.php - Retorna el banco de ejercicios de trigonometría
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/student_identity.php';

try {
    $pdo = Database::getConnection();
    $subtopic = $_GET['subtopic'] ?? null;
    $difficulty = isset($_GET['difficulty']) ? (int)$_GET['difficulty'] : null;

    if($subtopic!==null&&(!is_string($subtopic)||strlen($subtopic)>100))tutorApiError(422,'Tema inválido.');
    if(isset($_GET['difficulty'])&&(!is_string($_GET['difficulty'])||!preg_match('/^[1-5]$/D',$_GET['difficulty'])))tutorApiError(422,'Dificultad inválida.');
    $sql = "SELECT id, topic, subtopic, unit_number, difficulty_level, statement_jopara, statement_es, formula_latex, visual_type, initial_angle, curriculum_ref FROM exercises WHERE 1=1";
    $params = [];

    if ($subtopic) {
        $sql .= " AND subtopic = ?";
        $params[] = $subtopic;
    }
    if ($difficulty) {
        $sql .= " AND difficulty_level = ?";
        $params[] = $difficulty;
    }

    $sql .= " ORDER BY difficulty_level ASC, id ASC LIMIT 200";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $exercises = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'exercises' => $exercises
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) { tutorApiFailure($e); }
