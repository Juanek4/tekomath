<?php
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/database.php';
requireLogin();$user=getUser();$db=getDB();
if(empty($_SESSION['assignment_token']))$_SESSION['assignment_token']=bin2hex(random_bytes(32));
$message='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $token=$_POST['token']??null;
 if(!is_string($token)||!hash_equals($_SESSION['assignment_token'],$token)){http_response_code(403);exit('Solicitud vencida. Volvé a cargar la página.');}
 if($user['rol']==='estudiante'){
  $code=is_string($_POST['code']??null)?trim($_POST['code']):'';
  $message=TeacherAssignments::join($db,(int)$user['id'],$code)?'Te vinculaste con tu docente.':'No se pudo crear la vinculación. Revisá el código o comprobá si ya estás asignado.';
 }elseif($user['rol']==='docente'&&($_POST['action']??'')==='save_class'){
  try{
   TeacherAssignments::saveClass($db,(int)$user['id'],$_POST);
   $message='Los datos de tu clase están guardados. Ya podés compartir el código.';
  }catch(InvalidArgumentException $error){$message=$error->getMessage();}
 }elseif($user['rol']==='docente'&&($_POST['action']??'')==='remove'){
  $q=$db->prepare('DELETE FROM docente_alumnos WHERE docente_id=? AND estudiante_id=?');$q->execute([$user['id'],(int)($_POST['student']??0)]);$message='Vinculación retirada de tu listado.';
 }
}
$esc=fn($x)=>htmlspecialchars((string)$x,ENT_QUOTES,'UTF-8');
?>
<!doctype html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Mis vinculaciones · TEKO Math</title><link rel="stylesheet" href="../07_diseno_y_graficos/style.css"><link rel="stylesheet" href="../07_diseno_y_graficos/teko-ui.css?v=20261028-management"></head><body class="teko-management-page"><main class="teko-management-card assignments-panel">
<a href="<?= $user['rol']==='docente'?'docente.php':'dashboard.php' ?>">← Volver al escritorio</a><p class="management-eyebrow">TEKO Math · Gestión educativa</p><h1><?= $user['rol']==='docente'?'Mis estudiantes':'Mis docentes' ?></h1>
<?php if($message): ?><p class="management-notice" role="status"><?= $esc($message) ?></p><?php endif ?>
<?php if($user['rol']==='docente'): $class=TeacherAssignments::classInfo($db,(int)$user['id']); $configured=$class&&!empty($class['colegio'])&&!empty($class['curso'])&&!empty($class['bachillerato'])&&!empty($class['seccion']); $form=$class??[]; if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='save_class')$form=$_POST; ?>
<h2>Datos de mi clase</h2>
<p>Completá el colegio, el curso y la sección para generar el código que vas a compartir con tus estudiantes.</p>
<form method="post" class="teacher-class-form">
<input type="hidden" name="token" value="<?= $esc($_SESSION['assignment_token']) ?>"><input type="hidden" name="action" value="save_class">
<label for="colegio">Colegio</label><input id="colegio" name="colegio" required maxlength="160" value="<?= $esc($form['colegio']??'') ?>" placeholder="Nombre completo del colegio">
<label for="curso">Curso</label><select id="curso" name="curso" required><option value="">Elegí el curso</option><?php foreach([1=>'1.er curso',2=>'2.º curso',3=>'3.er curso'] as $value=>$label): ?><option value="<?= $value ?>" <?= (string)($form['curso']??'')===(string)$value?'selected':'' ?>><?= $label ?></option><?php endforeach ?></select>
<label for="bachillerato">Bachillerato y énfasis o especialidad</label><input id="bachillerato" name="bachillerato" required maxlength="160" value="<?= $esc($form['bachillerato']??'') ?>" placeholder="Ej.: Científico · Ciencias Básicas y Tecnología"><small>Indicá el bachillerato científico, técnico u otra modalidad y su especialidad.</small>
<label for="seccion">Sección</label><input id="seccion" name="seccion" required maxlength="40" value="<?= $esc($form['seccion']??'') ?>" placeholder="Ej.: A · Turno mañana">
<button class="btn btn--primary" type="submit"><?= $configured?'Guardar datos de la clase':'Guardar y generar código' ?></button>
</form>
<?php if($configured): ?>
<p><strong><?= $esc($class['colegio']) ?></strong> · <?= (int)$class['curso'] ?>.º curso · <?= $esc($class['bachillerato']) ?> · Sección <?= $esc($class['seccion']) ?></p>
<p>Compartí este código únicamente con tus estudiantes. Cada alumno lo ingresa desde «Mis docentes» para vincular su cuenta.</p><p><strong class="teacher-invite-code"><?= $esc($class['codigo']) ?></strong></p>
<?php endif ?>
<?php $q=$db->prepare('SELECT u.id,u.nombre FROM docente_alumnos da JOIN usuarios u ON u.id=da.estudiante_id WHERE da.docente_id=? ORDER BY u.nombre');$q->execute([$user['id']]);$rows=$q->fetchAll(); ?>
<p><?= count($rows) ?> estudiantes asignados. Un alumno puede trabajar con más de un docente.</p>
<?php foreach($rows as $row): ?><form class="assignment-student-row" method="post"><span><?= $esc($row['nombre']) ?></span><input type="hidden" name="token" value="<?= $esc($_SESSION['assignment_token']) ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="student" value="<?= (int)$row['id'] ?>"><button class="btn btn--secondary management-remove" type="submit">Retirar de mi listado</button></form><?php endforeach ?>
<?php else: ?>
<form method="post"><input type="hidden" name="token" value="<?= $esc($_SESSION['assignment_token']) ?>"><label for="code">Código de tu docente</label><input id="code" name="code" required maxlength="7" pattern="[A-Za-z][0-9]{6}" placeholder="Ej.: T482913" autocapitalize="characters" spellcheck="false" autocomplete="off"><button class="btn btn--primary" type="submit">Vincularme con mi docente</button></form>
<?php $q=$db->prepare('SELECT u.nombre FROM docente_alumnos da JOIN usuarios u ON u.id=da.docente_id WHERE da.estudiante_id=? ORDER BY u.nombre');$q->execute([$user['id']]);foreach($q as $row): ?><p><?= $esc($row['nombre']) ?></p><?php endforeach ?>
<?php endif ?></main></body></html>
