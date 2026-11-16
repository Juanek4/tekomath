<?php
require_once __DIR__.'/config/session.php';
requireLogin();
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
if(empty($_SESSION['api_csrf']))$_SESSION['api_csrf']=bin2hex(random_bytes(32));
echo json_encode(['token'=>$_SESSION['api_csrf']]);
