<?php
require_once __DIR__.'/../01_sistema_principal_y_registro_offline/config/database.php';require_once __DIR__.'/../01_sistema_principal_y_registro_offline/config/admin_invitations.php';
$db=getDB();AdminInvitations::ensure($db);$email=bin2hex(random_bytes(12)).'@test.invalid';$token=AdminInvitations::create($db,$email);
try{
 try{AdminInvitations::redeem($db,$token,'Test','wrong@test.invalid','password-test');throw new RuntimeException('Wrong email accepted');}catch(InvalidArgumentException $expected){}
 AdminInvitations::redeem($db,$token,'Prueba docente',$email,'password-test');
 $q=$db->prepare('SELECT rol,password FROM usuarios WHERE email=?');$q->execute([$email]);$u=$q->fetch();if($u['rol']!=='docente'||!password_verify('password-test',$u['password']))throw new RuntimeException('Invalid teacher account');
 try{AdminInvitations::redeem($db,$token,'Test',$email,'password-test');throw new RuntimeException('Token reused');}catch(InvalidArgumentException $expected){}
 echo "PASS: email binding, teacher creation, password hashing and single-use invitation.\n";
}finally{$db->prepare('DELETE FROM teacher_account_invites WHERE email=?')->execute([$email]);$db->prepare('DELETE FROM usuarios WHERE email=?')->execute([$email]);}
