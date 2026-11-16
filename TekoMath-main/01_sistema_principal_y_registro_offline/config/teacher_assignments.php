<?php
class TeacherAssignments {
 public static function ensure(PDO $db): void {
  teko_schema_exec($db,'CREATE TABLE IF NOT EXISTS docente_alumnos (docente_id INT NOT NULL, estudiante_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(docente_id,estudiante_id), INDEX idx_alumno(estudiante_id), FOREIGN KEY(docente_id) REFERENCES usuarios(id) ON DELETE CASCADE, FOREIGN KEY(estudiante_id) REFERENCES usuarios(id) ON DELETE CASCADE) ENGINE=InnoDB');
  teko_schema_exec($db,'CREATE TABLE IF NOT EXISTS docente_invitaciones (docente_id INT PRIMARY KEY, codigo VARCHAR(32) NOT NULL UNIQUE, FOREIGN KEY(docente_id) REFERENCES usuarios(id) ON DELETE CASCADE) ENGINE=InnoDB');
  foreach(['colegio'=>'VARCHAR(160)','curso'=>'TINYINT','bachillerato'=>'VARCHAR(160)','seccion'=>'VARCHAR(40)'] as $column=>$type){
   $q=$db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
   $q->execute(['docente_invitaciones',$column]);
   if(!$q->fetchColumn())teko_schema_exec($db,'ALTER TABLE docente_invitaciones ADD COLUMN '.$column.' '.$type.' NULL');
  }
 }
 public static function scope(int $teacher,string $column='u.id'): string {
  if(!in_array($column,['u.id','p.estudiante_id','pm.estudiante_id'],true))throw new InvalidArgumentException('Invalid scope');
  return ' EXISTS (SELECT 1 FROM docente_alumnos da WHERE da.docente_id='.(int)$teacher.' AND da.estudiante_id='.$column.') ';
 }
 public static function code(PDO $db,int $teacher): string {
  $class=self::classInfo($db,$teacher);
  if(!$class || !$class['colegio'] || !$class['curso'] || !$class['bachillerato'] || !$class['seccion'])throw new InvalidArgumentException('Completá los datos de la clase antes de generar el código.');
  for($attempt=0;$attempt<20;$attempt++){
   $q=$db->prepare('SELECT codigo FROM docente_invitaciones WHERE docente_id=?');$q->execute([$teacher]);$code=$q->fetchColumn();
   if(is_string($code)&&preg_match('/^[A-Z][0-9]{6}$/D',$code))return $code;
   $candidate=chr(random_int(65,90)).str_pad((string)random_int(0,999999),6,'0',STR_PAD_LEFT);
   if($code){
    $q=$db->prepare('UPDATE IGNORE docente_invitaciones SET codigo=? WHERE docente_id=? AND codigo=?');$q->execute([$candidate,$teacher,$code]);
   }else{
    $q=$db->prepare('INSERT IGNORE INTO docente_invitaciones(docente_id,codigo) VALUES(?,?)');$q->execute([$teacher,$candidate]);
   }
  }
  throw new RuntimeException('No pudimos generar un código único. Intentá nuevamente.');
 }
 public static function classInfo(PDO $db,int $teacher): ?array {
  $q=$db->prepare('SELECT codigo,colegio,curso,bachillerato,seccion FROM docente_invitaciones WHERE docente_id=?');
  $q->execute([$teacher]);
  return $q->fetch()?:null;
 }
 public static function saveClass(PDO $db,int $teacher,array $input): string {
  $values=[];
  foreach(['colegio'=>160,'bachillerato'=>160,'seccion'=>40] as $field=>$limit){
   $value=is_string($input[$field]??null)?trim($input[$field]):'';
   if($value==='' || mb_strlen($value)>$limit || preg_match('/[\x00-\x1F\x7F]/u',$value))throw new InvalidArgumentException('Completá colegio, bachillerato y sección con datos válidos.');
   $values[$field]=$value;
  }
  $course=filter_var($input['curso']??null,FILTER_VALIDATE_INT);
  if(!in_array($course,[1,2,3],true))throw new InvalidArgumentException('Seleccioná un curso de Educación Media.');
  // An existing code and its student assignments remain associated with this class.
  $ownsTransaction=!$db->inTransaction();
  if($ownsTransaction)$db->beginTransaction();
  try{
   $q=$db->prepare("SELECT id FROM usuarios WHERE id=? AND rol='docente' FOR UPDATE");$q->execute([$teacher]);
   if(!$q->fetchColumn())throw new InvalidArgumentException('Cuenta docente inválida.');
   $q=$db->prepare('SELECT codigo FROM docente_invitaciones WHERE docente_id=?');$q->execute([$teacher]);$existing=$q->fetchColumn();
   if(!$existing){
    for($attempt=0;$attempt<20;$attempt++){
     $candidate=chr(random_int(65,90)).str_pad((string)random_int(0,999999),6,'0',STR_PAD_LEFT);
     $q=$db->prepare('INSERT IGNORE INTO docente_invitaciones(docente_id,codigo) VALUES(?,?)');$q->execute([$teacher,$candidate]);
     if($q->rowCount())break;
    }
   }
   $q=$db->prepare('UPDATE docente_invitaciones SET colegio=?,curso=?,bachillerato=?,seccion=? WHERE docente_id=?');
   $q->execute([$values['colegio'],$course,$values['bachillerato'],$values['seccion'],$teacher]);
   $code=self::code($db,$teacher);
   if($ownsTransaction)$db->commit();
   return $code;
  }catch(Throwable $error){if($ownsTransaction)$db->rollBack();throw $error;}
 }
 public static function join(PDO $db,int $student,string $code): bool {
  $code=strtoupper(trim($code));
  if(!preg_match('/^[A-Z][0-9]{6}$/D',$code))return false;
  $q=$db->prepare("INSERT IGNORE INTO docente_alumnos(docente_id,estudiante_id) SELECT i.docente_id,u.id FROM docente_invitaciones i JOIN usuarios d ON d.id=i.docente_id AND d.rol='docente' JOIN usuarios u ON u.id=? AND u.rol='estudiante' WHERE i.codigo=?");$q->execute([$student,$code]);return $q->rowCount()>0;
 }
}
