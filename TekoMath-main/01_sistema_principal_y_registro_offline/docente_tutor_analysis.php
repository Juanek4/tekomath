<?php
// Included only after requireDocente(); identities come from registered students.
$analysisRows=[];$analysisUnavailable=false;
try {
    require_once __DIR__.'/../02_tekobot/TekoBot/Hackaton/config/database.php';
    require_once __DIR__.'/../02_tekobot/TekoBot/Hackaton/services/assessment_bank.php';
    $assessmentTitles=[];
    foreach(AssessmentBank::catalog() as $unit)foreach($unit['subtopics'] as $subtopic){
        $assessmentTitles[$unit['key'].':'.$subtopic['id']]=$unit['title'].' · '.$subtopic['title'];
    }
    $assignedScope=TeacherAssignments::scope((int)$user['id']);
    $registered=$db->query("SELECT u.id, u.nombre FROM usuarios u WHERE rol = 'estudiante' AND {$assignedScope}")->fetchAll(PDO::FETCH_KEY_PAIR);
    $cursor=Database::getConnection()->query('SELECT l.portal_user_id AS student_id, s.started_at, t.state_json FROM tutor_session_states t JOIN learning_sessions s ON s.id=t.session_id JOIN portal_student_links l ON l.tutor_student_id=s.student_id ORDER BY s.id DESC LIMIT 500');
    foreach($cursor as $record){
        if(!isset($registered[$record['student_id']]))continue;
        $state=json_decode($record['state_json'],true);
        foreach($state['assessment_results']??[] as $test){
            $assessmentTitle=isset($test['subtopic'])
                ? 'Prueba de tema: '.($assessmentTitles[$test['topic'].':'.$test['subtopic']]??$test['subtopic'])
                : 'Prueba de unidad: '.$test['topic'];
            $analysisRows[]=['name'=>$registered[$record['student_id']],'concept'=>$assessmentTitle,'analysis'=>[
                'updated_at'=>$test['completed_at'],'level'=>$test['interpretation'],'support'=>'evaluacion',
                'interactions'=>$test['total'],'doubts'=>count(array_filter($test['attempts'],fn($x)=>$x['assisted'])),
                'verified_correct'=>$test['direct_correct'],'recent_signals'=>array_map(fn($x)=>'Ejercicio '.$x['item'].': '.($x['correct']?'correcto':'a revisar').($x['assisted']?' con pista':' sin pista'),$test['attempts']),
                'recommendation'=>'Revisar los ejercicios a reforzar; usar otra actividad antes de concluir dominio.'
            ]];
        }
        foreach($state['learning_analysis']??[] as $concept=>$analysis){
            $analysisRows[]=['name'=>$registered[$record['student_id']],'concept'=>$concept,'analysis'=>$analysis];
        }
    }
} catch(Throwable $e){$analysisUnavailable=true;error_log('Tutor analysis unavailable: '.$e->getMessage());}
$escapeAnalysis=fn($text)=>htmlspecialchars((string)$text,ENT_QUOTES,'UTF-8');
?>
<section class="card" aria-labelledby="tutor-analysis-title" style="padding:20px;margin:20px 0;overflow-x:auto">
 <h2 id="tutor-analysis-title">Análisis de aprendizaje · TekoBot</h2>
 <p>Estimación automática por tema y conversación, basada en señales observables. Pedir ayuda no es un error ni una calificación. Revisá las evidencias antes de tomar decisiones.</p>
 <?php if(!$analysisRows): ?>
 <p><?= $analysisUnavailable?'El análisis no está disponible en este momento.':'Todavía no hay análisis. Se genera cuando los estudiantes conversan con TekoBot con su cuenta.' ?></p>
 <?php else: ?>
 <table style="width:100%;border-collapse:collapse"><thead><tr><th>Estudiante / tema</th><th>Comprensión y apoyo</th><th>Evidencias</th><th>Siguiente intervención</th></tr></thead><tbody>
 <?php foreach($analysisRows as $row): $a=$row['analysis']; ?>
 <tr><td style="padding:12px;vertical-align:top"><strong><?= $escapeAnalysis($row['name']) ?></strong><br><?= $escapeAnalysis($row['concept']) ?><br><small><?= $escapeAnalysis($a['updated_at']??'') ?></small></td>
 <td style="padding:12px;vertical-align:top"><?= $escapeAnalysis($a['level']) ?><br>Apoyo: <?= $escapeAnalysis(str_replace('_',' ',$a['support'])) ?></td>
 <td style="padding:12px;vertical-align:top"><?= (int)$a['interactions'] ?> interacciones · <?= (int)$a['doubts'] ?> pedidos de ayuda · <?= (int)$a['verified_correct'] ?> respuestas verificadas<ul><?php foreach($a['recent_signals'] as $signal): ?><li><?= $escapeAnalysis($signal) ?></li><?php endforeach ?></ul></td>
 <td style="padding:12px;vertical-align:top"><?= $escapeAnalysis($a['recommendation']) ?></td></tr>
 <?php endforeach ?></tbody></table>
 <?php endif ?>
</section>
