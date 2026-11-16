<?php
if(PHP_SAPI!=='cli')exit(1);
$errors=[];$checks=[];
try {
    require_once __DIR__.'/../config/runtime.php';
    require_once __DIR__.'/../01_sistema_principal_y_registro_offline/config/database.php';
    require_once __DIR__.'/../02_tekobot/TekoBot/Hackaton/config/database.php';
    if(is_file(teko_maintenance_path()))$errors[]='El modo mantenimiento está activo; retirarlo únicamente después de completar las comprobaciones.';
    foreach(['main'=>getDB(),'tutor'=>Database::getConnection()] as $module=>$db){
        $db->query('SELECT 1');$account=$db->query('SELECT CURRENT_USER()')->fetchColumn();
        $checks[]=$module.': conexión disponible con cuenta '.explode('@',$account)[0];
        if(str_starts_with(strtolower($account),'root@'))$errors[]=$module.': la aplicación todavía utiliza root.';
        foreach(teko_required_tables($module) as $table){
            try{$db->query('SELECT 1 FROM `'.$table.'` LIMIT 0');}
            catch(PDOException $e){$errors[]=$module.': falta preparar o autorizar la tabla '.$table;}
        }
    }
    $credential=getDB()->query('SELECT initial_password FROM console_credentials WHERE id=1')->fetchColumn();
    if($credential===false || (int)$credential===1)$errors[]='Cambiar la contraseña inicial de administración desde la consola antes de publicar.';
    if(!teko_production())$checks[]='Modo local conservado. Para comprobar producción, usar TEKO_ENV=production con su configuración privada.';
}catch(Throwable $e){$errors[]='La configuración no está lista: '.$e->getMessage();}
echo json_encode(['configuration_ready'=>!$errors,'checks'=>$checks,'pending'=>$errors],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
exit($errors?2:0);
