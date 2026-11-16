<?php
require_once __DIR__.'/../config/api_guard.php';
tutorApiGuard(['GET']);
if(empty($_SESSION['api_csrf']))$_SESSION['api_csrf']=bin2hex(random_bytes(32));
echo json_encode(['success'=>true,'token'=>$_SESSION['api_csrf'],'user_id'=>(int)getUser()['id']]);
