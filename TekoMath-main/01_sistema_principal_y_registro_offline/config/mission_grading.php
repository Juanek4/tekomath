<?php
function gradeMission(array $mission,array $answers): array {
 $items=[];
 foreach($mission['etapas']??[] as $i=>$stage){
  if(!empty($stage['opciones']))$items[$i.':-1']=$stage['opciones'];
  foreach($stage['pasos']??[] as $j=>$step)if(!empty($step['opciones']))$items[$i.':'.$j]=$step['opciones'];
 }
 if(!$items)throw new InvalidArgumentException('Esta misión no tiene ejercicios corregibles.');
 $seen=[];$correct=0;$attempts=0;$errors=0;
 foreach($answers as $answer){
  if(!is_array($answer))throw new InvalidArgumentException('Respuestas inválidas.');
  $key=(string)($answer['stage']??'').':'.(string)($answer['step']??-1);
  if(!isset($items[$key]))throw new InvalidArgumentException('Etapa inválida.');
  $option=null;foreach($items[$key] as $o)if((string)$o['id']===(string)($answer['option']??''))$option=$o;
  if(!$option)throw new InvalidArgumentException('Opción inválida.');
  $attempts++;if(empty($option['es_correcta']))$errors++;
  if(!array_key_exists($key,$seen)){$seen[$key]=!empty($option['es_correcta']);if($seen[$key])$correct++;}
 }
 if(count($seen)!==count($items))throw new InvalidArgumentException('Faltan respuestas para completar la misión.');
 return ['correct'=>$correct,'total'=>count($items),'attempts'=>$attempts,'errors'=>$errors,'percentage'=>(int)round(100*$correct/count($items))];
}
