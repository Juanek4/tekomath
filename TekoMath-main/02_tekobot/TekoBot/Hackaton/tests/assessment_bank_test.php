<?php
require_once __DIR__.'/../services/pedagogy_service.php';
require_once __DIR__.'/../services/tutor_state.php';
function bankAssert($value,$label){if(!$value)throw new RuntimeException($label);}
$seen=[];$topics=0;$graded=0;
foreach(AssessmentBank::catalog() as $unit){
 foreach($unit['subtopics'] as $sub){
  $questions=AssessmentBank::questions($unit['key'],$sub['id']);
  bankAssert(count($questions)>=6,'Coverage '.$unit['key'].':'.$sub['id']);$topics++;
  $state=TutorState::initial($unit['key'],$sub['id']);
  $state=PedagogyService::transition($state,'','assessment');
  $state=PedagogyService::transition($state,'no entiendo','');
  bankAssert(count($state['assessment']['attempts'])===0,'Doubt must not be graded');
  foreach($questions as $i=>$q){
   bankAssert(!isset($seen[$q['id']]),'Unique ID');$seen[$q['id']]=true;
   bankAssert(count($q['options'])===4 && count(array_unique($q['options']))===4,'Distinct options');
   bankAssert(is_int($q['correct_index']) && $q['correct_index']>=0 && $q['correct_index']<4,'Valid key');
   bankAssert(strlen($q['hint'])>10,'Hint present');
   $response=PedagogyService::response($state);
   bankAssert(str_contains($response['tutor_message_jopara'],$q['question']),'Current subtopic question');
   if($i===0)$state=PedagogyService::transition($state,'','assessment_hint');
   $state=PedagogyService::transition($state,(string)($q['correct_index']+1),'');$graded++;
  }
  $result=end($state['assessment_results']);
  bankAssert($result['total']===6 && $result['direct_correct']===5,'Grade and hint accounting');
  bankAssert($result['subtopic']===$sub['id'],'Result scope');
  $state=PedagogyService::transition($state,'1','');
  bankAssert(count($state['assessment_results'])===1,'No duplicate completion');
  $state=PedagogyService::transition($state,'','assessment_exit');
  $state=PedagogyService::transition($state,'','assessment');
  $wrong=(($questions[0]['correct_index']+1)%4)+1;
  $state=PedagogyService::transition($state,(string)$wrong,'');
  bankAssert($state['assessment']['attempts'][0]['correct']===false,'Wrong option');
 }
}
// Keep old three-item assessments readable until the student finishes or exits.
$legacy=TutorState::initial('funciones');
$legacy['assessment']=['topic'=>'funciones','index'=>0,'attempts'=>[],'assisted'=>false];
$legacy=AssessmentService::transition($legacy,'7','');
bankAssert($legacy['assessment']['attempts'][0]['correct']===true,'Legacy assessment compatibility');
echo "PASS: $topics subtopics, $graded questions, correct/wrong grading, hints, invalid input, scope and legacy compatibility.\n";
