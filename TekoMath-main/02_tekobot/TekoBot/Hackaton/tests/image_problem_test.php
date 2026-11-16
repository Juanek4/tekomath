<?php
require_once __DIR__.'/../services/pedagogy_service.php';require_once __DIR__.'/../services/tutor_state.php';
foreach(['topic','statement','confirm','guidance','answer','correct'] as $stage){
 $s=TutorState::initial('funciones','concepto');$s['workshop']=['stage'=>$stage,'statement'=>'2+2','attempts'=>8];
 $next=PedagogyService::transition($s,'3 + 5','image_problem');
 if(($next['workshop']['stage']??'')!=='confirm'||$next['workshop']['statement']!=='3 + 5')throw new RuntimeException('Image statement lost in '.$stage);
 if(($next['workshop']['attempts']??0)!==0)throw new RuntimeException('Old attempts leaked');
}
$s=TutorState::initial('funciones','concepto');$s['assessment']=['index'=>0];$s['numeric_query']=['value'=>2];$s['own_problem']=['statement'=>'old'];
$next=PedagogyService::transition($s,'3+5','image_problem');
if(isset($next['assessment'])||isset($next['own_problem'])||isset($next['numeric_query']))throw new RuntimeException('Stale response routing');
$unclear=PedagogyService::transition($s,'un texto sin datos suficientes','image_problem');
if(!in_array($unclear['workshop']['stage'],['clarify','confirm'],true))throw new RuntimeException('Needs clarification');
try{PedagogyService::transition($s,'','image_problem');throw new RuntimeException('Empty accepted');}catch(InvalidArgumentException $expected){}
echo "PASS: confirmed image creates a new problem from six workshop states, clears stale routing and requests clarification when necessary.\n";
