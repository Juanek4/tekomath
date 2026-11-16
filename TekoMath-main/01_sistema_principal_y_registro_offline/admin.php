<?php
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/database.php';
require_once __DIR__.'/config/admin_invitations.php';
require_once __DIR__.'/config/password_recovery.php';
header('Cache-Control: no-store');header('Referrer-Policy: no-referrer');
if(empty($_SESSION['admin_csrf']))$_SESSION['admin_csrf']=bin2hex(random_bytes(32));
$error='';$invitation='';$recovery='';$adminDb=getDB();$credentials=AdminInvitations::credentials($adminDb);
if(($_SESSION['console_version']??null)!==(int)$credentials['version'])unset($_SESSION['console_admin']);$authenticated=!empty($_SESSION['console_admin'])&&($_SESSION['console_until']??0)>time();
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!is_string($_POST['csrf']??null)||!hash_equals($_SESSION['admin_csrf'],$_POST['csrf'])){http_response_code(403);exit('Solicitud vencida. Recargá la página.');}
 $action=$_POST['action']??'';
 if($action==='login'){
  if(AccountSecurity::limited($adminDb,AccountSecurity::bucket('admin',(string)($_POST['username']??''))))$error='Esperá unos minutos antes de volver a intentar.';
  elseif(AdminInvitations::authenticate((string)($_POST['username']??''),(string)($_POST['password']??''))){session_regenerate_id(true);$_SESSION['console_admin']=true;$_SESSION['console_version']=(int)$credentials['version'];AccountSecurity::clear($adminDb,AccountSecurity::bucket('admin',(string)$_POST['username']));$_SESSION['console_until']=time()+1800;$_SESSION['admin_attempts']=0;header('Location: admin.php');exit;}
  else{AccountSecurity::failure($adminDb,AccountSecurity::bucket('admin',(string)($_POST['username']??'')));$_SESSION['admin_attempts']=($_SESSION['admin_retry']??0)<time()?1:($_SESSION['admin_attempts']??0)+1;$_SESSION['admin_retry']=time()+300;$error='Usuario o contraseña incorrectos.';}
 }elseif($authenticated&&$action==='change_password'){
  try{if(($_POST['new_password']??'')!==($_POST['confirm_password']??''))throw new InvalidArgumentException('Las contraseñas nuevas no coinciden.');AdminInvitations::changePassword($adminDb,(string)($_POST['current_password']??''),(string)($_POST['new_password']??''));unset($_SESSION['console_admin'],$_SESSION['console_version'],$_SESSION['console_until']);session_regenerate_id(true);header('Location: admin.php');exit;}
  catch(InvalidArgumentException $e){$error=$e->getMessage();}
 }elseif($authenticated&&$action==='reset_account'){
  try{
   if(($_POST['identity_checked']??'')!=='yes')throw new InvalidArgumentException('Confirmá la identidad de la persona antes de generar el enlace.');
   if(!is_string($_POST['reset_email']??null))throw new InvalidArgumentException('Ingresá un correo válido.');
   $recovery='recuperar.php?token='.PasswordRecovery::create($adminDb,trim($_POST['reset_email']));
  }catch(InvalidArgumentException $e){$error=$e->getMessage();}catch(Throwable $e){error_log('Reset creation failed: '.$e->getMessage());$error='No pudimos generar el enlace.';}
 }elseif($authenticated&&$action==='logout'){unset($_SESSION['console_admin'],$_SESSION['console_until']);session_regenerate_id(true);header('Location: admin.php');exit;}
 elseif($authenticated&&$action==='invite'){
  try{$db=getDB();AdminInvitations::ensure($db);$token=AdminInvitations::create($db,trim((string)($_POST['email']??'')));$invitation='docente_registro.php?token='.$token;}
  catch(InvalidArgumentException $e){$error=$e->getMessage();}catch(Throwable $e){error_log($e->getMessage());$error='No pudimos generar la invitación.';}
 }
}
$esc=fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Consola de administración · TEKO Math</title><link rel="stylesheet" href="../07_diseno_y_graficos/style.css"><link rel="stylesheet" href="../07_diseno_y_graficos/teko-ui.css?v=20261030-recovery"></head><body class="teko-management-page"><main class="admin-console teko-management-card"><a href="index.php">← Acceso principal</a><p class="management-eyebrow">TEKO Math · Gestión educativa</p><h1>Consola de administración</h1><p>Invitaciones docentes y recuperación de cuentas</p>
<?php if($error): ?><p class="management-notice management-notice--error" role="alert"><?= $esc($error) ?></p><?php endif ?>
<?php if(!$authenticated): ?><form method="post"><input type="hidden" name="csrf" value="<?= $esc($_SESSION['admin_csrf']) ?>"><input type="hidden" name="action" value="login"><label for="username">Usuario administrador</label><input id="username" name="username" autocomplete="username" required><label for="password">Contraseña</label><input id="password" type="password" name="password" autocomplete="current-password" required><button class="btn btn--primary" type="submit">Entrar a la consola</button></form>
<?php else: ?><form method="post"><input type="hidden" name="csrf" value="<?= $esc($_SESSION['admin_csrf']) ?>"><input type="hidden" name="action" value="invite"><label for="email">Correo del docente</label><input id="email" name="email" type="email" required><p>La invitación sirve una sola vez y vence en 7 días.</p><button class="btn btn--primary" type="submit">Generar invitación</button></form>
<?php if($invitation): ?><div class="admin-invitation"><label for="invite-link">Enlace para compartir</label><input id="invite-link" readonly data-path="<?= $esc($invitation) ?>"><p>Copiá este enlace y envialo al docente. No se envía ningún correo automáticamente.</p><script>document.getElementById('invite-link').value=new URL(document.getElementById('invite-link').dataset.path,location.href).href;</script></div><?php endif ?>
<section aria-labelledby="recovery-title"><h2 id="recovery-title">Recuperar cuenta de estudiante o docente</h2><p>Verificá la identidad de la persona y compartí el enlace por un canal privado. No necesitás conocer ni elegir su nueva contraseña.</p><form method="post"><input type="hidden" name="csrf" value="<?= $esc($_SESSION['admin_csrf']) ?>"><input type="hidden" name="action" value="reset_account"><label for="reset-email">Correo de la cuenta</label><input id="reset-email" name="reset_email" type="email" maxlength="150" required><label class="recovery-identity-check"><input type="checkbox" name="identity_checked" value="yes" required> Verifiqué la identidad de la persona</label><button class="btn btn--primary" type="submit">Generar enlace de recuperación</button></form>
<?php if($recovery): ?><div class="admin-invitation"><label for="recovery-link">Enlace privado de recuperación</label><input id="recovery-link" readonly data-path="<?= $esc($recovery) ?>"><p>Vence en 30 minutos y sirve una sola vez. Reemplaza cualquier enlace anterior de esta cuenta. No se envía un correo automáticamente.</p><script>document.getElementById('recovery-link').value=new URL(document.getElementById('recovery-link').dataset.path,location.href).href;</script></div><?php endif ?></section>
<section><h2>Cambiar contraseña de administración</h2><p>Después de cambiarla se cerrarán los accesos administrativos anteriores.</p>
<?php if($credentials['initial_password']): ?><p class="management-notice">Todavía usás la contraseña inicial. Cambiala antes de publicar la plataforma.</p><?php endif ?>
<form method="post"><input type="hidden" name="csrf" value="<?= $esc($_SESSION['admin_csrf']) ?>"><input type="hidden" name="action" value="change_password"><label for="current-password">Contraseña actual</label><input id="current-password" name="current_password" type="password" autocomplete="current-password" required><label for="new-password">Nueva contraseña de 12 a 72 caracteres</label><input id="new-password" name="new_password" type="password" autocomplete="new-password" minlength="12" maxlength="72" required><label for="confirm-password">Repetir nueva contraseña</label><input id="confirm-password" name="confirm_password" type="password" autocomplete="new-password" required><button class="btn btn--primary" type="submit">Cambiar contraseña y cerrar consola</button></form></section>
<form method="post"><input type="hidden" name="csrf" value="<?= $esc($_SESSION['admin_csrf']) ?>"><input type="hidden" name="action" value="logout"><button class="btn" type="submit">Cerrar consola</button></form><?php endif ?></main></body></html>
