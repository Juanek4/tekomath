<?php
require_once __DIR__.'/config/session.php';require_once __DIR__.'/config/database.php';require_once __DIR__.'/config/resource_access.php';require_once __DIR__.'/config/mission_attempts.php';
requireLogin();$user=getUser();$db=getDB();missionAttemptTable($db);
$page=max(1,(int)($_GET['page']??1));$limit=30;$offset=($page-1)*$limit;
if($user['rol']==='docente'){$scope='r.teacher_id='.(int)$user['id'].' AND '.TeacherAssignments::scope((int)$user['id'],'u.id');}
else{$scope='r.student_id='.(int)$user['id'];}
$count=(int)$db->query('SELECT COUNT(*) FROM mission_results r JOIN usuarios u ON u.id=r.student_id WHERE '.$scope)->fetchColumn();
$rows=$db->query('SELECT r.*,u.nombre FROM mission_results r JOIN usuarios u ON u.id=r.student_id WHERE '.$scope.' ORDER BY r.id DESC LIMIT '.$limit.' OFFSET '.$offset)->fetchAll();
$summaries=[];$groups=[];
foreach($rows as $row){$key=$row['student_id'].':'.$row['task_id'].':'.$row['teacher_id'];$groups[$key]=[(int)$row['student_id'],(int)$row['task_id'],(int)$row['teacher_id']];}
if($groups){
 $placeholders=implode(',',array_fill(0,count($groups),'(?,?,?)'));
 $summaryQuery=$db->prepare('SELECT g.*,first_result.percentage AS first_percentage,last_result.percentage AS last_percentage FROM
  (SELECT student_id,task_id,teacher_id,MIN(id) AS first_id,MAX(id) AS last_id,MAX(percentage) AS best_percentage,COUNT(*) AS attempt_count
   FROM mission_results WHERE (student_id,task_id,teacher_id) IN ('.$placeholders.') GROUP BY student_id,task_id,teacher_id) g
  JOIN mission_results first_result ON first_result.id=g.first_id
  JOIN mission_results last_result ON last_result.id=g.last_id');
 $summaryQuery->execute(array_merge(...array_values($groups)));
 foreach($summaryQuery as $summary)$summaries[$summary['student_id'].':'.$summary['task_id'].':'.$summary['teacher_id']]=$summary;
}
$esc=fn($x)=>htmlspecialchars((string)$x,ENT_QUOTES,'UTF-8');
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Historial de misiones · TEKO Math</title><link rel="stylesheet" href="../07_diseno_y_graficos/style.css"><link rel="stylesheet" href="../07_diseno_y_graficos/teko-ui.css?v=20261028-management"></head><body class="teko-management-page"><main class="mission-history teko-management-card"><a href="<?= $user['rol']==='docente'?'docente.php':'dashboard.php' ?>">← Volver al escritorio</a><p class="management-eyebrow">TEKO Math · Gestión educativa</p><h1>Historial de calificaciones</h1><p>Cada fila corresponde a un intento finalizado. La nota mide aciertos en la primera respuesta de cada ejercicio; no certifica dominio de todo el tema.</p><p>El historial comienza con los intentos guardados desde esta actualización. Los porcentajes anteriores no se convierten en resultados verificados.</p>
<p><?= $count ?> resultados · Página <?= $page ?></p>
<?php if(!$rows): ?><p>Todavía no hay intentos finalizados en esta página.</p><?php endif ?>
<?php foreach($rows as $row):
 $summary=$summaries[$row['student_id'].':'.$row['task_id'].':'.$row['teacher_id']];
 $first=$summary['first_percentage'];$last=$summary['last_percentage'];$best=$summary['best_percentage'];
 $time=(new DateTimeImmutable($row['completed_at'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Asuncion'));
?><article class="history-result"><h2><?= $esc($row['task_title']) ?></h2><p><?= $esc($row['nombre']) ?> · <?= $esc($time->format('d/m/Y H:i:s')) ?> (Paraguay)</p><p><strong><?= (int)$row['percentage'] ?>%</strong> · <?= (int)$row['correct_count'] ?> de <?= (int)$row['total_count'] ?> ejercicios</p><p>Para esta misión: primera <?= (int)$first ?>% · última <?= (int)$last ?>% · mejor <?= (int)$best ?>% · <?= (int)$summary['attempt_count'] ?> intentos.</p><small>La comparación incluye repeticiones de la actividad; no equivale a una prueba nueva independiente.</small></article><?php endforeach ?>
<nav aria-label="Páginas del historial"><?php if($page>1): ?><a class="btn" href="?page=<?= $page-1 ?>">Anterior</a><?php endif ?> <?php if($offset+$limit<$count): ?><a class="btn" href="?page=<?= $page+1 ?>">Siguiente</a><?php endif ?></nav></main></body></html>
