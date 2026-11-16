<?php
require_once __DIR__.'/../01_sistema_principal_y_registro_offline/config/database.php';
$db=getDB();$db->beginTransaction();
try {
 $ids=[];$insert=$db->prepare('INSERT INTO usuarios(nombre,email,password,rol) VALUES(?,?,?,?)');
 foreach(['docente','docente','estudiante','estudiante'] as $i=>$role){$insert->execute(['Prueba asignación',bin2hex(random_bytes(12)).'@test.invalid','not-a-login-hash',$role]);$ids[]=(int)$db->lastInsertId();}
 [$t1,$t2,$s1,$s2]=$ids;
 try{TeacherAssignments::code($db,$t1);throw new Exception('Code generated without class');}catch(InvalidArgumentException $expected){}
 $class=['colegio'=>'Colegio de prueba','curso'=>'1','bachillerato'=>'Científico · Ciencias Básicas','seccion'=>'A'];
 $c1=TeacherAssignments::saveClass($db,$t1,$class);$c2=TeacherAssignments::saveClass($db,$t2,$class);
 if(TeacherAssignments::classInfo($db,$t1)['colegio']!==$class['colegio'])throw new Exception('Class not persisted');
 if(TeacherAssignments::saveClass($db,$t1,array_replace($class,['seccion'=>'B']))!==$c1)throw new Exception('Existing code changed');
 try{TeacherAssignments::saveClass($db,$t1,array_replace($class,['curso'=>'4']));throw new Exception('Invalid course accepted');}catch(InvalidArgumentException $expected){}
 if(!TeacherAssignments::join($db,$s1,$c1)||!TeacherAssignments::join($db,$s2,$c2))throw new Exception('Join failed');
 if(TeacherAssignments::join($db,$s1,$c1))throw new Exception('Duplicate assignment');
 if(TeacherAssignments::join($db,$t2,$c1))throw new Exception('Teacher enrolled as student');
 if(TeacherAssignments::join($db,$s1,'invalid'))throw new Exception('Invalid invitation accepted');
 $rows=$db->query('SELECT u.id FROM usuarios u WHERE '.TeacherAssignments::scope($t1))->fetchAll(PDO::FETCH_COLUMN);
 if(array_map('intval',$rows)!==[$s1])throw new Exception('Cross teacher leak');
 if(!TeacherAssignments::join($db,$s1,$c2))throw new Exception('Multiple teachers unsupported');
 echo "PASS: assignment, duplicate prevention, role checks, invalid codes, teacher isolation and multiple teachers. Test records rolled back.\n";
} finally {$db->rollBack();}
