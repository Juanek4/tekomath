<?php
if(PHP_SAPI!=='cli')exit(1);
require_once __DIR__.'/../config/runtime.php';
function maintenanceDb(): PDO {
    if(teko_production())throw new RuntimeException('Esta herramienta está limitada al mantenimiento local.');
    $main=teko_database('main');$tutor=teko_database('tutor');
    if($main['host']!==$tutor['host'] || $main['port']!==$tutor['port'])throw new RuntimeException('Las bases deben estar en el mismo servidor para este respaldo consistente.');
    return new PDO('mysql:host='.$main['host'].';port='.$main['port'].';charset=utf8mb4',
        getenv('TEKO_MAINTENANCE_USER')?:'root',getenv('TEKO_MAINTENANCE_PASSWORD')?:'',
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_STRINGIFY_FETCHES=>true]);
}
function maintenanceCommand(array $command, ?string $inputFile=null): string {
    $descriptors=[0=>$inputFile?['file',$inputFile,'r']:['pipe','r'],1=>['pipe','w'],2=>['pipe','w']];
    $process=proc_open($command,$descriptors,$pipes,null,null,['bypass_shell'=>true]);
    if(!is_resource($process))throw new RuntimeException('No se pudo iniciar la herramienta SQL.');
    if(!$inputFile)fclose($pipes[0]);
    $output=stream_get_contents($pipes[1]);fclose($pipes[1]);
    $error=stream_get_contents($pipes[2]);fclose($pipes[2]);
    if(proc_close($process)!==0)throw new RuntimeException('La herramienta SQL falló: '.trim($error));
    return $output;
}
function maintenanceTables(PDO $db,string $schema): array {
    if(!preg_match('/^[a-zA-Z0-9_-]+$/D',$schema))throw new RuntimeException('Base inválida.');
    $q=$db->prepare('SELECT TABLE_NAME,ENGINE,TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=? ORDER BY TABLE_NAME');$q->execute([$schema]);
    $result=[];
    foreach($q->fetchAll() as $table){
        $name=$table['TABLE_NAME'];
        if($table['TABLE_TYPE']!=='BASE TABLE' || !preg_match('/^[a-zA-Z0-9_]+$/D',$name))throw new RuntimeException('Objeto no soportado en la comparación.');
        $rows=[];
        foreach($db->query('SELECT * FROM `'.$schema.'`.`'.$name.'`') as $row)$rows[]=json_encode($row,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
        sort($rows,SORT_STRING);
        $create=$db->query('SHOW CREATE TABLE `'.$schema.'`.`'.$name.'`')->fetch(PDO::FETCH_NUM)[1];
        // Normalize only the schema name; preserve constraints, columns, indexes and auto-increment.
        $create=str_replace('`'.$schema.'`.','`__schema__`.',$create);
        $result[$name]=['rows'=>count($rows),'content_sha256'=>hash('sha256',implode("\n",$rows)),
            'schema_sha256'=>hash('sha256',$create),'engine'=>$table['ENGINE']];
    }
    return $result;
}
function maintenancePrivateRoot(): string {return dirname(__DIR__,3).'/teko-private';}
function maintenanceClientFile(string $directory): string {
    $connection=teko_database('main');$path=$directory.'/maintenance-client.cnf';
    $escape=fn($value)=>str_replace(['\\','"',"\r","\n"],['\\\\','\\"','\\r','\\n'],(string)$value);
    $values=['host'=>$connection['host'],'port'=>$connection['port'],'user'=>getenv('TEKO_MAINTENANCE_USER')?:'root','password'=>getenv('TEKO_MAINTENANCE_PASSWORD')?:''];
    $text="[client]\n";foreach($values as $key=>$value)$text.=$key.'="'.$escape($value)."\"\n";
    if(file_put_contents($path,$text,LOCK_EX)===false)throw new RuntimeException('No se pudo preparar el cliente privado.');
    return $path;
}
