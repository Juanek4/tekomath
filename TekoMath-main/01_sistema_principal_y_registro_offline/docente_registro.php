<?php
require_once __DIR__.'/config/session.php';require_once __DIR__.'/config/database.php';require_once __DIR__.'/config/admin_invitations.php';
header('Referrer-Policy: no-referrer');header('Cache-Control: no-store');
if(empty($_SESSION['invite_csrf']))$_SESSION['invite_csrf']=bin2hex(random_bytes(32));
$token=is_string($_GET['token']??null)?$_GET['token']:'';$error='';$success=false;
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  if(!is_string($_POST['csrf']??null)||!hash_equals($_SESSION['invite_csrf'],$_POST['csrf']))throw new InvalidArgumentException('Recargá el formulario e intentá de nuevo.');
  if(($_POST['password']??'')!==($_POST['confirm']??''))throw new InvalidArgumentException('Las contraseñas no coinciden.');
  $db=getDB();AdminInvitations::ensure($db);AdminInvitations::redeem($db,$token,(string)($_POST['name']??''),trim((string)($_POST['email']??'')),(string)($_POST['password']??''));$success=true;
 }catch(InvalidArgumentException $e){$error=$e->getMessage();}catch(Throwable $e){error_log($e->getMessage());$error='No pudimos crear la cuenta. Comprobá si tu correo ya está registrado.';}
}
$esc=fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="referrer" content="no-referrer"><title>Crear cuenta docente · TEKO Math</title><link rel="stylesheet" href="../07_diseno_y_graficos/style.css"><link rel="stylesheet" href="../07_diseno_y_graficos/teko-ui.css?v=20261028-management"></head><body class="teko-management-page"><main class="admin-console teko-management-card"><p class="management-eyebrow">TEKO Math · Gestión educativa</p><h1>Crear cuenta docente</h1>
<?php if($success): ?><p class="management-notice" role="status">Tu cuenta docente está lista.</p><a class="btn btn--primary" href="index.php">Iniciar sesión</a>
<?php elseif(!preg_match('/^[a-f0-9]{64}$/D',$token)): ?><p>Necesitás una invitación válida de la coordinación.</p>
<?php else: ?><?php if($error): ?><p class="management-notice management-notice--error" role="alert"><?= $esc($error) ?></p><?php endif ?><p>Usá el correo al que está dirigida tu invitación.</p><form method="post"><input type="hidden" name="csrf" value="<?= $esc($_SESSION['invite_csrf']) ?>"><label for="name">Nombre completo</label><input id="name" name="name" required minlength="2" maxlength="100" autocomplete="name"><label for="email">Correo electrónico</label><input id="email" name="email" type="email" required autocomplete="email"><label for="password">Contraseña (mínimo 8 caracteres)</label><input id="password" name="password" type="password" required minlength="8" autocomplete="new-password"><label for="confirm">Confirmar contraseña</label><input id="confirm" name="confirm" type="password" required minlength="8" autocomplete="new-password"><button class="btn btn--primary" type="submit">Crear mi cuenta docente</button></form><?php endif ?></main></body></html>
