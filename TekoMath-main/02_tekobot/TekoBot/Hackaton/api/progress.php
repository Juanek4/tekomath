<?php
require_once __DIR__.'/../config/api_guard.php';
tutorApiGuard(['GET']);
// api/progress.php - Endpoint para consultar el progreso y las métricas del estudiante
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../config/student_identity.php';

require_once __DIR__ . '/../services/progress_service.php';

$studentId = $authenticatedStudentId;

try {
    $data = ProgressService::getStudentOverview($studentId);
    echo json_encode([
        'success' => true,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) { tutorApiFailure($e); }
