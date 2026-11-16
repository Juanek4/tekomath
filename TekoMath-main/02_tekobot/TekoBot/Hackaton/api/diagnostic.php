<?php
require_once __DIR__.'/../config/api_guard.php';
tutorApiGuard(['GET', 'POST']);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../config/student_identity.php';
require_once __DIR__.'/../services/progress_service.php';
require_once __DIR__.'/../services/assessment_bank.php';
function diagnosticError(int $status, string $message): void {
 http_response_code($status); echo json_encode(['success'=>false,'error'=>$message],JSON_UNESCAPED_UNICODE); exit;
}
if ($_SERVER['REQUEST_METHOD']==='GET') {
 $unit=$_GET['unit']??'trigonometria';$subtopic=$_GET['subtopic']??'angulos';
 if(!is_string($unit)||!is_string($subtopic))diagnosticError(422,'Tema inválido.');
 $questions=AssessmentBank::questions($unit,$subtopic);
 if(!$questions)diagnosticError(422,'Elegí un tema válido.');
 $token=bin2hex(random_bytes(24));
 $_SESSION['tutor_diagnostic']=['token'=>$token,'started'=>time(),'student_id'=>$authenticatedStudentId,'unit'=>$unit,'subtopic'=>$subtopic,'question_ids'=>array_column($questions,'id')];
 $public=array_map(fn($q)=>array_intersect_key($q,array_flip(['id','question','options','unit','subtopic'])),$questions);
 echo json_encode(['success'=>true,'questions'=>$public,'attempt_token'=>$token,'catalog'=>AssessmentBank::catalog()],JSON_UNESCAPED_UNICODE);exit;
}
if ($_SERVER['REQUEST_METHOD']!=='POST') {header('Allow: GET, POST');diagnosticError(405,'Método no permitido.');}
$data=tutorApiJson();
if(!is_array($data)) diagnosticError(400,'Solicitud inválida.');
$attempt=$_SESSION['tutor_diagnostic']??null;
if(!$attempt || !is_string($data['attempt_token']??null) || !hash_equals($attempt['token'],$data['attempt_token']) || $attempt['student_id']!==$authenticatedStudentId) diagnosticError(403,'Abrí nuevamente la evaluación para enviarla.');
if(time()-$attempt['started']>7200) diagnosticError(409,'La evaluación venció. Abrila nuevamente.');
$questions=AssessmentBank::questions($attempt['unit']??'', $attempt['subtopic']??'');
if(!$questions || array_column($questions,'id')!==($attempt['question_ids']??[]))diagnosticError(409,'El banco cambió. Abrí nuevamente la evaluación.');
$answers=$data['details']??null;
if(!is_array($answers)||count($answers)!==count($questions)) diagnosticError(422,'Respondé todas las preguntas.');
$correct=0;$validated=[];
foreach($questions as $q){
 $answer=$answers[$q['id']]??null;
 if(!is_int($answer)||$answer<0||$answer>=count($q['options'])) diagnosticError(422,'Hay una respuesta inválida.');
 $validated[$q['id']]=$answer;if($answer===$q['correct_index'])$correct++;
}
$type=$data['test_type']??'pre_test';
if(!in_array($type,['pre_test','post_test'],true))diagnosticError(422,'Tipo de evaluación inválido.');
try{
 $result=ProgressService::saveDiagnostic($authenticatedStudentId,$type,$correct,count($questions),max(0,time()-$attempt['started']),['bank_version'=>2,'unit'=>$attempt['unit'],'subtopic'=>$attempt['subtopic'],'answers'=>$validated]);
 unset($_SESSION['tutor_diagnostic']);echo json_encode($result,JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){error_log('Diagnostic save failed: '.$e->getMessage());diagnosticError(500,'No pudimos guardar la evaluación. Intentá nuevamente.');}
