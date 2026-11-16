<?php
// Server environment overrides the private file. Never load configuration from HTTP input.
function teko_config(string $key, mixed $default=null): mixed {
    static $private;
    if($private===null){
        $private=[];
        $path=getenv('TEKO_CONFIG_FILE')?:dirname(__DIR__,3).'/teko-private/teko-math.php';
        if(is_file($path)){
            $resolved=str_replace('\\','/',realpath($path));
            $public=str_replace('\\','/',realpath(dirname(__DIR__)));
            $documentRoot=realpath($_SERVER['DOCUMENT_ROOT']??'');
            foreach(array_filter([$public,$documentRoot?str_replace('\\','/',$documentRoot):null]) as $root){
                if(str_starts_with(strtolower($resolved),strtolower(rtrim($root,'/')).'/'))
                    throw new RuntimeException('La configuración privada debe estar fuera del directorio público.');
            }
            $private=require $path;
            if(!is_array($private))throw new RuntimeException('Configuración privada inválida.');
        }elseif(getenv('TEKO_CONFIG_FILE'))throw new RuntimeException('No se encontró la configuración privada indicada.');
    }
    $value=getenv('TEKO_'.$key);
    return $value!==false?$value:($private[$key]??$default);
}
function teko_production(): bool {return teko_config('ENV','local')==='production';}
function teko_maintenance_path(): string {return (string)teko_config('MAINTENANCE_FILE',dirname(__DIR__,3).'/teko-private/maintenance.flag');}
function teko_schema_setup(): bool {return !teko_production() || (PHP_SAPI==='cli' && getenv('TEKO_SCHEMA_SETUP')==='1');}
function teko_schema_exec(PDO $db,string $sql): int|false {return teko_schema_setup()?$db->exec($sql):0;}
function teko_required_tables(string $module): array {
    return $module==='main'
        ? ['usuarios','tareas','materiales','progreso_quizzes','progreso_misiones','salas_clase','packs_quiz',
            'docente_alumnos','docente_invitaciones','teacher_account_invites','account_security','security_migrations',
            'auth_failures','console_credentials','mission_attempts','mission_results','arcade_account_progress','password_reset_links']
        : ['students','learning_sessions','chat_history','curriculum_topics','exercises','student_progress','diagnostic_tests','tutor_session_states','portal_student_links'];
}
function teko_database(string $module): array {
    $prefix=strtoupper($module).'_DB_';
    return ['host'=>(string)teko_config($prefix.'HOST','127.0.0.1'),
        'port'=>(int)teko_config($prefix.'PORT',3306),
        'name'=>(string)teko_config($prefix.'NAME',$module==='main'?'teko_math':'kyhyjey_db'),
        'user'=>(string)teko_config($prefix.'USER',teko_production()?'':'root'),
        'password'=>(string)teko_config($prefix.'PASSWORD','')];
}
function teko_validate_runtime(): void {
    if(!in_array(teko_config('ENV','local'),['local','production'],true))throw new RuntimeException('Entorno inválido.');
    foreach(['main','tutor'] as $module){
        $db=teko_database($module);
        if(!preg_match('/^[a-zA-Z0-9_-]+$/D',$db['name'])||$db['port']<1||$db['port']>65535)throw new RuntimeException('Configuración SQL inválida.');
        if(teko_production() && (!$db['user'] || strtolower($db['user'])==='root' || !$db['password']))
            throw new RuntimeException('Producción requiere cuentas SQL propias y contraseña.');
    }
    if(teko_production()){
        $url=parse_url((string)teko_config('PUBLIC_URL',''));
        if(!$url || ($url['scheme']??'')!=='https' || empty($url['host']) || (isset($url['user']) || isset($url['pass']))
            || isset($url['query']) || isset($url['fragment']) || in_array(strtolower($url['host']),['localhost','127.0.0.1','::1'],true))
            throw new RuntimeException('Producción requiere una URL pública HTTPS válida.');
    }
}
if(teko_production()) {ini_set('display_errors','0');ini_set('display_startup_errors','0');ini_set('log_errors','1');}
teko_validate_runtime();
// A private flag stops new PHP requests before any database access during local maintenance.
$maintenanceFile=teko_maintenance_path();
if(PHP_SAPI!=='cli' && is_file($maintenanceFile)){
    http_response_code(503);header('Retry-After: 60');header('Cache-Control: no-store');
    header('Content-Type: application/json; charset=utf-8');
    exit(json_encode(['success'=>false,'error'=>'TEKO Math está en mantenimiento. Intentá nuevamente en unos minutos.'],JSON_UNESCAPED_UNICODE));
}
