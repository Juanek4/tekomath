<?php
require_once __DIR__.'/config/session.php';require_once __DIR__.'/config/database.php';require_once __DIR__.'/config/api_security.php';
requireLogin();header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
$user=getUser();$db=getDB();
teko_schema_exec($db,'CREATE TABLE IF NOT EXISTS arcade_account_progress (user_id INT PRIMARY KEY, snapshot_json LONGTEXT NOT NULL, revision INT NOT NULL DEFAULT 1, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES usuarios(id) ON DELETE CASCADE) ENGINE=InnoDB');
if(empty($_SESSION['api_csrf']))$_SESSION['api_csrf']=bin2hex(random_bytes(32));
if($_SERVER['REQUEST_METHOD']==='GET'){
 $q=$db->prepare('SELECT snapshot_json,revision FROM arcade_account_progress WHERE user_id=?');$q->execute([$user['id']]);$row=$q->fetch();echo json_encode(['success'=>true,'user_id'=>(int)$user['id'],'token'=>$_SESSION['api_csrf'],'revision'=>$row?(int)$row['revision']:0,'snapshot'=>$row?json_decode($row['snapshot_json'],true):null]);exit;
}
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit;}
apiMutationGuard();$raw=file_get_contents('php://input');if(strlen($raw)>300000){http_response_code(413);exit(json_encode(['success'=>false]));}
$data=json_decode($raw,true);
if(!is_array($data)||($data['user_id']??null)!==(int)$user['id']||!is_int($data['revision']??null)||!is_array($data['snapshot']??null)){
 http_response_code(409);exit(json_encode(['success'=>false,'error'=>'La cuenta cambió o el progreso no es válido. Recargá Arcade.']));
}
$snapshot=$data['snapshot'];foreach(['xp','correctAnswers','answersAttempted'] as $metric)if(isset($snapshot[$metric])&&(!is_numeric($snapshot[$metric])||$snapshot[$metric]<0)){http_response_code(422);exit(json_encode(['success'=>false,'error'=>'Progreso inválido.']));}
try{
 $db->beginTransaction();$lock=$db->prepare('SELECT id FROM usuarios WHERE id=? FOR UPDATE');$lock->execute([$user['id']]);
 $q=$db->prepare('SELECT revision FROM arcade_account_progress WHERE user_id=? FOR UPDATE');$q->execute([$user['id']]);$revision=(int)($q->fetchColumn()?:0);
 if($revision!==$data['revision']){$db->rollBack();http_response_code(409);exit(json_encode(['success'=>false,'error'=>'Hay progreso más reciente en otro dispositivo. Recargá antes de continuar.']));}
 $q=$db->prepare('INSERT INTO arcade_account_progress(user_id,snapshot_json,revision) VALUES(?,?,?) ON DUPLICATE KEY UPDATE snapshot_json=VALUES(snapshot_json),revision=VALUES(revision)');$q->execute([$user['id'],json_encode($snapshot),$revision+1]);$db->commit();echo json_encode(['success'=>true,'revision'=>$revision+1]);
}catch(Throwable $e){if($db->inTransaction())$db->rollBack();error_log($e->getMessage());http_response_code(500);echo json_encode(['success'=>false,'error'=>'No se pudo sincronizar.']);}
