<?php
function teachingResourceScope(array $user,string $alias): string {
    if(!in_array($alias,['t','m'],true))throw new InvalidArgumentException('Invalid resource alias');
    $id=(int)$user['id'];
    if($user['rol']==='docente')return "$alias.docente_id=$id";
    if($user['rol']==='estudiante')return "EXISTS (SELECT 1 FROM docente_alumnos access_link WHERE access_link.docente_id=$alias.docente_id AND access_link.estudiante_id=$id)";
    return '1=0';
}
function requireTeachingResource(PDO $db,array $user,string $table,int $id): void {
    if(!in_array($table,['tareas','materiales'],true))throw new InvalidArgumentException('Invalid resource table');
    $q=$db->prepare("SELECT t.id FROM $table t WHERE t.id=? AND ".teachingResourceScope($user,'t'));
    $q->execute([$id]);
    if(!$q->fetchColumn()){
        http_response_code(404);echo json_encode(['success'=>false,'error'=>'Recurso no disponible.']);exit;
    }
}
