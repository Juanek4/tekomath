<?php
require_once __DIR__.'/language_service.php';
require_once __DIR__.'/assessment_bank.php';
class AssessmentService {
 public static function bank(string $topic, string $subtopic = ''): array {
  if($subtopic==='') {
   foreach(AssessmentBank::catalog() as $unit) if($unit['key']===$topic) $subtopic=$unit['subtopics'][0]['id'];
  }
  return array_map(function($q){
   $options=[];foreach($q['options'] as $i=>$option)$options[]=($i+1).') '.$option;
   return [$q['question']."\n".implode("\n",$options)."\nRespondé con el número de opción (1 a 4).",[(string)($q['correct_index']+1)],$q['hint']];
  },AssessmentBank::questions($topic,$subtopic));
 }
 private static function activeBank(array $a): array {
  return ($a['bank_version']??1)===2 ? self::bank($a['topic'],$a['subtopic']) : self::legacyBank($a['topic']);
 }
 private static function legacyBank(string $topic): array {
  $banks=[
   'funciones'=>[
    ['Si f(x)=2x+1, calculá f(3).',['7'],'Sustituí x por el valor indicado y respetá el orden de operaciones.'],
    ['Si f(x)=5 para cualquier x, ¿cuánto vale f(8)?',['5'],'Una función constante conserva su salida.'],
    ['En f(x)=3x, ¿qué entrada produce la salida 12?',['4'],'Buscá el número que multiplicado por tres produce esa salida.']],
   'trigonometria'=>[
    ['En un triángulo rectángulo, el cateto opuesto mide 3 y la hipotenusa 5. Escribí seno como fracción.',['3/5','0.6'],'Seno relaciona cateto opuesto con hipotenusa.'],
    ['¿Cuántos grados mide un ángulo recto? Escribí solo el número.',['90'],'Es la cuarta parte de una vuelta completa.'],
    ['Respecto de un ángulo, el cateto opuesto mide 6 y el adyacente 3. Calculá la tangente.',['2'],'Tangente relaciona opuesto con adyacente.']],
   'geometria'=>[
    ['Una recta pasa por (0,1) y (2,5). Calculá su pendiente.',['2'],'La pendiente es cambio vertical dividido por cambio horizontal.'],
    ['En y=3x+4, ¿cuál es la ordenada al origen?',['4'],'Evaluá qué ocurre cuando x vale cero.'],
    ['Para y=2x+1, calculá y cuando x=4.',['9'],'Reemplazá x y realizá primero la multiplicación.']],
   'combinatoria'=>[
    ['Tenés 3 remeras y 2 pantalones. ¿Cuántos conjuntos distintos podés formar eligiendo uno de cada tipo?',['6'],'Por cada remera hay dos elecciones de pantalón.'],
    ['¿De cuántas maneras se ordenan 3 libros distintos en una fila?',['6'],'Elegí sucesivamente entre tres, dos y un libro.'],
    ['De 4 estudiantes se eligen 2 sin importar el orden. ¿Cuántas parejas distintas hay?',['6'],'Contá parejas sin contar dos veces la misma pareja.']]
  ];return $banks[$topic]??[];
 }
 public static function transition(array $s,string $message,string $action): ?array {
  if($action==='assessment'&&!isset($s['assessment'])){
   if(!self::bank($s['topic'],$s['subtopic']??''))return null;
   $s['assessment']=['bank_version'=>2,'topic'=>$s['topic'],'subtopic'=>$s['subtopic']??'','index'=>0,'attempts'=>[],'assisted'=>false,'started_at'=>gmdate('c'),'return_mode'=>$s['mode']];
   return $s;
  }
  if(!isset($s['assessment']))return null;
  $a=&$s['assessment'];$bank=self::activeBank($a);
  if($action==='assessment_exit'){unset($s['assessment']);return $s;}
  if($a['index']>=count($bank))return $s;
  if($action==='assessment_hint'){$a['assisted']=true;return $s;}
  $normalized=str_replace([' ', ','],['','.'],mb_strtolower(trim($message)));
  // Questions and malformed input are not graded as wrong attempts.
  if(($a['bank_version']??1)===2 && !preg_match('/^[1-4]$/D',$normalized)){$a['notice']='Respondé con el número de opción (1 a 4). Podés pedir una pista.';return $s;}
  if(!preg_match('~^-?\d+(?:\.\d+)?(?:/\d+)?$~D',$normalized)){$a['notice']='Escribí un número o una fracción para responder. Podés pedir una pista o salir para consultar al tutor.';return $s;}
  $q=$bank[$a['index']];$correct=in_array($normalized,$q[1],true);
  $a['attempts'][]=['item'=>$a['index']+1,'correct'=>$correct,'assisted'=>$a['assisted']];
  $a['index']++;$a['assisted']=false;unset($a['notice']);
  if($a['index']===count($bank)){
   $direct=count(array_filter($a['attempts'],fn($x)=>$x['correct']&&!$x['assisted']));
   $s['assessment_results'][]=['topic'=>$a['topic'],'subtopic'=>$a['subtopic']??null,'bank_version'=>$a['bank_version']??1,'total'=>count($bank),'direct_correct'=>$direct,'attempts'=>$a['attempts'],'completed_at'=>gmdate('c'),'interpretation'=>$direct===count($bank)?'Evidencia inicial favorable en esta prueba breve':'Conviene repasar y comprobar con ejercicios diferentes'];
   $s['assessment_results']=array_slice($s['assessment_results'],-20);
  }return $s;
 }
 public static function response(array $s): array {
  $a=$s['assessment'];$bank=self::activeBank($a);
  $done=$a['index']>=count($bank);
  $text=$done?'Prueba terminada. '.end($s['assessment_results'])['interpretation'].'. Una prueba breve no demuestra dominio de toda la unidad.':'Comprobación de comprensión · Ejercicio '.($a['index']+1).' de '.count($bank)."\n\n".$bank[$a['index']][0];
  if($done){
   $correct=count(array_filter($a['attempts'],fn($attempt)=>$attempt['correct']));
   $direct=end($s['assessment_results'])['direct_correct'];
   $text.="\n\nResultado: {$correct} de ".count($bank)." correctas; {$direct} sin pista.";
  }
  if(!$done&&$a['assisted'])$text.="\n\nPista: ".$bank[$a['index']][2];
  if(isset($a['notice']))$text.="\n\n".$a['notice'];
  return TutorLanguage::response(['tutor_message_jopara'=>$text,'formula_display'=>['latex'=>'','note'=>'Comprobación de comprensión'],'visual_action'=>['type'=>'none'],'quick_options'=>$done?[['id'=>'assessment_exit','label'=>'Volver al tutor']]:[['id'=>'assessment_hint','label'=>'Necesito una pista'],['id'=>'assessment_exit','label'=>'Salir y consultar al tutor']]],$s);
 }
}
