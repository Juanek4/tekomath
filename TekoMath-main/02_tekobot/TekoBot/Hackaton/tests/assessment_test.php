<?php
require_once __DIR__.'/../services/pedagogy_service.php';
require_once __DIR__.'/../services/tutor_state.php';
function ensureAssessment($ok,$label){if(!$ok)throw new RuntimeException($label);}
foreach(['funciones','trigonometria','geometria','combinatoria'] as $topic){
 $s=TutorState::initial($topic);$before=$s['lesson'];$s=PedagogyService::transition($s,'','assessment');
 $s=PedagogyService::transition($s,'no entiendo','');ensureAssessment(count($s['assessment']['attempts'])===0,'Doubt is not an error');
 foreach(AssessmentService::bank($topic) as $i=>$q){
  if($i===0)$s=PedagogyService::transition($s,'','assessment_hint');
  $s=PedagogyService::transition($s,$q[1][0],'');
  $response=PedagogyService::response($s);ensureAssessment(!empty($response['tutor_message_jopara']),'Response');
 }
 $result=end($s['assessment_results']);ensureAssessment($result['direct_correct']===count(AssessmentService::bank($topic))-1,'Hints separated');
 $s=PedagogyService::transition($s,'999','');ensureAssessment(count($s['assessment_results'])===1,'No duplicate completion');
 $s=PedagogyService::transition($s,'','assessment_exit');ensureAssessment($s['lesson']===$before&&!isset($s['assessment']),'Resume lesson');
 $s=PedagogyService::transition($s,'','assessment');$wrong=AssessmentService::bank($topic)[0][1][0]==='1'?'2':'1';$s=PedagogyService::transition($s,$wrong,'');
 ensureAssessment($s['assessment']['attempts'][0]['correct']===false,'Incorrect answer recorded');
}
echo "PASS: four units, grading, hints, ungradable doubts, no duplicate completion, resume\n";
