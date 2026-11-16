<?php
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/database.php';
require_once __DIR__.'/config/password_recovery.php';
header('Cache-Control: no-store');header('Referrer-Policy: no-referrer');header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
$db=getDB();PasswordRecovery::ensure($db);
if(empty($_SESSION['recovery_csrf']))$_SESSION['recovery_csrf']=bin2hex(random_bytes(32));
if($_SERVER['REQUEST_METHOD']==='GET' && isset($_GET['token'])){
    $_SESSION['password_reset_token']=is_string($_GET['token'])&&preg_match('/^[a-f0-9]{64}$/D',$_GET['token'])?$_GET['token']:'';
    header('Location: recuperar.php',true,303);exit;
}
$token=$_SESSION['password_reset_token']??'';$error='';$success=false;
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!is_string($_POST['csrf']??null)||!hash_equals($_SESSION['recovery_csrf'],$_POST['csrf'])){
        http_response_code(403);$error='Recargá el formulario e intentá nuevamente.';
    }else{
        try{
            $bucket=AccountSecurity::bucket('password_recovery',$token?:'no_link');
            if(AccountSecurity::limited($db,$bucket)){http_response_code(429);throw new InvalidArgumentException('Esperá cinco minutos antes de intentar nuevamente.');}
            if(!is_string($_POST['password']??null)||!is_string($_POST['confirm']??null)||$_POST['password']!==$_POST['confirm'])throw new InvalidArgumentException('Las contraseñas no coinciden.');
            PasswordRecovery::redeem($db,$token,$_POST['password']);
            unset($_SESSION['password_reset_token']);$_SESSION['recovery_csrf']=bin2hex(random_bytes(32));
            AccountSecurity::clear($db,$bucket);$success=true;
        }catch(InvalidArgumentException $e){if(http_response_code()!==429)AccountSecurity::failure($db,$bucket);$error=$e->getMessage();}
        catch(Throwable $e){error_log('Password recovery failed: '.$e->getMessage());$error='No pudimos cambiar la contraseña. Intentá nuevamente.';http_response_code(500);}
    }
}elseif($_SERVER['REQUEST_METHOD']!=='GET'){http_response_code(405);header('Allow: GET, POST');exit;}
$valid=!$success && PasswordRecovery::valid($db,$token);
$esc=fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Recuperar acceso · TEKO Math</title><link rel="stylesheet" href="../07_diseno_y_graficos/style.css"><link rel="stylesheet" href="../07_diseno_y_graficos/teko-ui.css?v=20261030-recovery"></head>
<body class="teko-management-page"><main class="teko-management-card admin-console"><a href="index.php">← Iniciar sesión</a><p class="management-eyebrow">TEKO Math · Acceso a tu cuenta</p><h1>Recuperar contraseña</h1>
<?php if($error): ?><p class="management-notice management-notice--error" role="alert"><?= $esc($error) ?></p><?php endif ?>
<?php if($success): ?><p class="management-notice" role="status">Tu contraseña fue actualizada. Las sesiones anteriores quedaron invalidadas. Iniciá sesión con tu nueva contraseña.</p><a class="btn btn--primary" href="index.php">Volver al inicio de sesión</a>
<?php elseif($valid): ?><p>Elegí una contraseña nueva. El enlace vence a los 30 minutos de su creación y sirve una sola vez.</p><form method="post" action="recuperar.php"><input type="hidden" name="csrf" value="<?= $esc($_SESSION['recovery_csrf']) ?>"><label for="password">Nueva contraseña</label><input id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required><p>Usá al menos 8 caracteres. Si elegís una contraseña muy larga, puede que necesites acortarla.</p><label for="confirm">Repetir contraseña</label><input id="confirm" name="confirm" type="password" minlength="8" maxlength="72" autocomplete="new-password" required><button class="btn btn--primary" type="submit">Guardar nueva contraseña</button></form>
<?php else: ?><p>Para recuperar tu cuenta, pedile a la coordinación de TEKO Math un enlace privado e indicá el correo con el que te registraste. La coordinación verificará tu identidad antes de compartirlo.</p><p>Si ya tenías un enlace, puede haber vencido o haber sido utilizado. Pedí uno nuevo. No se envían correos automáticamente en esta instalación local.</p><?php endif ?>
</main></body></html>
