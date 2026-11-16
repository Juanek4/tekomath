<?php
require_once __DIR__.'/mission_grading.php';
function publicMissionData(mixed $value): mixed {
 if(!is_array($value))return $value;
 foreach($value as $key=>$item){if(in_array($key,['es_correcta','feedback','feedback_diagnostico','respuesta_correcta','solucion'],true))unset($value[$key]);else $value[$key]=publicMissionData($item);}return $value;
}
function missionAttemptTable(PDO $db): void {
 teko_schema_exec($db,'CREATE TABLE IF NOT EXISTS mission_attempts (id CHAR(64) PRIMARY KEY, student_id INT NOT NULL, task_id INT NOT NULL, snapshot_json LONGTEXT NOT NULL, answers_json LONGTEXT NOT NULL, expires_at DATETIME NOT NULL, finished TINYINT NOT NULL DEFAULT 0, INDEX idx_owner(student_id,task_id)) ENGINE=InnoDB');
 teko_schema_exec($db,'CREATE TABLE IF NOT EXISTS mission_results (id BIGINT AUTO_INCREMENT PRIMARY KEY, attempt_id CHAR(64) NOT NULL UNIQUE, student_id INT NOT NULL, teacher_id INT NOT NULL, task_id INT NOT NULL, task_title VARCHAR(200) NOT NULL, correct_count INT NOT NULL, total_count INT NOT NULL, percentage INT NOT NULL, completed_at DATETIME NOT NULL, INDEX idx_teacher(teacher_id,id), INDEX idx_history(student_id,task_id,id)) ENGINE=InnoDB');
}
function missionItems(array $mission): array {
 $items=[];foreach($mission['etapas']??[] as $i=>$stage){if(!empty($stage['opciones']))$items[$i.':-1']=$stage['opciones'];foreach($stage['pasos']??[] as $j=>$step)if(!empty($step['opciones']))$items[$i.':'.$j]=$step['opciones'];}return $items;
}
function handleMissionAttempt(PDO $db,array $user,?string $action): void {
 if(!in_array($action,['iniciar_intento_mision','responder_mision'],true))return;
 if($user['rol']!=='estudiante'){http_response_code(403);exit(json_encode(['success'=>false]));}
 missionAttemptTable($db);$task=(int)($_POST['tarea_id']??0);requireTeachingResource($db,$user,'tareas',$task);
 try{
  if($action==='iniciar_intento_mision'){
   // Serialize starts for this student so simultaneous tabs resume one attempt.
   $db->beginTransaction();
   $owner=$db->prepare('SELECT id FROM usuarios WHERE id=? FOR UPDATE');$owner->execute([$user['id']]);
   $existing=$db->prepare('SELECT * FROM mission_attempts WHERE student_id=? AND task_id=? AND finished=0 AND expires_at>? ORDER BY expires_at DESC LIMIT 1 FOR UPDATE');
   $existing->execute([$user['id'],$task,gmdate('Y-m-d H:i:s')]);$active=$existing->fetch();
   if($active){$db->commit();exit(json_encode(missionResumeResponse($active,true),JSON_UNESCAPED_UNICODE));}
   $q=$db->prepare('SELECT mision_data FROM tareas WHERE id=?');$q->execute([$task]);$snapshot=json_decode($q->fetchColumn()?:'{}',true);
   if(!missionItems($snapshot))throw new InvalidArgumentException('Esta misión no tiene ejercicios corregibles.');
   $id=bin2hex(random_bytes(32));$db->prepare('INSERT INTO mission_attempts(id,student_id,task_id,snapshot_json,answers_json,expires_at) VALUES(?,?,?,?,?,?)')->execute([$id,$user['id'],$task,json_encode($snapshot),'[]',gmdate('Y-m-d H:i:s',time()+7200)]);
   $db->commit();
   exit(json_encode(missionResumeResponse(['id'=>$id,'snapshot_json'=>json_encode($snapshot),'answers_json'=>'[]','expires_at'=>gmdate('Y-m-d H:i:s',time()+7200)],false),JSON_UNESCAPED_UNICODE));
  }
  $db->beginTransaction();$attempt=loadMissionAttempt($db,$user,$task);
  $answers=json_decode($attempt['answers_json'],true);$items=missionItems(json_decode($attempt['snapshot_json'],true));
  $key=(string)($_POST['stage']??'').':'.(string)($_POST['step']??-1);$choice=(string)($_POST['option']??'');$request=(string)($_POST['request_id']??'');
  if(!preg_match('/^[a-f0-9-]{16,64}$/D',$request))throw new InvalidArgumentException('Identificador de respuesta inválido.');
  foreach($answers as $a)if(($a['request_id']??'')===$request){if($a['stage'].':'.$a['step']!==$key||$a['option']!==$choice)throw new InvalidArgumentException('Respuesta duplicada incompatible.');$db->commit();exit(json_encode(['success'=>true,'correct'=>$a['correct'],'feedback'=>$a['feedback']]));}
  $solved=[];foreach($answers as $a)if($a['correct'])$solved[$a['stage'].':'.$a['step']]=true;
  $pending=array_values(array_diff(array_keys($items),array_keys($solved)));
  if(($pending[0]??null)!==$key||count($answers)>=1000)throw new InvalidArgumentException('La etapa no está activa.');
  $selected=null;foreach($items[$key] as $option)if((string)$option['id']===$choice)$selected=$option;
  if(!$selected)throw new InvalidArgumentException('Opción inválida.');
  $correct=!empty($selected['es_correcta']);$feedback=(string)($correct?($selected['feedback']??'Correcto.'):($selected['feedback_diagnostico']??'Revisá tu respuesta.'));
  $answers[]=['stage'=>(int)$_POST['stage'],'step'=>(int)($_POST['step']??-1),'option'=>$choice,'request_id'=>$request,'correct'=>$correct,'feedback'=>$feedback];
  $db->prepare('UPDATE mission_attempts SET answers_json=? WHERE id=?')->execute([json_encode($answers),$attempt['id']]);$db->commit();
  exit(json_encode(['success'=>true,'correct'=>$correct,'feedback'=>$feedback],JSON_UNESCAPED_UNICODE));
 }catch(InvalidArgumentException $e){if($db->inTransaction())$db->rollBack();http_response_code(409);exit(json_encode(['success'=>false,'error'=>$e->getMessage()]));}
}
function missionResumeResponse(array $attempt,bool $resumed): array {
 $mission=json_decode($attempt['snapshot_json'],true);$answers=json_decode($attempt['answers_json'],true);$solved=[];
 foreach($answers as $answer)if($answer['correct'])$solved[$answer['stage'].':'.$answer['step']]=true;
 $pending=array_values(array_diff(array_keys(missionItems($mission)),array_keys($solved)));
 return ['success'=>true,'attempt_id'=>$attempt['id'],'mission'=>publicMissionData($mission),'resumed'=>$resumed,'answers'=>$answers,'next_item'=>$pending[0]??null,'expires_at'=>$attempt['expires_at'].'Z'];
}
function loadMissionAttempt(PDO $db,array $user,int $task): array {
 $q=$db->prepare('SELECT * FROM mission_attempts WHERE id=? AND student_id=? AND task_id=? FOR UPDATE');$q->execute([(string)($_POST['attempt_id']??''),$user['id'],$task]);$a=$q->fetch();
 if(!$a||$a['finished']||$a['expires_at']<=gmdate('Y-m-d H:i:s'))throw new InvalidArgumentException('Intento inválido, vencido o ya guardado.');return $a;
}
