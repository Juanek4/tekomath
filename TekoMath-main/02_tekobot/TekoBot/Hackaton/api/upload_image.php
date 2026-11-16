<?php
// Legacy server upload disabled: both modules recognize locally with the shared engine.
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../../../../01_sistema_principal_y_registro_offline/config/session.php';
if(!isLoggedIn()){http_response_code(401);echo json_encode(['success'=>false,'error'=>'Iniciá sesión.']);exit;}
http_response_code(410);echo json_encode(['success'=>false,'error'=>'La lectura de imágenes ahora se realiza en el navegador. Recargá la página para usar el lector compartido.']);
