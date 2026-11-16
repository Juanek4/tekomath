<?php
function liveDenied(): void {
    http_response_code(403);echo json_encode(['success'=>false,'error'=>'No tenés acceso a esta sala o recurso.']);exit;
}
function enforceLiveAccess(PDO $db,array $user,?string $action): void {
    if(!in_array($action,['packs','estado'],true)&&($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){
        http_response_code(405);header('Allow: POST');echo json_encode(['success'=>false,'error'=>'Esta acción requiere POST.']);exit;
    }
    $teacherActions=['packs','crear_pack','eliminar_pack','crear_sala','iniciar_juego','siguiente_pregunta','revelar_resultado','cerrar_sala'];
    if(in_array($action,$teacherActions,true)&&$user['rol']!=='docente')liveDenied();
    if(in_array($action,['unirse_sala','responder'],true)&&$user['rol']!=='estudiante')liveDenied();
    if(!in_array($action,['unirse_sala','estado','responder','iniciar_juego','siguiente_pregunta','revelar_resultado','cerrar_sala'],true))return;
    $id=(int)($_POST['sala_id']??$_GET['sala_id']??0);
    $pin=trim((string)($_POST['codigo_pin']??$_GET['codigo_pin']??''));
    if($id>0){$q=$db->prepare('SELECT * FROM salas_clase WHERE id=?');$q->execute([$id]);}
    else{$q=$db->prepare('SELECT * FROM salas_clase WHERE codigo_pin=? ORDER BY id DESC LIMIT 1');$q->execute([$pin]);}
    $room=$q->fetch();if(!$room)liveDenied();
    if($user['rol']==='docente'){
        if((int)$room['docente_id']!==(int)$user['id'])liveDenied();
        return;
    }
    $q=$db->prepare('SELECT 1 FROM docente_alumnos WHERE docente_id=? AND estudiante_id=?');$q->execute([$room['docente_id'],$user['id']]);
    if(!$q->fetchColumn())liveDenied();
    if($action==='unirse_sala')return;
    foreach(json_decode($room['alumnos_json']??'[]',true)?:[] as $member)if((int)$member['id']===(int)$user['id'])return;
    liveDenied();
}
