<?php
require_once __DIR__.'/../services/tutor_state.php';
require_once __DIR__.'/../services/student_analysis.php';
function checkAnalysis($value,$label){if(!$value)throw new RuntimeException($label);}
$s=TutorState::initial('funciones','lineal');
$s=QuestionResolver::transition($s,'No entiendo función lineal','');
$s=StudentAnalysis::adapt(StudentAnalysis::update($s,'No entiendo función lineal'));
$p=$s['learning_analysis']['funciones:lineal'];
checkAnalysis($p['doubts']===1&&$p['verified_correct']===0,'Doubt is not an incorrect attempt');
checkAnalysis($s['question_context']['intent']==='simple','Simplify concept response');
$s=StudentAnalysis::update($s,'sí');checkAnalysis($s['learning_analysis']['funciones:lineal']['verified_correct']===0,'Self report is not mastery');
$s['feedback']='correct';$s=StudentAnalysis::update($s,'answer');
checkAnalysis($s['learning_analysis']['funciones:lineal']['verified_correct']===1,'Verified feedback');
$s['feedback']=null;$s['question_context']['keys']=['trigonometria:seno'];$s=StudentAnalysis::update($s,'qué es seno');
checkAnalysis($s['learning_analysis']['trigonometria:seno']['verified_correct']===0,'Concept isolation');
$loaded=json_decode(json_encode($s),true);checkAnalysis($loaded['learning_analysis']===$s['learning_analysis'],'State persistence');
$s=TutorState::initial('funciones','lineal');$s['mode']='TEACHING_MODE';
$s['assessment_results']=[['topic'=>'funciones','subtopic'=>'constante','total'=>6,'direct_correct'=>0]];
checkAnalysis(StudentAnalysis::adapt($s)['lesson']['variant']===0,'Other subtopic must not change support');
$s['assessment_results'][0]['subtopic']='lineal';$s['assessment_results'][0]['direct_correct']=3;
checkAnalysis(StudentAnalysis::adapt($s)['lesson']['variant']===1,'Support threshold scales to six questions');
echo "PASS: adaptation, evidence, self reports, concept isolation and serialization\n";
