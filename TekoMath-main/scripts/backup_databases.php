<?php
require_once __DIR__.'/maintenance_lib.php';
$db=maintenanceDb();
$stamp=(new DateTimeImmutable('now',new DateTimeZone('America/Asuncion')))->format('Ymd-His');
$directory=maintenancePrivateRoot().'/backups/full-'.$stamp.'-'.bin2hex(random_bytes(3));
if(!mkdir($directory,0700,true))throw new RuntimeException('No se pudo crear el directorio del respaldo.');
$client=maintenanceClientFile($directory);$locked=false;
try {
    $schemas=[teko_database('main')['name'],teko_database('tutor')['name']];
    $q=$db->prepare('SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA IN (?,?)');$q->execute($schemas);
    if($q->fetchColumn())throw new RuntimeException('Hay disparadores: usar un procedimiento de respaldo que los incluya.');
    foreach(['ROUTINES'=>'ROUTINE_SCHEMA','EVENTS'=>'EVENT_SCHEMA'] as $table=>$column){
        $q=$db->prepare('SELECT COUNT(*) FROM information_schema.'.$table.' WHERE '.$column.' IN (?,?)');$q->execute($schemas);
        if($q->fetchColumn())throw new RuntimeException('Hay rutinas o eventos: requieren un respaldo especializado.');
    }
    // A short read barrier keeps both individual dumps and their manifest at one logical point.
    $db->exec('FLUSH TABLES WITH READ LOCK');$locked=true;
    $manifest=['format'=>1,'created_at'=>(new DateTimeImmutable('now',new DateTimeZone('America/Asuncion')))->format(DATE_ATOM),'databases'=>[]];
    $dump=dirname(__DIR__,3).'/mysql/bin/mysqldump.exe';
    foreach(['main','tutor'] as $module){
        $schema=teko_database($module)['name'];$file=$module.'.sql';
        maintenanceCommand([$dump,'--defaults-extra-file='.$client,'--single-transaction','--quick','--skip-lock-tables','--skip-comments',
            '--hex-blob','--skip-triggers','--routines=false','--events=false','--default-character-set=utf8mb4','--result-file='.$directory.'/'.$file,$schema]);
        $manifest['databases'][$module]=['source'=>$schema,'file'=>$file,'sha256'=>hash_file('sha256',$directory.'/'.$file),'tables'=>maintenanceTables($db,$schema)];
    }
    maintenanceCommand([$dump,'--defaults-extra-file='.$client,'--skip-lock-tables','--skip-comments','--result-file='.$directory.'/mysql-privileges.sql','mysql','db','global_priv']);
    $private=getenv('TEKO_CONFIG_FILE')?:maintenancePrivateRoot().'/teko-math.php';
    if(!copy($private,$directory.'/teko-math.private.php'))throw new RuntimeException('No se pudo respaldar la configuración.');
    if(!copy(dirname(__DIR__,3).'/mysql/bin/my.ini',$directory.'/my.ini'))throw new RuntimeException('No se pudo respaldar my.ini.');
    foreach(['teko-math.private.php','my.ini','mysql-privileges.sql'] as $file)$manifest['files'][$file]=hash_file('sha256',$directory.'/'.$file);
    file_put_contents($directory.'/manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),LOCK_EX);
    echo json_encode(['success'=>true,'backup'=>$directory,'tables'=>array_sum(array_map(fn($x)=>count($x['tables']),$manifest['databases']))],JSON_UNESCAPED_UNICODE).PHP_EOL;
}finally{
    if($locked)$db->exec('UNLOCK TABLES');
    if(is_file($client))unlink($client);
}
