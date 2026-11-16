<?php
function apiMutationGuard(): void {
 if(!in_array($_SERVER['REQUEST_METHOD']??'GET',['POST','PUT','PATCH','DELETE'],true))return;
 $expected=$_SESSION['api_csrf']??null;$supplied=$_SERVER['HTTP_X_TEKO_CSRF']??null;
 if(!is_string($expected)||!is_string($supplied)||!hash_equals($expected,$supplied)){
  http_response_code(403);echo json_encode(['success'=>false,'error'=>'La sesión de la solicitud venció. Recargá la página e intentá de nuevo.']);exit;
 }
}
