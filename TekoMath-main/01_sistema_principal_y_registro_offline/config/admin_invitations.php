<?php
class AdminInvitations {
 public const USER='tekoadmin';
 public static function credentials(PDO $db):array {
  static $ready=false;
  if(!$ready){
   teko_schema_exec($db,'CREATE TABLE IF NOT EXISTS console_credentials (id INT PRIMARY KEY, password_hash VARCHAR(255) NOT NULL, version INT NOT NULL DEFAULT 1, initial_password TINYINT NOT NULL DEFAULT 1) ENGINE=InnoDB');
   if(teko_schema_setup() && !$db->query('SELECT id FROM console_credentials WHERE id=1')->fetchColumn()){
    $bootstrap=(string)teko_config('ADMIN_BOOTSTRAP_HASH','');
    if(!password_get_info($bootstrap)['algo'])throw new RuntimeException('Configurá la credencial inicial de consola mediante instalación privada.');
    $db->prepare('INSERT IGNORE INTO console_credentials(id,password_hash) VALUES(1,?)')->execute([$bootstrap]);
   }
   $ready=true;
  }
  $row=$db->query('SELECT * FROM console_credentials WHERE id=1')->fetch();
  if(!$row || (teko_production() && $row['initial_password']))throw new RuntimeException('La consola requiere una contraseña propia antes de producción.');
  return $row;
 }
 public static function authenticate(string $user,string $password):bool {$row=self::credentials(getDB());return hash_equals(self::USER,$user)&&password_verify($password,$row['password_hash']);}
 public static function changePassword(PDO $db,string $current,string $next):void {
  $row=self::credentials($db);
  if(!password_verify($current,$row['password_hash']))throw new InvalidArgumentException('La contraseña actual no es correcta.');
  if(mb_strlen($next)<12||strlen($next)>72||password_verify($next,$row['password_hash']))throw new InvalidArgumentException('Usá una contraseña nueva de al menos 12 caracteres; si es muy larga, acortala.');
  $q=$db->prepare('UPDATE console_credentials SET password_hash=?,version=version+1,initial_password=0 WHERE id=1 AND version=?');$q->execute([password_hash($next,PASSWORD_DEFAULT),$row['version']]);
  if(!$q->rowCount())throw new InvalidArgumentException('La contraseña cambió en otra sesión. Iniciá sesión nuevamente.');
 }
 public static function ensure(PDO $db): void {teko_schema_exec($db,'CREATE TABLE IF NOT EXISTS teacher_account_invites (id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(150) NOT NULL, token_hash CHAR(64) NOT NULL UNIQUE, expires_at DATETIME NOT NULL, used_at DATETIME NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB');}
 public static function create(PDO $db,string $email): string {
  if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('Ingresá un correo válido.');
  $q=$db->prepare('SELECT id FROM usuarios WHERE email=?');$q->execute([$email]);if($q->fetchColumn())throw new InvalidArgumentException('Ese correo ya tiene una cuenta.');
  $token=bin2hex(random_bytes(32));$q=$db->prepare('INSERT INTO teacher_account_invites(email,token_hash,expires_at) VALUES(?,?,?)');$q->execute([$email,hash('sha256',$token),gmdate('Y-m-d H:i:s',time()+7*86400)]);return $token;
 }
 public static function redeem(PDO $db,string $token,string $name,string $email,string $password): void {
  if(!preg_match('/^[a-f0-9]{64}$/D',$token)||mb_strlen(trim($name))<2||(mb_strlen($password)<8||strlen($password)>72))throw new InvalidArgumentException('Revisá el nombre, la invitación y la contraseña (mínimo 8 caracteres).');
  $db->beginTransaction();try{
   $q=$db->prepare('SELECT * FROM teacher_account_invites WHERE token_hash=? FOR UPDATE');$q->execute([hash('sha256',$token)]);$invite=$q->fetch();
   if(!$invite||$invite['used_at']||$invite['expires_at']<=gmdate('Y-m-d H:i:s')||strcasecmp($invite['email'],$email)!==0)throw new InvalidArgumentException('La invitación no es válida, venció o no corresponde a ese correo.');
   $q=$db->prepare("INSERT INTO usuarios(nombre,email,password,rol) VALUES(?,?,?,'docente')");$q->execute([trim($name),$invite['email'],password_hash($password,PASSWORD_DEFAULT)]);
   $db->prepare('UPDATE teacher_account_invites SET used_at=? WHERE id=?')->execute([gmdate('Y-m-d H:i:s'),$invite['id']]);$db->commit();
  }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
 }
}
