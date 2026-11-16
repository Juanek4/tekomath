<?php
// One-time local provisioning. Run manually from CLI with XAMPP's local SQL administrator.
if(PHP_SAPI!=='cli')exit(1);
$target=dirname(__DIR__,3).'/teko-private/teko-math.php';
if(is_file($target)){fwrite(STDERR,"La configuración privada ya existe; no se modifica.\n");exit(1);}
$root=new PDO('mysql:host=127.0.0.1;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$hash=$root->query('SELECT password_hash FROM teko_math.console_credentials WHERE id=1')->fetchColumn();
if(!$hash)throw new RuntimeException('Falta la credencial de consola existente.');
$config=['ENV'=>'local','PUBLIC_URL'=>'http://localhost/TekoMath-main/','ADMIN_BOOTSTRAP_HASH'=>$hash,'GEMINI_API_KEY'=>''];
$created=[];
try {
 foreach(['MAIN'=>'teko_math','TUTOR'=>'kyhyjey_db'] as $module=>$database){
    $user='teko_'.strtolower($module).'_'.bin2hex(random_bytes(3));$password=bin2hex(random_bytes(32));
    $account=$root->quote($user)."@'127.0.0.1'";
    $root->exec('CREATE USER '.$account.' IDENTIFIED BY '.$root->quote($password));$created[]=$account;
    $root->exec('GRANT SELECT,INSERT,UPDATE,DELETE,CREATE,ALTER,INDEX,REFERENCES ON `'.$database.'`.* TO '.$account);
    foreach(['HOST'=>'127.0.0.1','PORT'=>3306,'NAME'=>$database,'USER'=>$user,'PASSWORD'=>$password] as $key=>$value)$config[$module.'_DB_'.$key]=$value;
    new PDO('mysql:host=127.0.0.1;dbname='.$database.';charset=utf8mb4',$user,$password,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
 }
 if(!is_dir(dirname($target)))mkdir(dirname($target),0700,true);
 $handle=fopen($target,'x');if(!$handle)throw new RuntimeException('No se pudo crear la configuración privada.');
 fwrite($handle,"<?php\n// Private local credentials. Keep outside htdocs and backups served by HTTP.\nreturn ".var_export($config,true).";\n");fclose($handle);
 echo "Configuración privada creada fuera de htdocs; dos cuentas SQL verificadas. No se cambiaron datos ni contraseñas de usuarios.\n";
}catch(Throwable $error){
 foreach($created as $account)$root->exec('DROP USER IF EXISTS '.$account);
 throw $error;
}

