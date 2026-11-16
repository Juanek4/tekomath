<?php
class AccountSecurity {
 public static function ensure(PDO $db):void {
  teko_schema_exec($db,'CREATE TABLE IF NOT EXISTS account_security (user_id INT PRIMARY KEY, disabled TINYINT NOT NULL DEFAULT 0, session_version INT NOT NULL DEFAULT 0, FOREIGN KEY(user_id) REFERENCES usuarios(id) ON DELETE CASCADE) ENGINE=InnoDB');
  teko_schema_exec($db,'CREATE TABLE IF NOT EXISTS security_migrations (name VARCHAR(100) PRIMARY KEY) ENGINE=InnoDB');
  teko_schema_exec($db,'CREATE TABLE IF NOT EXISTS auth_failures (bucket CHAR(64) PRIMARY KEY, failures INT NOT NULL, blocked_until BIGINT NOT NULL) ENGINE=InnoDB');
  if(!$db->query("SELECT name FROM security_migrations WHERE name='disable_original_demo_accounts'")->fetchColumn()){
   $seeds=['estudiante@teko.com'=>'Estudiante Demo','docente@teko.com'=>'Docente Demo','maria@teko.com'=>'María García','carlos@teko.com'=>'Carlos López'];
   $q=$db->prepare('SELECT id,nombre,password FROM usuarios WHERE email=?');
   foreach($seeds as $email=>$name){$q->execute([$email]);$row=$q->fetch();if($row&&$row['nombre']===$name&&password_verify('1234',$row['password']))$db->prepare('INSERT INTO account_security(user_id,disabled,session_version) VALUES(?,1,1) ON DUPLICATE KEY UPDATE disabled=1,session_version=session_version+1')->execute([$row['id']]);}
   $db->exec("INSERT IGNORE INTO security_migrations(name) VALUES('disable_original_demo_accounts')");
  }
 }
 public static function state(PDO $db,int $id):array {$q=$db->prepare('SELECT disabled,session_version FROM account_security WHERE user_id=?');$q->execute([$id]);return $q->fetch()?:['disabled'=>0,'session_version'=>0];}
 public static function bucket(string $kind,string $name):string{return hash('sha256',$kind.'|'.mb_strtolower(trim($name)).'|'.($_SERVER['REMOTE_ADDR']??'local'));}
 public static function limited(PDO $db,string $bucket):bool{$q=$db->prepare('SELECT failures,blocked_until FROM auth_failures WHERE bucket=?');$q->execute([$bucket]);$r=$q->fetch();return $r&&$r['failures']>=5&&$r['blocked_until']>time();}
 public static function failure(PDO $db,string $bucket):void{$db->prepare('INSERT INTO auth_failures(bucket,failures,blocked_until) VALUES(?,1,?) ON DUPLICATE KEY UPDATE failures=IF(blocked_until<UNIX_TIMESTAMP(),1,failures+1),blocked_until=VALUES(blocked_until)')->execute([$bucket,time()+300]);}
 public static function clear(PDO $db,string $bucket):void{$db->prepare('DELETE FROM auth_failures WHERE bucket=?')->execute([$bucket]);}
}
