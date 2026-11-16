<?php
require_once __DIR__.'/../services/tutor_state.php';
require_once __DIR__.'/../services/pedagogy_service.php';
$templates=[
 ['Profe, qué es piko %s?','definition'],
 ['Che, explicame %s con más detalle na','detail'],
 ['Mostrame un ejemplo de %s porfa','example'],
 ['No pillo %s, explicame más simple','simple'],
 ['Ndaikumbyi %s, más fácil por favor','simple'],
 ['xq se usa %s?','why'],
 ['Para qué sirve %s en la vida cotidiana?','usage'],
 ['Me confundo con %s, qué error debo evitar?','misconception']
];
$results=[];$failures=[];$counts=[];
foreach(QuestionResolver::cards() as $card){
 if(QuestionResolver::match($card['title'])!==[$card['key']])continue;
 foreach($templates as [$template,$intent])foreach(['es','es_py','jopara'] as $language){
  $message=sprintf($template,$card['title']);
  $s=TutorState::initial('funciones','trigonometricas_intro');$s['language']=$language;
  $next=QuestionResolver::transition($s,$message,'');$r=$next?QuestionResolver::response($next):[];
  $expected=in_array($card['key'],['funciones:dominio','funciones:recorrido','trigonometria:signos'],true)?'definition':$intent;
  $checks=['concept'=>($next['question_context']['keys']??[])===[$card['key']],
   'intent'=>($next['question_context']['intent']??'')===$expected,
   'content'=>mb_strlen($r['tutor_message_jopara']??'')>20,
   'formula'=>($r['formula_display']['latex']??null)===($card['formula']??''),
   'progress'=>($next['lesson']??null)===$s['lesson']&&($next['recent_evidence']??null)===[]];
  if($next){
   $follow=QuestionResolver::transition($next,'¿Y por qué?','');
   $checks['context']=($follow['question_context']['keys']??[])===[$card['key']];
   $resumed=PedagogyService::transition($next,'','resume');
   $checks['resume']=$resumed['mode']===$s['mode']&&$resumed['lesson']===$s['lesson'];
  }
  $ok=!in_array(false,$checks,true);$counts[$language]=($counts[$language]??0)+1;
  $row=['id'=>count($results)+1,'concept'=>$card['key'],'language'=>$language,'question'=>$message,'intent'=>$expected,'passed'=>$ok,'checks'=>$checks,'response'=>$r];
  $results[]=$row;if(!$ok)$failures[]=$row;
 }
}
$summary=['total'=>count($results),'passed'=>count($results)-count($failures),'failed'=>count($failures),'languages'=>$counts,'scope'=>'Synthetic offline regression, not a national certification or external model evaluation.'];
file_put_contents(__DIR__.'/simulation_paraguay_results.json',json_encode(['summary'=>$summary,'cases'=>$results],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
echo json_encode($summary,JSON_PRETTY_PRINT).PHP_EOL;
foreach(array_slice($failures,0,5) as $f)echo json_encode(['question'=>$f['question'],'checks'=>$f['checks']],JSON_UNESCAPED_UNICODE).PHP_EOL;
exit($failures?1:0);
