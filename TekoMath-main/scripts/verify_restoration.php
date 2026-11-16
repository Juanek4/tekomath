<?php
require_once __DIR__.'/maintenance_lib.php';
$backup=realpath($argv[1]??'');
$allowed=strtolower(str_replace('\\','/',realpath(maintenancePrivateRoot().'/backups'))).'/';
if(!$backup || !str_starts_with(strtolower(str_replace('\\','/',$backup)),$allowed))throw new RuntimeException('Indicá un respaldo dentro del directorio privado de backups.');
$manifest=json_decode(file_get_contents($backup.'/manifest.json'),true,512,JSON_THROW_ON_ERROR);
foreach($manifest['databases'] as $module=>$source){
    if(!in_array($module,['main','tutor'],true)||$source['file']!==$module.'.sql')throw new RuntimeException('Manifiesto inválido.');
    if(!hash_equals($source['sha256'],hash_file('sha256',$backup.'/'.$source['file'])))throw new RuntimeException('El respaldo SQL cambió o está dañado.');
}
foreach($manifest['files'] as $file=>$hash){
    if(!in_array($file,['teko-math.private.php','my.ini','mysql-privileges.sql'],true) || !hash_equals($hash,hash_file('sha256',$backup.'/'.$file)))throw new RuntimeException('Un archivo privado del respaldo cambió.');
}
$db=maintenanceDb();$nonce=bin2hex(random_bytes(5));$created=[];$accounts=[];$clientFiles=[];
$directory=$backup.'/restore-'.$nonce;mkdir($directory,0700);
$private=require $backup.'/teko-math.private.php';$server=null;$tableCount=0;$rowCount=0;
try {
    foreach(['main','tutor'] as $module){
        $schema='teko_restore_'.$nonce.'_'.$module;
        if($db->query('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='.$db->quote($schema))->fetchColumn())throw new RuntimeException('La base temporal ya existe.');
        $db->exec('CREATE DATABASE `'.$schema.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');$created[]=$schema;
        $user='teko_restore_'.bin2hex(random_bytes(5));$password=bin2hex(random_bytes(24));$account=$db->quote($user)."@'127.0.0.1'";
        $db->exec('CREATE USER '.$account.' IDENTIFIED BY '.$db->quote($password));$accounts[]=$account;
        $db->exec('GRANT SELECT,INSERT,UPDATE,DELETE,CREATE,ALTER,INDEX,REFERENCES,DROP,LOCK TABLES ON `'.$schema.'`.* TO '.$account);
        $client=$directory.'/'.$module.'-client.cnf';$clientFiles[]=$client;
        $connection=teko_database('main');
        file_put_contents($client,"[client]\nhost=127.0.0.1\nport=".$connection['port']."\nuser=".$user."\npassword=".$password."\n");
        maintenanceCommand([dirname(__DIR__,3).'/mysql/bin/mysql.exe','--defaults-extra-file='.$client,'--default-character-set=utf8mb4','--database='.$schema],$backup.'/'.$module.'.sql');
        $actual=maintenanceTables($db,$schema);
        if($actual!==$manifest['databases'][$module]['tables'])throw new RuntimeException('No coincide el contenido o la estructura restaurada de '.$module.'.');
        $tableCount+=count($actual);$rowCount+=array_sum(array_column($actual,'rows'));
        foreach(['HOST'=>'127.0.0.1','PORT'=>$connection['port'],'NAME'=>$schema,'USER'=>$user,'PASSWORD'=>$password] as $key=>$value)$private[strtoupper($module).'_DB_'.$key]=$value;
    }
    unset($private['ADMIN_BOOTSTRAP_HASH']);$private['ENV']='local';$private['GEMINI_API_KEY']='';
    $config=$directory.'/restored.private.php';file_put_contents($config,"<?php\nreturn ".var_export($private,true).";\n");
    // Serve only a short-lived loopback instance; its SQL accounts cannot touch live databases.
    $env=getenv();$env['TEKO_CONFIG_FILE']=$config;$env['TEKO_RESTORE_NONCE']=$nonce;
    $listener=stream_socket_server('tcp://127.0.0.1:0',$errno,$message);
    if(!$listener)throw new RuntimeException('No se pudo reservar un puerto local.');
    $address=stream_socket_get_name($listener,false);$port=(int)substr($address,strrpos($address,':')+1);fclose($listener);
    $router=$directory.'/router.php';
    $runtime=var_export(dirname(__DIR__).'/config/runtime.php',true);
    file_put_contents($router,'<?php if(PHP_SAPI!=="cli-server")exit(1);if(parse_url($_SERVER["REQUEST_URI"],PHP_URL_PATH)==="/__restore_health"){require '.$runtime.';header("Content-Type: application/json");echo json_encode(["nonce"=>getenv("TEKO_RESTORE_NONCE"),"main"=>teko_database("main")["name"],"tutor"=>teko_database("tutor")["name"]]);exit;}return false;');
    $log=$directory.'/smoke-server.log';
    $server=proc_open([PHP_BINARY,'-S','127.0.0.1:'.$port,'-t',dirname(__DIR__),$router],[0=>['pipe','r'],1=>['file',$log,'a'],2=>['file',$log,'a']],$pipes,null,$env,['bypass_shell'=>true]);
    if(!is_resource($server))throw new RuntimeException('No se pudo iniciar el sitio restaurado.');fclose($pipes[0]);
    $ready=false;
    for($i=0;$i<40;$i++){
        $body=@file_get_contents('http://127.0.0.1:'.$port.'/__restore_health',false,stream_context_create(['http'=>['timeout'=>.3]]));
        $health=$body?json_decode($body,true):null;
        if($health && ($health['nonce']??'')===$nonce && $health['main']===$private['MAIN_DB_NAME'] && $health['tutor']===$private['TUTOR_DB_NAME']){$ready=true;break;}
        usleep(100000);
    }
    if(!$ready)throw new RuntimeException('No se confirmó la identidad aislada del sitio restaurado.');
    if(getenv('TEKO_RESTORE_APACHE')==='1'){
        proc_terminate($server);proc_close($server);
        $apacheRoot=dirname(__DIR__,3).'/apache';
        $phpRoot=dirname(__DIR__,3).'/php';
        $documentRoot=str_replace('\\','/',dirname(__DIR__));
        // The health route confirms the database identities before any HTTP fixture writes.
        $healthFile=$directory.'/health.php';
        file_put_contents($healthFile,str_replace('if(PHP_SAPI!=="cli-server")exit(1);','',file_get_contents($router)));
        $apacheConfig=$directory.'/httpd.conf';
        $lines=['ServerRoot "'.$apacheRoot.'"','Listen 127.0.0.1:'.$port,'ServerName 127.0.0.1','PidFile "'.$directory.'/httpd.pid"','ErrorLog "'.$directory.'/apache-error.log"'];
        foreach(['authz_core','authz_host','dir','env','mime','rewrite','alias'] as $module)$lines[]='LoadModule '.$module.'_module modules/mod_'.$module.'.so';
        foreach(['php8ts.dll','libpq.dll','libsqlite3.dll'] as $dll)$lines[]='LoadFile "'.$phpRoot.'/'.$dll.'"';
        $lines=array_merge($lines,['LoadModule php_module "'.$phpRoot.'/php8apache2_4.dll"','PHPINIDir "'.$phpRoot.'"','TypesConfig "'.$apacheRoot.'/conf/mime.types"','DocumentRoot "'.$documentRoot.'"','<Directory "'.$documentRoot.'">','Require all granted','AllowOverride All','</Directory>','SetEnv TEKO_CONFIG_FILE "'.$config.'"','SetEnv TEKO_RESTORE_NONCE '.$nonce,'<FilesMatch "\\.php$">','SetHandler application/x-httpd-php','</FilesMatch>','Alias /__restore_health "'.$healthFile.'"','<Directory "'.$directory.'">','Require local','AllowOverride None','</Directory>']);
        file_put_contents($apacheConfig,implode("\n",$lines)."\n");
        maintenanceCommand([$apacheRoot.'/bin/httpd.exe','-t','-f',$apacheConfig]);
        $server=proc_open([$apacheRoot.'/bin/httpd.exe','-f',$apacheConfig],[0=>['pipe','r'],1=>['file',$log,'a'],2=>['file',$log,'a']],$pipes,null,$env,['bypass_shell'=>true]);
        if(!is_resource($server))throw new RuntimeException('No se pudo iniciar Apache aislado.');
        fclose($pipes[0]);
        $ready=false;
        for($i=0;$i<40;$i++){
            $body=@file_get_contents('http://127.0.0.1:'.$port.'/__restore_health',false,stream_context_create(['http'=>['timeout'=>.3]]));
            $health=$body?json_decode($body,true):null;
            if($health && ($health['nonce']??'')===$nonce && $health['main']===$private['MAIN_DB_NAME'] && $health['tutor']===$private['TUTOR_DB_NAME']){$ready=true;break;}
            usleep(100000);
        }
        if(!$ready)throw new RuntimeException('No se confirmó el aislamiento de Apache.');
    }
    putenv('TEKO_RESTORE_TEST_URL=http://127.0.0.1:'.$port.'/');
    $testOutput=maintenanceCommand(['node',__DIR__.'/../tests/restored_site_smoke.cjs']);
    file_put_contents($directory.'/http-test-results.log',$testOutput);
    $report=['success'=>true,'tables_verified'=>$tableCount,'rows_verified'=>$rowCount,'comparison'=>'schema SHA-256 and canonical row content SHA-256 per table','http_smoke'=>'passed','server'=>getenv('TEKO_RESTORE_APACHE')==='1'?'apache':'php-built-in','test_output'=>'http-test-results.log','temporary_databases'=>$created];

}finally{
    if(is_resource($server)){proc_terminate($server);proc_close($server);}
    foreach($created as $schema){
        if(!preg_match('/^teko_restore_[a-f0-9]{10}_(main|tutor)$/D',$schema))throw new RuntimeException('Limpieza temporal rechazada por seguridad.');
        $db->exec('DROP DATABASE `'.$schema.'`');
    }
    foreach($accounts as $account)$db->exec('DROP USER IF EXISTS '.$account);
    foreach($clientFiles as $file)if(is_file($file))unlink($file);
    foreach([$config??null,$router??null,$healthFile??null] as $file)if($file&&is_file($file))unlink($file);
}


$report['temporary_databases_removed']=true;$report['temporary_accounts_removed']=true;
file_put_contents($directory.'/verification.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo json_encode($report,JSON_UNESCAPED_UNICODE).PHP_EOL;
