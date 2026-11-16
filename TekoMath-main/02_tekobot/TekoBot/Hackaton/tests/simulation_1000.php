<?php
// Offline regression: real production transitions and responses, no model/API calls.
require_once __DIR__.'/../services/tutor_state.php';
require_once __DIR__.'/../services/pedagogy_service.php';
$cards=array_values(array_filter(QuestionResolver::cards(), fn($c)=>QuestionResolver::match($c['title'])===[$c['key']]));
$templates=[['¿Qué es %s?','definition'],['Explicame %s con mas detalle','detail'],['Dame un ejemplo de %s','example'],['No entiendo %s, más simple por favor','simple'],['¿Por qué se usa %s?','why'],['¿Para qué sirve %s?','usage'],['Me confundo con %s, ¿qué error debo evitar?','misconception']];
$rows=[];$failed=0;
for($i=0;$i<1000;$i++) {
    $card=$cards[$i%count($cards)]; $template=$templates[$i%count($templates)];
    $message=sprintf($template[0],$card['title']);
    $state=TutorState::initial('funciones','trigonometricas_intro');$state['language']='es';
    $before=$state; $state=QuestionResolver::transition($state,$message,'');
    $response=$state?QuestionResolver::response($state):[];
    $checks=[
        'concept'=>($state['question_context']['keys']??[])===[$card['key']],
        'intent'=>($state['question_context']['intent']??'')===(in_array($card['key'],['funciones:dominio','funciones:recorrido','trigonometria:signos'],true)?'definition':$template[1]),
        'content'=>str_contains($response['tutor_message_jopara']??'',$card['title'])&&mb_strlen($response['tutor_message_jopara']??'')>20,
        'formula'=>($response['formula_display']['latex']??'')===($card['formula']??''),
        'progress'=>($state['lesson']??null)===$before['lesson']&&($state['recent_evidence']??null)===[],
    ];
    if($state){
        $follow=QuestionResolver::transition($state,'¿Y por qué?','');
        $checks['followup']=($follow['question_context']['keys']??[])===[$card['key']];
    }
    $passed=!in_array(false,$checks,true);if(!$passed)$failed++;
    $rows[]=['id'=>$i+1,'concept'=>$card['key'],'question'=>$message,'checks'=>$checks,'passed'=>$passed,'response'=>$response];
}
$report=['total'=>count($rows),'passed'=>1000-$failed,'failed'=>$failed,'concepts'=>count(array_unique(array_column($rows,'concept'))),'scope'=>'Offline resolver; does not evaluate external AI, database or browser.','cases'=>$rows];
file_put_contents(__DIR__.'/simulation_1000_results.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo json_encode(array_diff_key($report,['cases'=>true]),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
exit($failed?1:0);
