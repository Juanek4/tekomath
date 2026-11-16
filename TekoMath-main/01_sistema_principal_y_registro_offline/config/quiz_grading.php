<?php
function quizAttempt(array $user): void {
 $action=$_POST['action']??'';
 if(!in_array($action,['start','answer'],true))return;
 if($user['rol']!=='estudiante'){http_response_code(403);exit(json_encode(['success'=>false]));}
 if($action==='start'){
  foreach($_SESSION['graded_quizzes']??[] as $key=>$old)if($old['expires']<time()||isset($old['saved']))unset($_SESSION['graded_quizzes'][$key]);
  if(count($_SESSION['graded_quizzes']??[])>=10){http_response_code(429);exit(json_encode(['success'=>false,'error'=>'Completá una de las pruebas abiertas antes de iniciar otra.']));}
  $bank=require __DIR__.'/quiz_bank.php';
  $topic=(string)($_POST['tema']??'Trigonometría');$diff=(string)($_POST['dificultad']??'facil');
  if(!isset($bank[$topic][$diff])){http_response_code(422);exit(json_encode(['success'=>false,'error'=>'Tema o dificultad no disponible.']));}
  $id=bin2hex(random_bytes(16));$questions=$bank[$topic][$diff];shuffle($questions);
  $_SESSION['graded_quizzes'][$id]=['id'=>$id,'user'=>(int)$user['id'],'topic'=>$topic,'diff'=>$diff,'questions'=>$questions,'answers'=>[],'expires'=>time()+7200];
  $public=array_map(fn($q)=>['q'=>$q['q'],'opts'=>$q['opts']],$questions);
  exit(json_encode(['success'=>true,'quiz_id'=>$id,'questions'=>$public],JSON_UNESCAPED_UNICODE));
 }
 $id=(string)($_POST['quiz_id']??'');
 if(!isset($_SESSION['graded_quizzes'][$id])){http_response_code(409);exit(json_encode(['success'=>false,'error'=>'Intento no disponible.']));}
 $a=&$_SESSION['graded_quizzes'][$id];$i=filter_var($_POST['index']??null,FILTER_VALIDATE_INT);$option=filter_var($_POST['option']??null,FILTER_VALIDATE_INT);
 if(!$a||$a['id']!==($_POST['quiz_id']??'')||$a['user']!==(int)$user['id']||$a['expires']<time()||$i===false||$i!==count($a['answers'])||$option===false||!isset($a['questions'][$i]['opts'][$option])){http_response_code(409);exit(json_encode(['success'=>false,'error'=>'Intento inválido o pregunta ya respondida.']));}
 $q=$a['questions'][$i];$correct=$option===$q['correct'];$a['answers'][]=$correct;
 exit(json_encode(['success'=>true,'correct'=>$correct,'explanation'=>$q['exp']],JSON_UNESCAPED_UNICODE));
}
function quizResult(array $user): array {
 $a=$_SESSION['graded_quizzes'][(string)($_POST['quiz_id']??'')]??null;
 if(!$a||$a['id']!==($_POST['quiz_id']??'')||$a['user']!==(int)$user['id']||$a['expires']<time()||count($a['answers'])!==count($a['questions'])||isset($a['saved'])){http_response_code(409);exit(json_encode(['success'=>false,'error'=>'Completá una prueba válida antes de guardar.']));}
 $correct=count(array_filter($a['answers']));$total=count($a['questions']);return [$a['topic'],$a['diff'],$correct,$total,(int)round(100*$correct/$total)];
}
