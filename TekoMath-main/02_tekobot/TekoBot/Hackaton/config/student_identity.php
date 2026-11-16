<?php
require_once __DIR__.'/../../../../01_sistema_principal_y_registro_offline/config/session.php';
if(!isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>false,'error'=>'Iniciá sesión para usar el tutor.']);
    exit;
}
require_once __DIR__.'/database.php';
$portalUser=getUser();
$identityDb=Database::getConnection();
teko_schema_exec($identityDb,'CREATE TABLE IF NOT EXISTS portal_student_links (portal_user_id INTEGER PRIMARY KEY, tutor_student_id INTEGER NOT NULL UNIQUE)');
$identityLookup=$identityDb->prepare('SELECT tutor_student_id FROM portal_student_links WHERE portal_user_id=?');
$identityLookup->execute([(int)$portalUser['id']]);
$authenticatedStudentId=(int)$identityLookup->fetchColumn();
if(!$authenticatedStudentId){
    $identityDb->beginTransaction();
    try {
        // A separate tutor row avoids inheriting unrelated legacy records with the same numeric ID.
        $identityDb->prepare('INSERT INTO students(name) VALUES(?)')->execute([$portalUser['nombre']]);
        $authenticatedStudentId=(int)$identityDb->lastInsertId();
        $identityDb->prepare('INSERT INTO portal_student_links(portal_user_id,tutor_student_id) VALUES(?,?)')->execute([(int)$portalUser['id'],$authenticatedStudentId]);
        $identityDb->commit();
    } catch(Throwable $identityError){
        if($identityDb->inTransaction())$identityDb->rollBack();
        // Another request may have established the same mapping concurrently.
        $identityLookup->execute([(int)$portalUser['id']]);
        $authenticatedStudentId=(int)$identityLookup->fetchColumn();
        if(!$authenticatedStudentId)throw $identityError;
    }
}
