<?php
require_once __DIR__.'/question_resolver.php';
/** Observable learning support signals, never a diagnosis or an inferred mastery score. */
class StudentAnalysis {
    public static function update(array $state, string $message, string $action=''): array {
        if($message==='__init__'||isset($state['assessment']))return $state;
        $keys=$state['question_context']['keys']??[];
        $key=count($keys)===1?$keys[0]:$state['topic'].':'.$state['subtopic'];
        $p=$state['learning_analysis'][$key]??['interactions'=>0,'doubts'=>0,'examples'=>0,'verified_correct'=>0,'recent_signals'=>[]];
        $p['interactions']++;
        $text=QuestionResolver::normalize($message);
        $doubt=preg_match('/no ent|no me quedo claro|mas facil|mas simple|confund|no se|no puedo/u',$text)||in_array($action,['confused','partial'],true);
        $example=str_contains($text,'ejemplo')||$action==='example';
        if($doubt)$p['doubts']++;
        if($example)$p['examples']++;
        // Only backend-verified feedback counts as evidence, never «sí» or a claimed answer.
        if(($state['feedback']??null)==='correct')$p['verified_correct']++;
        $p['support']=$doubt?'paso_a_paso':($example?'con_ejemplo':($p['support']??'estandar'));
        if(preg_match('/mas detalle|profund|mas avanzado/u',$text))$p['support']='ampliado';
        $p['level']=$p['verified_correct']>=3?'Comprensión con evidencia en actividades':($p['verified_correct']?'Comprensión inicial con evidencia':'Nivel aún no determinado');
        $signal=$doubt?'Pidió una explicación más sencilla':($example?'Pidió un ejemplo':(($state['feedback']??null)==='correct'?'Respuesta verificada por el motor':'Consulta o avance de la lección'));
        $p['recent_signals']=array_slice(array_merge($p['recent_signals'],[$signal]),-5);
        $p['recommendation']=$p['support']==='paso_a_paso'?'Explicar una idea por vez, revisar el significado de los términos y pedir que describa el primer paso.':($p['support']==='con_ejemplo'?'Mostrar un ejemplo distinto y pedir que explique qué relación identifica.':'Comprobar comprensión con una actividad antes de concluir dominio.');
        $p['updated_at']=gmdate('c');
        $state['learning_analysis'][$key]=$p;$state['analysis_key']=$key;
        return $state;
    }
    public static function adapt(array $state): array {
        $p=$state['learning_analysis'][$state['analysis_key']??'']??[];
        $tests=array_values(array_filter($state['assessment_results']??[],fn($test)=>$test['topic']===$state['topic'] &&
            (!isset($test['subtopic']) || $test['subtopic']===$state['subtopic'])));
        if($tests) {
            $latest=end($tests);
            if($latest['direct_correct']/max(1,$latest['total'])<2/3)$p['support']='paso_a_paso';
        }
        if(!in_array($state['mode'],['QUESTION_MODE','TEACHING_MODE'],true))return $state;
        if(($p['support']??'')==='paso_a_paso') {
            if($state['mode']==='QUESTION_MODE'&&in_array($state['question_context']['intent'],['definition','detail'],true))$state['question_context']['intent']='simple';
            if($state['mode']==='TEACHING_MODE')$state['lesson']['variant']=max(1,$state['lesson']['variant']??0);
        }
        return $state;
    }
}
