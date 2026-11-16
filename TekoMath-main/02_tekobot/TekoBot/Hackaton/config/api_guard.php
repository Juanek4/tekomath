<?php
require_once __DIR__.'/../../../../01_sistema_principal_y_registro_offline/config/session.php';

function tutorApiError(int $status, string $message): void {
    http_response_code($status);
    echo json_encode(['success'=>false,'error'=>$message],JSON_UNESCAPED_UNICODE);
    exit;
}

function tutorApiGuard(array $methods): void {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    if(!isLoggedIn())tutorApiError(401,'Iniciá sesión para usar el tutor.');
    $method=$_SERVER['REQUEST_METHOD']??'GET';
    if(!in_array($method,$methods,true)){
        header('Allow: '.implode(', ',$methods));
        tutorApiError(405,'Método no permitido.');
    }
    if(in_array($method,['POST','PUT','PATCH','DELETE'],true)){
        $expected=$_SESSION['api_csrf']??null;$token=$_SERVER['HTTP_X_TEKO_CSRF']??null;
        if(!is_string($expected)||!is_string($token)||!hash_equals($expected,$token))
            tutorApiError(403,'La solicitud no es válida. Recargá la página e intentá nuevamente.');
        $type=strtolower(trim(explode(';',$_SERVER['CONTENT_TYPE']??'')[0]));
        if($type!=='application/json')tutorApiError(415,'Enviá la solicitud en formato JSON.');
        if((int)($_SERVER['CONTENT_LENGTH']??0)>65536)tutorApiError(413,'La solicitud es demasiado grande.');
    }
}

function tutorApiJson(): array {
    $raw=file_get_contents('php://input',false,null,0,65537);
    if(strlen($raw)>65536)tutorApiError(413,'La solicitud es demasiado grande.');
    $data=json_decode($raw,true);
    if(!is_array($data)||!str_starts_with(ltrim($raw),'{'))tutorApiError(400,'Solicitud JSON inválida.');
    return $data;
}

function tutorApiFailure(Throwable $error): void {
    if($error instanceof InvalidArgumentException)tutorApiError(422,'Revisá los datos de la solicitud.');
    if($error->getMessage()==='Conversación no disponible.')tutorApiError(404,'Conversación no disponible.');
    if($error->getMessage()==='La conversación cambió; volvé a cargarla.')tutorApiError(409,'La conversación cambió; volvé a cargarla.');
    error_log('TekoBot API failure: '.$error->getMessage());
    tutorApiError(500,'No pudimos completar la solicitud. Intentá nuevamente.');
}
